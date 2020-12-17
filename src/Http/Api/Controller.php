<?php

namespace Phpsa\LaravelApiController\Http\Api;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Phpsa\LaravelApiController\Contracts\Parser;
use Phpsa\LaravelApiController\Contracts\Relationships;
use Phpsa\LaravelApiController\Events\Created;
use Phpsa\LaravelApiController\Events\Updated;
use Phpsa\LaravelApiController\Exceptions\ApiException;
use Phpsa\LaravelApiController\Http\Api\Contracts\HasModel;
use Phpsa\LaravelApiController\Http\Api\Contracts\HasPolicies;
use Phpsa\LaravelApiController\Http\Api\Contracts\HasRepository;
use Phpsa\LaravelApiController\Http\Api\Contracts\HasResources;
use Phpsa\LaravelApiController\Http\Api\Contracts\HasResponse;
use Phpsa\LaravelApiController\Http\Api\Contracts\HasValidation;

/**
 * Class Controller.
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;
    use HasModel;
    use HasPolicies;
    use HasRepository;
    use HasResources;
    use HasResponse;
    use HasValidation;
    use Parser;
    use Relationships;

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
        $this->handleIndexActionCommon($request, $extraParams);
        $fields = $this->parseFieldParams();
        $limit = $this->parseLimitParams();

        $items = $limit > 0 ? $this->repository->paginate($limit, $fields)->appends($this->originalQueryParams) : $this->repository->get($fields);

        return $this->handleIndexResponse($items);
    }

    public function handleIndexActionRaw($request, array $extraParams = [])
    {
        $this->handleIndexActionCommon($request, $extraParams);
        $fields = $this->parseFieldParams();
        $limit = $this->parseLimitParams();

        $items = $limit > 0 ? $this->repository->paginateRaw($limit, $fields)->appends($this->originalQueryParams) : $this->repository->getRaw($fields);

        return $this->handleIndexResponse($items);
    }

    protected function handleIndexActionCommon($request, array $extraParams = [])
    {
        $this->addCustomParams($request, $extraParams);
        $this->validateRequestType($request);
        $this->authoriseUserAction('viewAny');
        $this->handleCommonActions($request);
        $this->qualifyCollectionQuery();
    }

    protected function handleCommonActions($request)
    {
        $this->getUriParser($request);
        $this->parseIncludeParams();
        $this->parseSortParams();
        $this->parseFilterParams();
        $this->parseAllowedScopes($request);
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

            return $this->handleStoreResponse($item);
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

        $this->handleCommonActions($request);
        $fields = $this->parseFieldParams();
        $this->qualifyItemQuery();

        try {
            $item = $this->repository->find($id, $fields);
            $this->authoriseUserAction('view', $item);
        } catch (ModelNotFoundException $exception) {
            return $this->errorNotFound('Record not found');
        }

        return $this->handleShowResponse($item);
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

        $this->handleCommonActions($request);

        try {
            $item = $this->repository->find($id);
            $this->authoriseUserAction('update', $item);
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

            return $this->handleUpdateResponse($item);
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

        $this->handleCommonActions($request);
        $this->qualifyItemQuery();

        try {
            $item = $this->repository->find($id);
            $this->authoriseUserAction('delete', $item);
            $item->delete();
        } catch (ModelNotFoundException $exception) {
            return $this->errorNotFound('Record not found');
        }

        return $this->handleDestroyResponse($id);
    }
}
