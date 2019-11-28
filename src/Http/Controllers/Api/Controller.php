<?php

namespace Phpsa\LaravelApiController\Http\Api;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Phpsa\LaravelApiController\Contracts\ModelRepository;
use Phpsa\LaravelApiController\Contracts\Parser;
use Phpsa\LaravelApiController\Contracts\Policies;
use Phpsa\LaravelApiController\Contracts\Relationships;
use Phpsa\LaravelApiController\Contracts\Response as ApiResponse;
use Phpsa\LaravelApiController\Contracts\Validation;
use Phpsa\LaravelApiController\Events\Created;
use Phpsa\LaravelApiController\Events\Deleted;
use Phpsa\LaravelApiController\Events\Updated;
use Phpsa\LaravelApiController\Exceptions\ApiException;
use Phpsa\LaravelApiController\Http\Resources\ApiCollection;
use Phpsa\LaravelApiController\Http\Resources\ApiResource;

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
    use Relationships;
    use Policies;
    use ModelRepository;
    use Validation;

    /**
     * Do we need to unguard the model before create/update?
     *
     * @var bool
     */
    protected $unguard = false;

    /**
     * Resource for item.
     *
     * @var mixed instance of \Illuminate\Http\Resources\Json\JsonResource
     */
    protected $resourceSingle = ApiResource::class;

    /**
     * Resource for collection.
     *
     * @var mixed instance of \Illuminate\Http\Resources\Json\ResourceCollection
     */
    protected $resourceCollection = ApiCollection::class;

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

    protected $includesWhitelist = [];

    protected $includesBlacklist = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->makeModel();
        $this->makeRepository();
    }

    /**
     * Display a listing of the resource.
     * GET /api/{resource}.
     *
     * @param \Illuminate\Http\Request|\Illuminate\Foundation\Http\FormRequest $request
     */
    public function handleIndexAction($request)
    {
        $this->validateRequestType($request);
        $this->authoriseUserAction('viewAny');
        $this->getUriParser($request);

        $this->parseIncludeParams();
        $this->parseSortParams();
        $this->parseFilterParams();
        $fields = $this->parseFieldParams();
        $limit = $this->parseLimitParams();

        $this->qualifyCollectionQuery();

        $items = $limit > 0 ? $this->repository->paginate($limit, $fields) : $this->repository->get($fields);

        return $this->respondWithMany($items);
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/{resource}.
     *
     * @param \Illuminate\Http\Request|\Illuminate\Foundation\Http\FormRequest $request
     */
    public function handleStoreAction($request)
    {
        $this->validateRequestType($request);
        $this->authoriseUserAction('create');

        $data = $request->all();

        if (empty($data)) {
            return $this->errorWrongArgs('Empty request');
        }

        $this->validate($request, $this->rulesForCreate());

        $columns = $this->getTableColumns();

        $data = $this->qualifyStoreQuery($data);

        $insert = array_intersect_key($data, array_flip($columns));

        $diff = array_diff(array_keys($data), array_keys($insert));

        $this->unguardIfNeeded();

        DB::beginTransaction();

        try {
            $item = self::$model->create($insert);

            $this->storeRelated($item, $diff, $data);

            event(new Created($item, $request));

            DB::commit();

            return $this->respondItemCreated($this->repository->getById($item->id));
        } catch (\Illuminate\Database\QueryException $exception) {
            $message = config('app.debug') ? $exception->getMessage() : 'Failed to create Record';

            throw new ApiException($message);
        } catch (\Exception $exception) {
            DB::rollback();

            return $this->errorWrongArgs($exception->getMessage());
        }
    }

    /**
     * Display the specified resource.
     * GET /api/{resource}/{id}.
     *
     * @param int $id
     * @param \Illuminate\Http\Request|\Illuminate\Foundation\Http\FormRequest $request
     */
    public function handleShowAction($id, $request)
    {
        $this->validateRequestType($request);

        $this->authoriseUserAction('view', self::$model::find($id));

        $this->getUriParser($request);

        $this->parseIncludeParams();
        $fields = $this->parseFieldParams();

        $this->qualifyItemQuery();

        try {
            $item = $this->repository->getById($id, $fields);
        } catch (\Exception $exception) {
            return $this->errorNotFound('Record not found');
        }

        return $this->respondWithOne($item);
    }

    /**
     * Update the specified resource in storage.
     * PUT /api/{resource}/{id}.
     *
     * @param int $id
     * @param \Illuminate\Http\Request|\Illuminate\Foundation\Http\FormRequest $request
     */
    public function handleUpdateAction($id, $request)
    {
        $this->validateRequestType($request);

        $this->authoriseUserAction('update', self::$model::find($id));

        $this->validate($request, $this->rulesForUpdate($id));

        $data = $request->all();

        if (empty($data)) {
            return $this->errorWrongArgs('Empty request');
        }

        try {
            $item = $this->repository->getById($id);
        } catch (ModelNotFoundException $exception) {
            return $this->errorNotFound('Record does not exist');
        }

        $this->validate($request, $this->rulesForUpdate($item->id));

        $data = $this->qualifyUpdateQuery($data);

        $columns = $this->getTableColumns();

        $updates = array_intersect_key($data, array_flip($columns));

        $diff = array_diff(array_keys($data), array_keys($updates));

        $this->unguardIfNeeded();

        DB::beginTransaction();

        try {
            $item->fill($updates);
            $item->save();

            $this->storeRelated($item, $diff, $data);

            event(new Updated($item, $request));

            DB::commit();

            return $this->respondWithOne($this->repository->getById($item->id));
        } catch (\Illuminate\Database\QueryException $exception) {
            $message = config('app.debug') ? $exception->getMessage() : 'Failed to update Record';

            throw new ApiException($message);
        } catch (\Exception $exception) {
            DB::rollback();

            return $this->errorWrongArgs($exception->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/{resource}/{id}.
     *
     * @param int $id
     * @param \Illuminate\Http\Request|\Illuminate\Foundation\Http\FormRequest $request
     */
    public function handleDestroyAction($id, $request)
    {
        $this->validateRequestType($request);

        $this->authoriseUserAction('delete', self::$model::find($id));

        try {
            $item = $this->repository->getById($id);
            $this->repository->deleteById($id);
            event(new Deleted($item, $request));
        } catch (ModelNotFoundException $exeption) {
            return $this->errorNotFound('Record does not exist');
        }

        return $this->respondNoContent();
    }
}
