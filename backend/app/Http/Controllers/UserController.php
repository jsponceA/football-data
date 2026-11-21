<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UserIndexRequest;
use App\Http\Requests\UserPhotoRequest;
use App\Http\Requests\UserStatusRequest;
use App\Http\Resources\ManagedUserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class UserController extends Controller
{
    public function __construct(private UserService $users)
    {
    }

    public function index(UserIndexRequest $request): AnonymousResourceCollection
    {
        return ManagedUserResource::collection(
            $this->users->paginate($request->validated(), $this->perPage($request)),
        );
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        return ManagedUserResource::make($this->users->create($request->validated()))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(User $user): ManagedUserResource
    {
        return ManagedUserResource::make($user->load('roles'));
    }

    public function update(UpdateUserRequest $request, User $user): ManagedUserResource
    {
        return ManagedUserResource::make(
            $this->users->update($user, $request->validated(), $request->user()),
        );
    }

    public function updateStatus(UserStatusRequest $request, User $user): ManagedUserResource
    {
        return ManagedUserResource::make(
            $this->users->setActive($user, $request->boolean('is_active'), $request->user()),
        );
    }

    // aparte del PUT: el JSON de la edición no puede llevar archivos
    public function updatePhoto(UserPhotoRequest $request, User $user): ManagedUserResource
    {
        return ManagedUserResource::make($this->users->replacePhoto($user, $request->file('photo')));
    }

    public function destroyPhoto(User $user): ManagedUserResource
    {
        return ManagedUserResource::make($this->users->removePhoto($user));
    }
}
