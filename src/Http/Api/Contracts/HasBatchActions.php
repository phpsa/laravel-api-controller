<?php
namespace Phpsa\LaravelApiController\Http\Api\Contracts;

use Illuminate\Support\Facades\DB;
use Phpsa\LaravelApiController\Exceptions\ApiException;

trait HasBatchActions
{

    public function handleBatchStoreAction($request, array $extraParams)
    {
        $this->validateRequestType($request);
        $this->addCustomParams($extraParams);
        $this->authoriseUserAction('create');

        $this->validate($this->request, $this->rulesForBatchCreate());

        DB::beginTransaction();

        $this->unguardIfNeeded();

        try {
            $records = collect($this->request->get('data'), [])->map(function ($item) {
                $data = $this->qualifyStoreQuery($item);
                $insert = $this->addTableData($data);
                $diff = array_diff(array_keys($data), array_keys($insert));
                $item = self::$model->create($insert);

                $this->storeRelated($item, $diff, $data);

                return $item;
            });

            DB::commit();

            return $this->handleStoreResponse($records);
        } catch (\Exception $exception) {
            $message = config('app.debug') ? $exception->getMessage() : 'Failed to create Records';

            DB::rollback();
            throw new ApiException($message, (int) $exception->getCode(), $exception);
        }
    }


    protected function handleBatchStoreResponse($items)
    {
        return $this->respondWithResource($this->/** @scrutinizer ignore-call */getResourceCollection(), $items, 201);
    }

    protected function handleBatchUpdateResponse($items)
    {
        return $this->respondWithResource($this->/** @scrutinizer ignore-call */getResourceCollection(), $items, 200);
    }

    protected function rulesForBatchCreate() : array
    {
        return [];
    }

    protected function rulesForBatchUpdate() : array
    {
        return [];
    }
}
