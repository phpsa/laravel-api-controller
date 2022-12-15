<?php

namespace Phpsa\LaravelApiController\Tests\Controllers;

use Phpsa\LaravelApiController\Tests\Models\Task;
use Phpsa\LaravelApiController\Http\Api\Controller;
use Phpsa\LaravelApiController\Tests\Requests\UserRequest;
use Phpsa\LaravelApiController\Tests\Resources\TaskResource;
use Phpsa\LaravelApiController\Tests\Resources\TaskResourceCollection;

class TaskController extends Controller
{
    protected $resourceSingle = TaskResource::class;

    protected $resourceCollection = TaskResourceCollection::class;

    protected string $resourceModel = Task::class;

      /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return $this->handleIndexAction();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @see self::handleStoreOrUpdateAction to do magic insert / update
     * @param  App\Http\Requests\ProjectRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserRequest $request)
    {
        return $this->handleStoreAction($request);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return $this->handleShowAction($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  App\Http\Requests\ProjectRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UserRequest $request, $id)
    {
        return $this->handleUpdateAction($id, $request);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Task $task
     * @return \Illuminate\Http\Response
     */
    public function destroy($taskId)
    {
        return $this->handleDestroyAction($taskId);
    }


     /**
     * Remove the specified resource from storage.
     *
     * @param  Task $task
     * @return \Illuminate\Http\Response
     */
    public function restore($taskId)
    {
        return $this->handleRestoreAction($taskId);
    }
}
