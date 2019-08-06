<?php

namespace Phpsa\LaravelApiController\Http\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Phpsa\LaravelApiController\UriParser;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Phpsa\LaravelApiController\Traits\Parser;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Phpsa\LaravelApiController\Exceptions\ApiException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Phpsa\LaravelApiController\Repository\BaseRepository;
use Phpsa\LaravelApiController\Traits\Response as ApiResponse;

/**
 * Class Controller.
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, ApiResponse, Parser;

    /**
     * Eloquent model instance.
     *
     * @var Model;
     */
    protected $model;

    /**
     * Repository instance.
     *
     * @var BaseRepository
     */
    protected $repository;

    /**
     * Illuminate\Http\Request instance.
     *
     * @var Request
     */
    protected $request;

    /**
     * UriParser instance.
     *
     * @var UriParser
     */
    protected $uriParser;

    /**
     * Do we need to unguard the model before create/update?
     *
     * @var bool
     */
    protected $unguard = false;

    /**
     * Holds the current authed user object.
     *
     * @var \Illuminate\Foundation\Auth\User
     */
    protected $user;

    /**
     * Constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->makeModel();
        $this->makeRepository();
        $this->request = $request;
        $this->uriParser = new UriParser($request, config('laravel-api-controller.parameters.filter'));
        $this->user = auth()->user();
    }

    /**
     * @throws ApiException
     * @return Model|mixed
     */
    protected function makeModel()
    {
        $model = resolve($this->model());

        if (! $model instanceof Model) {
            throw new ApiException("Class {$this->model()} must be an instance of ".Model::class);
        }

        return $this->model = $model;
    }

    protected function makeRepository()
    {
        $this->repository = BaseRepository::withModel($this->model());
    }

    /**
     * Display a listing of the resource.
     * GET /api/{resource}.
     *
     * @return Response
     */
    public function index()
    {
        $this->parseIncludeParams();
        $this->parseSortParams();
        $this->parseFilterParams();
        $fields = $this->parseFieldParams();
        $limit = $this->parseLimitParams();

        return $limit > 0 ? $this->respondWithPagination(
            $this->repository->paginate($limit, $fields)
        ) : $this->respondWithMany(
            $this->repository->get($fields)
        );
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/{resource}.
     *
     * @return Response
     */
    public function store()
    {
        $data = $this->request->all();

        if (empty($data)) {
            return $this->errorWrongArgs('Empty request');
        }

        $validator = Validator::make($data, $this->rulesForCreate());

        if ($validator->fails()) {
            return $this->errorWrongArgs($validator->messages());
        }

        $columns = Schema::getColumnListing($this->model->getTable());

        $insert = array_intersect_key($data, array_flip($columns));

        $this->unguardIfNeeded();

        try {
            $item = $this->model->create($insert);
        } catch (\Exception $e) {
            return $this->errorWrongArgs($e->getMessage());
        }

        return $this->respondCreated($this->repository->getById($item->id));
    }

    /**
     * Display the specified resource.
     * GET /api/{resource}/{id}.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $this->parseIncludeParams();
        $fields = $this->parseFieldParams();

        try {
            $item = $this->repository->getById($id, $fields);
        } catch (\Exception $e) {
            return $this->errorNotFound('Record not found');
        }

        return $this->respondWithOne($item);
    }

    /**
     * Update the specified resource in storage.
     * PUT /api/{resource}/{id}.
     *
     * @param int $id
     *
     * @return Response
     */
    public function update($id)
    {
        $data = $this->request->all();

        if (empty($data)) {
            return $this->errorWrongArgs('Empty request');
        }

        try {
            $item = $this->repository->getById($id);
        } catch (ModelNotFoundException $e) {
            return $this->errorNotFound('Record does not exist');
        }

        $validator = Validator::make($data, $this->rulesForUpdate($item->id));

        if ($validator->fails()) {
            return $this->errorWrongArgs($validator->messages());
        }

        $columns = Schema::getColumnListing($this->model->getTable());

        $fields = array_intersect_key($data, array_flip($columns));

        $this->unguardIfNeeded();
        $item->fill($fields);
        $item->save();

        return $this->respondWithOne($item);
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/{resource}/{id}.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        try {
            $this->repository->deleteById($id);
        } catch (ModelNotFoundException $e) {
            return $this->errorNotFound('Record does not exist');
        }

        return $this->respondNoContent();
    }

    /**
     * Show the form for creating the specified resource.
     *
     * @return Response
     */
    public function create()
    {
        return $this->errorNotImplemented();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit(/* @scrutinizer ignore-unused */ $id)
    {
        return $this->errorNotImplemented();
    }

    /**
     * Eloquent model.
     *
     * @return Model
     */
    abstract protected function model();

    /**
     * Get the validation rules for create.
     *
     * @return array
     */
    protected function rulesForCreate()
    {
        return [];
    }

    /**
     * Get the validation rules for update.
     *
     * @param int $id
     *
     * @return array
     */
    protected function rulesForUpdate(/* @scrutinizer ignore-unused */ $id)
    {
        return [];
    }

    /**
     * Unguard eloquent model if needed.
     */
    protected function unguardIfNeeded()
    {
        if ($this->unguard) {
            $this->model->unguard();
        }
    }

    /**
     * Check if the user has one or more roles.
     *
     * @param mixed $role role name or array of role names
     *
     * @return bool
     * @author Craig Smith <craig.smith@customd.com>
     * @copyright 2018 Custom D
     * @since 1.0.0
     */
    protected function hasRole($role)
    {
        return $this->user && $this->user->hasRole($role);
    }

    /**
     * Checks if user has all the passed roles.
     *
     * @param array $roles
     *
     * @return bool
     * @author Craig Smith <craig.smith@customd.com>
     * @copyright 2018 Custom D
     * @since 1.0.0
     */
    protected function hasAllRoles($roles)
    {
        return $this->user && $this->user->hasRole($roles, true);
    }
}
