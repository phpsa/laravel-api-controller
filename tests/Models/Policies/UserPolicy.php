<?php

namespace Phpsa\LaravelApiController\Tests\Models\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Phpsa\LaravelApiController\Tests\Models\User;

class UserPolicy
{

    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $authed
     * @return mixed
     */
    public function viewAny(User $authed)
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $authed
     * @param  \App\Models\Project  $project
     * @return mixed
     */
    public function view(User $authed, User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $authed
     * @return mixed
     */
    public function create(User $authed)
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $authed
     * @param  \App\Models\Project  $project
     * @return mixed
     */
    public function update(User $authed, User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $authed
     * @param  \App\Models\Project  $project
     * @return mixed
     */
    public function delete(User $authed, User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $authed
     * @param  \App\Models\Project  $project
     * @return mixed
     */
    public function restore(User $authed, User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $authed
     * @param  \App\Models\Project  $project
     * @return mixed
     */
    public function forceDelete(User $authed, User $user)
    {
        return true;
    }

    /**
     * Add query to the index (getall) endpoint for this endpoint call.
     *
     * @param \App\Model\User $authed
     * @param mixed $builder
     *
     * @return void
     */
    public function qualifyCollectionQueryWithUser(User $authed, $builder): void
    {
        //
    }

    /**
     * Add query to the show (get) endpoint for this endpoint call.
     *
     * @param \App\Model\User $authed
     * @param mixed $builder
     *
     * @return void
     */
    public function qualifyItemQueryWithUser(User $authed, $builder): void
    {
        //
    }

    /**
     * allows you to manipulate the data for the store endpoint
     *
     * @param \App\Model\User $authed
     * @param array $data
     *
     * @return array
     */
    public function qualifyStoreDataWithUser(User $authed, array $data): array
    {
        return $data;
    }

    /**
     * allows you to manipulate the data for the update endpoint
     *
     * @param \App\Model\User $authed
     * @param array $data
     *
     * @return array
     */
    public function qualifyUpdateDataWithUser(User $authed, array $data): array
    {
        return $data;
    }
}
