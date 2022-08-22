<?php

namespace Phpsa\LaravelApiController\Tests\Controllers;

use Phpsa\LaravelApiController\Http\Api\Controller;
use Phpsa\LaravelApiController\Tests\Models\UserProfile;
use Phpsa\LaravelApiController\Tests\Requests\UserProfileRequest;
use Phpsa\LaravelApiController\Tests\Resources\UserProfileResource;
use Phpsa\LaravelApiController\Tests\Resources\UserProfileResourceCollection;

class UserProfileController extends Controller
{
    protected $resourceSingle = UserProfileResource::class;
    protected $resourceCollection = UserProfileResourceCollection::class;

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
     * @param  \Phpsa\LaravelApiController\Tests\Requests\UserProfileRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserProfileRequest $request)
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
     * @param  \Phpsa\LaravelApiController\Tests\Requests\UserProfileRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UserProfileRequest $request, $id)
    {
        return $this->handleUpdateAction($id, $request);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        return $this->handleDestroyAction($id);
    }

    /**
     * Eloquent model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function model()
    {
        return UserProfile::class;
    }
}
