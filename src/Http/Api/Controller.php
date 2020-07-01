<?php

namespace Phpsa\LaravelApiController\Http\Api;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Phpsa\LaravelApiController\Http\Api\Contracts\Parser;
use Phpsa\LaravelApiController\Http\Api\Contracts\Policies;
use Phpsa\LaravelApiController\Http\Api\Contracts\Relationships;
use Phpsa\LaravelApiController\Http\Api\Contracts\HasResponse;
use Phpsa\LaravelApiController\Http\Api\Contracts\Validation;
use Phpsa\LaravelApiController\Events\Created;
use Phpsa\LaravelApiController\Events\Deleted;
use Phpsa\LaravelApiController\Events\Updated;
use Phpsa\LaravelApiController\Exceptions\ApiException;
use Phpsa\LaravelApiController\Http\Api\Contracts\HasModel;
use Phpsa\LaravelApiController\Http\Api\Contracts\HasRepository;
use Phpsa\LaravelApiController\Http\Api\Contracts\HasResources;

/**
 * Class Controller.
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;
    use HasModel;
    use HasRepository;
    use HasResources;
    use HasResponse;
    use Parser;
    use Relationships;
    use Policies;
    use Validation;

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
    public function handleIndexAction($request, array $extraParams = [])
    {
        $this->addCustomParams($request, $extraParams);
        $this->validateRequestType($request);
        $this->authoriseUserAction('viewAny');
        $this->getUriParser($request);

        $this->parseIncludeParams();
        $this->parseSortParams();
        $this->parseFilterParams();
        $this->parseMethodParams($request);
        $fields = $this->parseFieldParams();
        $limit = $this->parseLimitParams();

        $this->qualifyCollectionQuery();

        $items = $limit > 0 ? $this->repository->paginate($limit, $fields) : $this->repository->get($fields);

        return $this->respondWithMany($items);
    }

    public function handleStoreOrUpdateAction($request, array $extraParams = [])
    {
        $key = self::$model->getKeyName();
        $id = $request->input($key, null) ?? data_get($extraParams, $key, null);

        return $id ? $this->handleUpdateAction($id, $request, $extraParams) : $this->handleStoreAction($request, $extraParams);
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/{resource}.
     *
     * @param \Illuminate\Http\Request|\Illuminate\Foundation\Http\FormRequest $request
     */
    public function handleStoreAction($request, array $extraParams = [])
    {
        $this->addCustomParams($request, $extraParams);
        $this->validateRequestType($request);
        $this->authoriseUserAction('create');

        $this->validate($request, $this->rulesForCreate());

        $data = $this->qualifyStoreQuery($request->all());

        $insert = $this->addTableData($data);

        $diff = array_diff(array_keys($data), array_keys($insert));

        $this->unguardIfNeeded();

        DB::beginTransaction();

        try {
            $item = self::$model->create($insert);

            $this->storeRelated($item, $diff, $data);

            event(new Created($item, $request));

            DB::commit();

            return $this->respondItemCreated($this->repository->getById($item->getKey()));
        } catch (\Exception $exception) {
            $message = config('app.debug') ? $exception->getMessage() : 'Failed to create Record';

            DB::rollback();
            throw new ApiException($message, (int) $exception->getCode(), $exception);
        }
    }

    /**
     * Display the specified resource.
     * GET /api/{resource}/{id}.
     *
     * @param int                                                              $id
     * @param \Illuminate\Http\Request|\Illuminate\Foundation\Http\FormRequest $request
     */
    public function handleShowAction($id, $request, array $extraParams = [])
    {
        $this->addCustomParams($request, $extraParams);
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
     * @param int                                                              $id
     * @param \Illuminate\Http\Request|\Illuminate\Foundation\Http\FormRequest $request
     */
    public function handleUpdateAction($id, $request, array $extraParams = [])
    {
        $this->addCustomParams($request, $extraParams);
        $this->validateRequestType($request);

        $this->authoriseUserAction('update', self::$model::find($id));

        $this->validate($request, $this->rulesForUpdate($id));

        try {
            $item = $this->repository->getById($id);
        } catch (ModelNotFoundException $exception) {
            return $this->errorNotFound('Record does not exist');
        }

        $this->validate($request, $this->rulesForUpdate($item->getKey()));

        $data = $this->qualifyUpdateQuery($request->all());

        $updates = $this->addTableData($data);

        $diff = array_diff(array_keys($data), array_keys($updates));

        $this->unguardIfNeeded();

        DB::beginTransaction();

        try {
            $item->fill($updates);
            $item->save();

            $this->storeRelated($item, $diff, $data);

            event(new Updated($item, $request));

            DB::commit();

            return $this->respondWithOne($this->repository->getById($item->getKey()));
        } catch (\Exception $exception) {
            $message = config('app.debug') ? $exception->getMessage() : 'Failed to update Record';
            DB::rollback();

            throw new ApiException($message, (int) $exception->getCode(), $exception);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/{resource}/{id}.
     *
     * @param int                                                              $id
     * @param \Illuminate\Http\Request|\Illuminate\Foundation\Http\FormRequest $request
     */
    public function handleDestroyAction($id, $request)
    {
        $this->validateRequestType($request);
        try {
            $item = self::$model::findOrFail($id);
            $this->authoriseUserAction('delete', $item);
            $this->repository->deleteById($id);
            event(new Deleted($item, $request));
        } catch (ModelNotFoundException $exeption) {
            return $this->errorNotFound('Record does not exist');
        }

        return $this->respondNoContent();
    }
}
