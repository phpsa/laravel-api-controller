<?php

namespace Phpsa\LaravelApiController\Http\Api\Contracts;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Phpsa\LaravelApiController\Events\Created;
use Phpsa\LaravelApiController\Events\Updated;
use Phpsa\LaravelApiController\Exceptions\ApiException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait HasBatchActions
{

    public function getBatchData(): Collection
    {
        return collect($this->getRequestArray()['data'] ?? []);
    }

    /**
     * @param \Illuminate\Http\Request|\Illuminate\Foundation\Http\FormRequest $request
     * @param array $extraParams
     *
     * @return mixed
     */
    public function handleBatchStoreAction($request, array $extraParams = [])
    {
        $this->validateRequestType($request);
        $this->addCustomParams($extraParams);

        $this->authoriseUserAction('create');

        $this->validate($this->request, $this->rulesForBatchCreate());

        DB::beginTransaction();

        $this->unguardIfNeeded();

        try {
            $records = $this->processStoreQuery(collect($this->getBatchData()));

            DB::commit();

            return $this->handleBatchStoreResponse($records);
        } catch (\Exception $exception) {
            $message = config('app.debug') ? $exception->getMessage() : 'Failed to create Records';

            DB::rollback();
            throw new ApiException($message, (int) $exception->getCode(), $exception);
        }
    }

    protected function processStoreQuery(Collection $items): Collection
    {
        return $items->map(function ($item) {
            $data = $this->qualifyStoreQuery($item);
            $insert = $this->addTableData($data);
            $diff = array_diff(array_keys($data), array_keys($insert));
            $item = $this->getModel()->create($insert);

            $this->storeRelated($item, $diff, $data);

            event(new Created($item, $this->request));

            return $item;
        });
    }

    /**
     * @param \Illuminate\Http\Request|\Illuminate\Foundation\Http\FormRequest $request
     *
     * @return mixed
     */
    public function handleBatchUpdateAction($request, array $extraParams = [])
    {
        $this->validateRequestType($request);
        $this->addCustomParams($extraParams);

        $this->validate($this->request, $this->rulesForBatchUpdate());

        DB::beginTransaction();

        $this->unguardIfNeeded();

        try {
            $records = $this->processUpdateQuery(collect($this->getBatchData()));

            DB::commit();

            return $this->handleBatchUpdateResponse($records);
        } catch (\Exception $exception) {
            $message = config('app.debug') ? $exception->getMessage() : 'Failed to create Records';

            DB::rollback();
            match (get_class($exception)) {
                ModelNotFoundException::class => throw $exception,
                default => throw new ApiException($message, (int) $exception->getCode(), $exception)
            };
        }
    }

    protected function processUpdateQuery(Collection $items): Collection
    {
        return $items->map(function ($item) {

            $key = $this->getModel()->getKeyName();

            $id = $item[$key];
            $existing = $this->getNewQuery()->where($key, $id)->firstOrFail();
            $this->authoriseUserAction('update', $existing);

            $data = $this->qualifyUpdateQuery($item);

            $updates = $this->addTableData($data);

            $diff = array_diff(array_keys($data), array_keys($updates));

            $existing->fill($updates);
            $existing->save();

            $this->storeRelated($existing, $diff, $data);

            event(new Updated($existing, $this->request));

            return $existing;
        });
    }


      /**
     * @param \Illuminate\Http\Request|\Illuminate\Foundation\Http\FormRequest $request
     *
     * @return mixed
     */
    public function handleBatchStoreOrUpdateAction($request, array $extraParams = [])
    {
        $this->validateRequestType($request);
        $this->addCustomParams($extraParams);

        $key = $this->getModel()->getKeyName();
        $records = $this->getBatchData();

        DB::beginTransaction();

        $this->unguardIfNeeded();

        try {
            $existing = $this->processUpdateQuery($records->filter(function ($record) use ($key) {
                return ! isset($record[$key]) ||  empty($record[$key]);
            }));

            $new = $this->processStoreQuery($records->filter(function ($record) use ($key) {
                return isset($record[$key]) && ! empty($record[$key]);
            }));

            DB::commit();

            return $this->handleBatchStoreOrUpdateResponse($existing->merge($new));
        } catch (\Exception $exception) {
            $message = config('app.debug') ? $exception->getMessage() : 'Failed to create Records';

            DB::rollback();
            match (get_class($exception)) {
                ModelNotFoundException::class => throw $exception,
                default => throw new ApiException($message, (int) $exception->getCode(), $exception)
            };
        }
    }



    /**
     * @return mixed Response|jsonResponse
     */
    protected function handleBatchStoreResponse(Collection $items)
    {
        return $this->respondWithResource($this->/** @scrutinizer ignore-call */getResourceCollection(), $items, 201);
    }

    /**
     * @return mixed Response|jsonResponse
     */
    protected function handleBatchUpdateResponse(Collection $items)
    {
        return $this->respondWithResource($this->/** @scrutinizer ignore-call */getResourceCollection(), $items, 200);
    }

    /**
     * @return mixed Response|jsonResponse
     */
    protected function handleBatchStoreOrUpdateResponse(Collection $items)
    {
        return $this->respondWithResource($this->getResourceCollection(), $items, 200);
    }

    /**
     * @deprecated use your FormRequest instead
     */
    protected function rulesForBatchCreate(): array
    {
        return [];
    }

    /**
     * @deprecated use your FormRequest instead
     */
    protected function rulesForBatchUpdate(): array
    {
        return [];
    }
}
