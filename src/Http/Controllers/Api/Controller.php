<?php

namespace Phpsa\LaravelApiController\Http\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response as Res;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Phpsa\LaravelApiController\UriParser;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller as LaravelController;
use Phpsa\LaravelApiController\Exceptions\ApiException;
use Phpsa\LaravelApiController\Repository\BaseRepository;
use Phpsa\LaravelApiController\Exceptions\UnknownColumnException;

abstract class Controller extends LaravelController
{
    /**
     * HTTP header status code.
     *
     * @var int
     */
    protected $statusCode = Res::HTTP_OK;

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
     * Resource key for an item.
     *
     * @var string
     */
    protected $resourceKeySingular = 'data';

    /**
     * Resource key for a collection.
     *
     * @var string
     */
    protected $resourceKeyPlural = 'data';

    /**
     * Holds teh current authed user object.
     *
     * @var User
     */
    protected $user;

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
    protected $maximumLimit;

    protected $tableColumns;

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

        $this->tableColumns = Schema::getColumnListing($this->model->getTable());
    }

    /**
     * @throws ApiException
     * @return Model|mixed
     */
    public function makeModel()
    {
        $model = resolve($this->model());

        if (! $model instanceof Model) {
            throw new ApiException("Class {$this->model()} must be an instance of ".Model::class);
        }

        return $this->model = $model;
    }

    public function makeRepository()
    {
        $this->repository = BaseRepository::withModel($this->model());
    }

    /**
     * Eloquent model.
     *
     * @return Model
     */
    abstract protected function model();

    protected function _parseWith()
    {
        $field = config('laravel-api-controller.parameters.include');
        if (empty($field)) {
            return;
        }
        $with = $this->request->input($field);
        if ($with !== null) {
            $this->repository->with(explode(',', $with));
        }
    }

    protected function _parseSort()
    {
        $field = config('laravel-api-controller.parameters.sort');
        $sort = $field && $this->request->has($field) ? $this->request->input($field) : $this->defaultSort;

        if ($sort) {
            $sorts = is_array($sort) ? $sort : explode(',', $sort);
            if (empty($sorts)) {
                return;
            }

            foreach ($sorts as $sort) {
                if (empty($sort)) {
                    continue;
                }
                $sortP = explode(' ', $sort);

                $sortF = $sortP[0];

                if (! in_array($sortF, $this->tableColumns)) {
                    continue;
                }

                $sortD = ! empty($sortP[1]) && strtolower($sortP[1]) == 'asc' ? 'asc' : 'desc';
                $this->repository->orderBy($sortF, $sortD);
            }
        }
    }

    protected function _parseWhere()
    {
        $where = $this->uriParser->whereParameters();
        if (! empty($where)) {
            foreach ($where as $whr) {
                if (strpos($whr['key'], '.') > 0) {
                    //test if exists in the withs, if not continue out to exclude from the qbuild
                    //continue;
                } else {
                    if (! in_array($whr['key'], $this->tableColumns)) {
                        continue;
                    }
                }
                switch ($whr['type']) {
                    case 'In':
                        if (! empty($whr['values'])) {
                            $this->repository->whereIn($whr['key'], $whr['values']);
                        }
                        break;
                    case 'NotIn':
                        if (! empty($whr['values'])) {
                            $this->repository->whereNotIn($whr['key'], $whr['values']);
                        }
                        break;
                    case 'Basic':
                        $this->repository->where($whr['key'], $whr['value'], $whr['operator']);

                        break;
                }
            }
        }
    }

    public function _parseFields()
    {
        $attributes = $this->model->attributesToArray();
        $fields = $this->request->has('fields') && ! empty($this->request->input('fields')) ? explode(',', $this->request->input('fields')) : $this->defaultFields;
        foreach ($fields as $k => $field) {
            if ($field === '*') {
                continue;
            }
            if (strpos($field, '.') > 0) {
                //check if mapped field exists
                //@todo
                unset($fields[$k]);
                continue;
            }
            if (! in_array($field, $this->tableColumns)) {
                //does the attribute exist ?

                if (! array_key_exists($field, $attributes)) {
                    throw new UnknownColumnException($field.' does not exist in table');
                }
                unset($fields[$k]);
            }
        }

        return $fields;
    }

    /**
     * Display a listing of the resource.
     * GET /api/{resource}.
     *
     * @return Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $this->_parseWith();
        $this->_parseSort();
        $this->_parseWhere();
        $fields = $this->_parseFields();

        $limit = $this->request->has('limit') ? intval($this->request->input('limit')) : $this->defaultLimit;
        if ($this->maximumLimit && ($limit > $this->maximumLimit || ! $limit)) {
            $limit = $this->maximumLimit;
        }

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

        if (! $data) {
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

        return $this->respondCreated($item->id);
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
        $this->_parseWith();
        $fields = empty($this->request->input('fields')) ? $this->defaultFields : explode(',', $this->request->input('fields'));

        try {
            $item = $this->repository->getById($id);
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

        if (! $data) {
            return $this->errorWrongArgs('Empty request');
        }

        $item = $this->repository->getById($id);
        if ($item->doesntExist()) {
            return $this->errorNotFound();
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
        $item = $this->repository->getById($id);
        if ($item->doesntExist()) {
            return $this->errorNotFound();
        }
        $item->delete();

        return response()->json(['message' => 'Deleted']);
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
    public function edit($id)
    {
        return $this->errorNotImplemented();
    }

    /**
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param $message
     * @return json Res
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * Respond with a given item.
     *
     * @param $item
     *
     * @return mixed
     */
    protected function respondWithOne($item)
    {
        return $this->respond([
            'status'      					=> 'success',
            'status_code' 					=> Res::HTTP_OK,
            $this->resourceKeySingular      => $item,
        ]);
    }

    /**
     * Respond with a given collection of items.
     *
     * @param $items
     * @param int $skip
     * @param int $limit
     *
     * @return mixed
     */
    protected function respondWithMany($items)
    {
        return $this->respond([
            'status'      					=> 'success',
            'status_code' 					=> Res::HTTP_OK,
            $this->resourceKeyPlural        => $items,
        ]);
    }

    /**
     * Created Response.
     *
     * @param [int] $id of insterted data
     * @param [type] $message
     *
     * @return void
     */
    public function respondCreated($id = null, $message = null)
    {
        $response = [
            'status'      		=> 'success',
            'status_code' 		=> Res::HTTP_CREATED,
        ];
        if ($message !== null) {
            $response['message'] = $message;
        }
        if ($id !== null) {
            if (is_scalar($id)) {
                $response[$this->resourceKeySingular] = $id;
            } else {
                $response[$this->resourceKeyPlural] = $id;
            }
        }

        return $this->respond($response);
    }

    /**
     * @param LengthAwarePaginator $paginate
     * @param $data
     * @return mixed
     */
    protected function respondWithPagination(LengthAwarePaginator $paginator)
    {
        return $this->respond([
            'status'      					=> 'success',
            'status_code' 					=> Res::HTTP_OK,
            'paginator' => [
                'total_count'  => $paginator->total(),
                'total_pages'  => ceil($paginator->total() / $paginator->perPage()),
                'current_page' => $paginator->currentPage(),
                'limit'        => $paginator->perPage(),
            ],
            $this->resourceKeyPlural => $paginator->items(),
        ]);
    }

    /**
     * Respond with a given response.
     *
     * @param mixed $data
     * @param array $headers
     *
     * @return mixed
     */
    protected function respond($data, $code = null, $headers = [])
    {
        return response()->json(
            $data,
            $code ? $code : $this->getStatusCode(),
            $headers
        );
    }

    /**
     * Response with the current error.
     *
     * @param string $message
     *
     * @return mixed
     */
    protected function respondWithError($message)
    {
        return $this->respond([
            'status'      => 'error',
            'status_code' =>  $this->statusCode,
            'message'     => $message,
        ]);
    }

    /**
     * Generate a Response with a 403 HTTP header and a given message.
     *
     * @param $message
     *
     * @return Response
     */
    protected function errorForbidden($message = 'Forbidden')
    {
        return $this->setStatusCode(403)->respondWithError($message);
    }

    /**
     * Generate a Response with a 500 HTTP header and a given message.
     *
     * @param string $message
     *
     * @return Response
     */
    protected function errorInternalError($message = 'Internal Error')
    {
        return $this->setStatusCode(500)->respondWithError($message);
    }

    /**
     * Generate a Response with a 404 HTTP header and a given message.
     *
     * @param string $message
     *
     * @return Response
     */
    protected function errorNotFound($message = 'Resource Not Found')
    {
        return $this->setStatusCode(404)->respondWithError($message);
    }

    /**
     * Generate a Response with a 401 HTTP header and a given message.
     *
     * @param string $message
     *
     * @return Response
     */
    protected function errorUnauthorized($message = 'Unauthorized')
    {
        return $this->setStatusCode(401)->respondWithError($message);
    }

    /**
     * Generate a Response with a 400 HTTP header and a given message.
     *
     * @param string$message
     *
     * @return Response
     */
    protected function errorWrongArgs($message = 'Wrong Arguments')
    {
        return $this->setStatusCode(400)->respondWithError($message);
    }

    /**
     * Generate a Response with a 501 HTTP header and a given message.
     *
     * @param string $message
     *
     * @return Response
     */
    protected function errorNotImplemented($message = 'Not implemented')
    {
        return $this->setStatusCode(501)->respondWithError($message);
    }

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
    protected function rulesForUpdate($id)
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
