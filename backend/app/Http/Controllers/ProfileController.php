<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UserPhotoRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\Request;

// el perfil de quien tiene la sesión abierta; no pide permisos porque cada quien edita lo suyo
class ProfileController extends Controller
{
    public function __construct(private UserService $users)
    {
    }

    public function update(UpdateProfileRequest $request): UserResource
    {
        return UserResource::make($this->users->updateProfile($request->user(), $request->validated()));
    }

    public function updatePhoto(UserPhotoRequest $request): UserResource
    {
        return UserResource::make($this->users->replacePhoto($request->user(), $request->file('photo')));
    }

    public function destroyPhoto(Request $request): UserResource
    {
        return UserResource::make($this->users->removePhoto($request->user()));
    }
}
