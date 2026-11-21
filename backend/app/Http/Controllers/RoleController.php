<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaginationRequest;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(private RoleService $roles)
    {
    }

    public function index(PaginationRequest $request): AnonymousResourceCollection
    {
        return RoleResource::collection($this->roles->paginate($this->perPage($request)));
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        return RoleResource::make($this->roles->create($request->validated()))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Role $role): RoleResource
    {
        return RoleResource::make($this->roles->detail($role));
    }

    public function update(UpdateRoleRequest $request, Role $role): RoleResource
    {
        return RoleResource::make($this->roles->update($role, $request->validated()));
    }

    public function destroy(Role $role): Response
    {
        $this->roles->delete($role);

        return response()->noContent();
    }
}
