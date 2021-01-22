<?php

namespace Phpsa\LaravelApiController\Http\Api;

use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Phpsa\LaravelApiController\Events\Created;
use Phpsa\LaravelApiController\Events\Updated;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Phpsa\LaravelApiController\Exceptions\ApiException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Phpsa\LaravelApiController\Http\Api\Contracts\HasModel;
use Phpsa\LaravelApiController\Http\Api\Contracts\HasParser;
use Phpsa\LaravelApiController\Http\Api\Contracts\HasIncludes;
use Phpsa\LaravelApiController\Http\Api\Contracts\HasPolicies;
use Phpsa\LaravelApiController\Http\Api\Contracts\HasResponse;
use Phpsa\LaravelApiController\Http\Api\Contracts\HasResources;
use Phpsa\LaravelApiController\Http\Api\Contracts\HasValidation;
use Phpsa\LaravelApiController\Http\Api\Contracts\HasRelationships;

abstract class Controller extends BaseController
{
    //Laravel Specific Items
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    //Api=Controller extended traits
    use HasModel;
    use HasPolicies;
    use HasResources;
    use HasResponse;
    use HasValidation;
    use HasParser;
    use HasRelationships;
    use HasIncludes;

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
     */
    public function __construct()
    {
        $this->makeModel();
    }

    /**
     * Display a listing of the resource.
     * GET /api/{resource}.
     *
     * @param \Illuminate\Http\Request|\Illuminate\Foundation\Http\FormRequest|null $request
     */
    public function handleIndexAction($request = null, array $extraParams = [])
    {
        $this->handleIndexActionCommon($request, $extraParams);
        $fields = $this->parseFieldParams();
        $limit = $this->parseLimitParams();

        $items = $limit > 0 ? $this->builder->paginate($limit, $fields)->appends($this->originalQueryParams) : $this->builder->get($fields);

        return $this->handleIndexResponse($items);
    }

    public function handleIndexActionRaw($request = null, array $extraParams = [])
    {
        $this->handleIndexActionCommon($request, $extraParams);
        $fields = $this->parseFieldParams();
        $limit = $this->parseLimitParams();

        $items = $limit > 0 ? $this->builder->paginateRaw($limit, $fields)->appends($this->originalQueryParams) : $this->builder->getRaw($fields);

        return $this->handleIndexResponse($items);
    }

    protected function handleIndexActionCommon($request = null, array $extraParams = [])
    {
        $this->validateRequestType($request);
        $this->addCustomParams($extraParams);
        $this->authoriseUserAction('viewAny');
        $this->handleCommonActions($request);
        $this->qualifyCollectionQuery();
    }

    protected function handleCommonActions($request = null)
    {
        $this->validateRequestType($request);
        $this->getUriParser();
        $this->parseIncludeParams();
        $this->parseSortParams();
        $this->parseFilterParams();
        $this->parseAllowedScopes();
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
        $this->validateRequestType($request);
        $this->addCustomParams($extraParams);
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
     * @param \Illuminate\Http\Request|\Illuminate\Foundation\Http\FormRequest|null $request
     */
    public function handleShowAction($id, $request = null, array $extraParams = [])
    {
        $this->validateRequestType($request);
        $this->addCustomParams($extraParams);

        $this->handleCommonActions($request);
        $fields = $this->parseFieldParams();
        $this->qualifyItemQuery();

        try {
            $item = $this->builder->find($id, $fields);
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
        $this->validateRequestType($request);
        $this->addCustomParams($extraParams);

        $this->handleCommonActions($request);

        try {
            $item = $this->builder->find($id);
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
     * @param \Illuminate\Http\Request|\Illuminate\Foundation\Http\FormRequest|null $request
     */
    public function handleDestroyAction($id, $request = null)
    {
        $this->handleCommonActions($request);
        $this->qualifyItemQuery();

        try {
            $item = $this->builder->find($id);
            $this->authoriseUserAction('delete', $item);
            $item->delete();
        } catch (ModelNotFoundException $exception) {
            return $this->errorNotFound('Record not found');
        }

        return $this->handleDestroyResponse($id);
    }
}
