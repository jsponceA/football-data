<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaginationRequest;
use App\Http\Resources\PermissionResource;
use App\Services\RoleService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PermissionController extends Controller
{
    public function __construct(private RoleService $roles)
    {
    }

    public function index(PaginationRequest $request): AnonymousResourceCollection
    {
        return PermissionResource::collection($this->roles->paginatePermissions($this->perPage($request)));
    }
}
