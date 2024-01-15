<?php

namespace Phpsa\LaravelApiController\Http\Api\Contracts;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Phpsa\LaravelApiController\Events\Created;
use Phpsa\LaravelApiController\Events\Updated;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Phpsa\LaravelApiController\Exceptions\ApiException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Phpsa\LaravelApiController\Http\Resources\ApiResource;
use Illuminate\Http\JsonResponse;

trait HasBatchActions
{

    protected function getBatchKey(): string
    {
        return isset($this->batchKey) ? $this->batchKey : 'data';
    }

    public function getBatchData(): Collection
    {
        return collect($this->getRequestArray()[$this->getBatchKey()] ?? []);
    }

    public function handleBatchStoreAction(?Request $request, array $extraParams = []): JsonResponse
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

    public function handleBatchUpdateAction(?Request $request, array $extraParams = []): JsonResponse
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


    public function handleBatchStoreOrUpdateAction(?Request $request = null, array $extraParams = []): JsonResponse
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


    protected function handleBatchStoreResponse(Collection $items): JsonResponse
    {
        return $this->respondWithResource($this->getResourceCollection(), $items, 201);
    }

    protected function handleBatchUpdateResponse(Collection $items): JsonResponse
    {
        return $this->respondWithResource($this->getResourceCollection(), $items, 200);
    }

    protected function handleBatchStoreOrUpdateResponse(Collection $items): JsonResponse
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
