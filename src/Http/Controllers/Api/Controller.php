<?php

namespace Phpsa\LaravelApiController\Http\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Phpsa\LaravelApiController\UriParser;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Phpsa\LaravelApiController\Traits\Parser;
use Phpsa\LaravelApiController\Events\Created;
use Phpsa\LaravelApiController\Events\Deleted;
use Phpsa\LaravelApiController\Events\Updated;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Resources\Json\ResourceCollection;
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
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;
    use ApiResponse;
    use Parser;

    /**
     * Eloquent model instance.
     *
     * @var mixed|Model instance
     */
    protected $model;

    /**
     * Repository instance.
     *
     * @var mixed|BaseRepository
     */
    protected $repository;

    /**
     * Illuminate\Http\Request instance.
     *
     * @var mixed|Request
     */
    protected $request;

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
     * @deprecated 0.5.0 - see auth()->user() || Request::user()
     */
    protected $user;

    /**
     * Resource key for an item.
     *
     * @var string
     * @deprecated 0.5.0 - to be removed 0.6.0
     */
    protected $resourceKeySingular = 'data';

    /**
     * Resource key for a collection.
     *
     * @var string
     * @deprecated 0.5.0 - to be removed 0.6.0
     */
    protected $resourceKeyPlural = 'data';

    /**
     * Resource for item.
     *
     * @var mixed instance of \Illuminate\Http\Resources\Json\JsonResource
     */
    protected $resourceSingle = JsonResource::class;

    /**
     * Resource for collection.
     *
     * @var mixed instance of \Illuminate\Http\Resources\Json\ResourceCollection
     */
    protected $resourceCollection = ResourceCollection::class;

    /**
     * Default Fields to response with.
     *
     * @var array
     */
    protected $defaultFields = ['*'];

    /**
     * Set the default sorting for queries.
     *
     * @var string
     */
    protected $defaultSort = null;

    /**
     * Number of items displayed at once if not specified.
     * There is no limit if it is 0 or false.
     *
     * @var int
     */
    protected $defaultLimit = 25;

    /**
     * Maximum limit that can be set via $_GET['limit'].
     *
     * @var int
     */
    protected $maximumLimit = 0;

    /**
     * Constructor.
     *
     * @param Request $request
     */
    public function __construct()
    {
        $this->makeModel();
        $this->makeRepository();
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
            throw new ApiException("Class {$this->model()} must be an instance of " . Model::class);
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
     * @param Request $request
     *
     * @return Response
     */
    public function handleIndexAction($request)
    {
        if (! is_a($request, Request::class)) {
            throw new ApiException("Request should be an instance of Illuminate\Http\Request");
        }

        try {
            $res = $this->authorize('viewAny', $this->model());
        } catch (AuthorizationException $exception) {
            return $this->errorForbidden($exception->getMessage());
        }

        $this->request = $request;
        $this->uriParser = new UriParser($this->request, config('laravel-api-controller.parameters.filter'));

        $this->parseIncludeParams();
        $this->parseSortParams();
        $this->parseFilterParams();
        $fields = $this->parseFieldParams();
        $limit = $this->parseLimitParams();

        $items = $limit > 0 ? $this->repository->paginate($limit, $fields) : $this->repository->get($fields);

        return $this->respondWithMany($items);
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/{resource}.
     *
     * @return Response
     */
    public function handleStoreAction($request)
    {
        if (! is_a($request, Request::class)) {
            throw new ApiException("Request should be an instance of Illuminate\Http\Request");
        }

        try {
            $res = $this->authorize('create', $this->model());
        } catch (AuthorizationException $exception) {
            return $this->errorForbidden($exception->getMessage());
        }

        $data = $request->all();

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
            event(new Created($item, $request));
        } catch (\Exception $e) {
            return $this->errorWrongArgs($e->getMessage());
        }

        return $this->respondItemCreated($this->repository->getById($item->id));
    }

    /**
     * Display the specified resource.
     * GET /api/{resource}/{id}.
     *
     * @param int $id
     *
     * @return Response
     */
    public function handleShowAction($id, $request)
    {
        if (! is_a($request, Request::class)) {
            throw new ApiException("Request should be an instance of Illuminate\Http\Request");
        }

        try {
            $res = $this->authorize('view', $this->model::find($id));
        } catch (AuthorizationException $exception) {
            return $this->errorForbidden($exception->getMessage());
        }

        $this->request = $request;
        $this->uriParser = new UriParser($this->request, config('laravel-api-controller.parameters.filter'));

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
    public function handleUpdateAction($id, $request)
    {
        if (! is_a($request, Request::class)) {
            throw new ApiException("Request should be an instance of Illuminate\Http\Request");
        }

        try {
            $this->authorize('update', $this->model::find($id));
        } catch (AuthorizationException $exception) {
            return $this->errorForbidden($exception->getMessage());
        }

        $data = $request->all();

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

        event(new Updated($item, $request));

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
    public function handleDestroyAction($id, $request)
    {
        if (! is_a($request, Request::class)) {
            throw new ApiException("Request should be an instance of Illuminate\Http\Request");
        }

        try {
            $this->authorize('delete', $this->model::find($id));
        } catch (AuthorizationException $exception) {
            return $this->errorForbidden($exception->getMessage());
        }

        try {
            $item = $this->repository->getById($id);
            $this->repository->deleteById($id);
            event(new Deleted($item, $request));
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
     * @return string
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
     *
     * @deprecated 0.5.0
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
     * @deprecated 0.5.0
     */
    protected function hasAllRoles($roles)
    {
        return $this->user && $this->user->hasRole($roles, true);
    }
}
