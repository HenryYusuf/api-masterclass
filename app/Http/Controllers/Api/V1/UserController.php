<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Filters\V1\AuthorFilter;
use App\Http\Requests\Api\V1\ReplaceUserRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Http\Requests\Api\V1\StoreUserRequest;
use App\Http\Requests\Api\V1\UpdateUserRequest;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserController extends ApiController
{
    protected $policyClass = UserPolicy::class;

    /**
     * Display a listing of the resource.
     */
    public function index(AuthorFilter $filters)
    {
        return UserResource::collection(
            User::filters($filters)->paginate()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        try {
            // $user = User::findOrFail($request->input('data.relationships.author.data.id'));

            // policy
            $this->isAble('store', targetModel: User::class);

            // TODO: create ticket
            return new UserResource(User::create($request->mappedAttributes()));

        } catch (AuthorizationException $authorizationException) {
            return $this->error('You are not authorized to update this resource', 401);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        if ($this->include('tickets')) {
            return new UserResource($user->load('tickets'));
        }

        return new UserResource($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, $user_id)
    {
        // PATCH
        try {
            $user = User::findOrFail($user_id);

            // policy
            $this->isAble('update', $user);

            $user->update($request->mappedAttributes());

            return new UserResource($user);
        } catch (ModelNotFoundException $modelNotFoundException) {
            return $this->error('User not found', 404);
        } catch (AuthorizationException $authorizationException) {
            return $this->error('You are not authorized to update this resource', 401);
        }
    }

    public function replace(ReplaceUserRequest $request, $user_id)
    {
        // PUT
        try {
            $user = User::findOrFail($user_id);

            $this->isAble('replace', $user);

            $user->update($request->mappedAttributes());

            return new UserResource($user);
        } catch (ModelNotFoundException $exception) {
            return $this->error('User not found', 404);
        } catch (AuthorizationException $authorizationException) {
            return $this->error('You are not authorized to replace this resource', 401);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($user_id)
    {
        try {
            $user = User::findOrFail($user_id);

            // policy
            $this->isAble('delete', $user);

            $user->delete();

            return $this->ok('User successfully deleted');
        } catch (ModelNotFoundException $exception) {
            return $this->error('User not found', 404);
        }
    }
}
