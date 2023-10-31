<?php

namespace Phpsa\LaravelApiController\Http\Api;

use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Phpsa\LaravelApiController\Exceptions\ApiException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Phpsa\LaravelApiController\Http\Api\Contracts\HasModel;
use Phpsa\LaravelApiController\Http\Api\Contracts\HasRequest;
use Phpsa\LaravelApiController\Http\Api\Contracts\HasEvents;
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
    use HasEvents;
    use HasRequest;

    /**
     * Set the default sorting for queries.
     *
     * @var string
     */
    protected $defaultSort = null;

    /**
     * Number of items displayed at once if not specified.
     * There is no limit if it is 0 or false.
     * null defaults to the model limit parameters
     *
     * @var ?int
     */
    protected $defaultLimit = null;

    /**
     * Maximum limit that can be set via $_GET['limit'].
     *
     * @var int
     */
    protected $maximumLimit = 0;

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

        $items = $limit > 0 ? $this->getBuilder()->paginate($limit, $fields)->appends($this->originalQueryParams) : $this->getBuilder()->get($fields);

        return $this->handleIndexResponse($items);
    }

    public function handleIndexActionRaw($request = null, array $extraParams = [])
    {
        $this->handleIndexActionCommon($request, $extraParams);
        $fields = $this->parseFieldParams();
        $limit = $this->parseLimitParams();

        $items = $limit > 0 ? $this->getBuilder()->paginateRaw($limit, $fields)->appends($this->originalQueryParams) : $this->getBuilder()->getRaw($fields);

        return $this->handleIndexResponse($items);
    }

    protected function handleIndexActionCommon($request = null, array $extraParams = [])
    {
        $this->validateRequestType($request);
        $this->addCustomParams($extraParams);
        $this->authoriseUserAction('viewAny');
        $this->handleCommonActions($this->request);
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
        $key = $this->getModel()->getKeyName();
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

        $this->validate($this->request, $this->rulesForCreate());

        $data = $this->qualifyStoreQuery($this->getRequestArray());

        $insert = $this->addTableData($data);

        $diff = array_diff(array_keys($data), array_keys($insert));

        $this->unguardIfNeeded();

        DB::beginTransaction();

        try {
            $item = $this->getModel()->create($insert);

            $this->storeRelated($item, $diff, $data);

            $this->triggerCreatedEvent($item);

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
     * @param \Illuminate\Database\Eloquent\Model|int|string $id Model id / model instance for the record
     * @param \Illuminate\Http\Request|\Illuminate\Foundation\Http\FormRequest|null $request
     */
    public function handleShowAction($id, $request = null, array $extraParams = [])
    {
        $this->validateRequestType($request);
        $this->addCustomParams($extraParams);

        $this->handleCommonActions($this->request);
        $fields = $this->parseFieldParams();
        $this->qualifyItemQuery();

        try {
            $item = $this->resolveRouteBinding($id)->firstOrFail($fields);

            $this->authoriseUserAction('view', $item);
        } catch (ModelNotFoundException $exception) {
            $this->errorNotFound('Record not found');
        }

        return $this->handleShowResponse($item);
    }

    /**
     * Update the specified resource in storage.
     * PUT /api/{resource}/{id}.
     *
     * @param \Illuminate\Database\Eloquent\Model|int|string $id Model id / model instance for the record                                                            $id
     * @param \Illuminate\Http\Request|\Illuminate\Foundation\Http\FormRequest $request
     */
    public function handleUpdateAction($id, $request, array $extraParams = [])
    {
        $this->validateRequestType($request);
        $this->addCustomParams($extraParams);

        $this->handleCommonActions($this->request);

        try {
            $item = $this->resolveRouteBinding($id)->firstOrFail();
            $this->authoriseUserAction('update', $item);
        } catch (ModelNotFoundException $exception) {
            return $this->errorNotFound('Record does not exist');
        }

        $this->validate($this->request, $this->rulesForUpdate($item->getKey()));

        $data = $this->qualifyUpdateQuery($this->getRequestArray());

        $updates = $this->addTableData($data);

        $diff = array_diff(array_keys($data), array_keys($updates));

        DB::beginTransaction();

        $this->unguardIfNeeded();

        try {
            $item->fill($updates);
            $item->save();

            $this->storeRelated($item, $diff, $data);

            $this->triggerUpdatedEvent($item);

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
     * @param \Illuminate\Database\Eloquent\Model|int|string $id Model id / model instance for the record                                                             $id
     * @param \Illuminate\Http\Request|\Illuminate\Foundation\Http\FormRequest|null $request
     */
    public function handleDestroyAction($id, $request = null)
    {
        $this->handleCommonActions($request);
        $this->qualifyItemQuery();

        try {
            $item = $this->resolveRouteBinding($id)->firstOrFail();
            $this->authoriseUserAction('delete', $item);
            $item->delete();
            $this->triggerDeletedEvent($item);
        } catch (ModelNotFoundException $exception) {
            return $this->errorNotFound('Record not found');
        }

        return $this->handleDestroyResponse($id);
    }

    /**
     * Remove the specified resource from storage.
     * PATCH /api/{resource}/{id}.
     *
     * @param \Illuminate\Database\Eloquent\Model|int|string $id Model id / model instance for the record                                                             $id
     * @param \Illuminate\Http\Request|\Illuminate\Foundation\Http\FormRequest|null $request
     */
    public function handleRestoreAction($id, $request = null)
    {
        $this->handleCommonActions($request);
        $this->qualifyItemQuery();

        try {
            //@phpstan-ignore-next-line
            $item = $this->resolveRouteBinding($id)->onlyTrashed()->firstOrFail();
            $this->authoriseUserAction('restore', $item);
            $item->restore();
            $this->triggerRestoredEvent($item);
        } catch (ModelNotFoundException $exception) {
            return $this->errorNotFound('Record not found');
        }

        return $this->handleRestoreResponse($item);
    }
}
