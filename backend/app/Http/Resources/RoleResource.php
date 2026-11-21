<?php

namespace App\Http\Resources;

use App\Services\RoleService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Spatie\Permission\Models\Role */
class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            // el front lo usa para no ofrecer editar ni borrar el rol admin
            'protected' => $this->name === RoleService::PROTECTED_ROLE,
            'permissions' => $this->whenLoaded('permissions', fn () => $this->permissions->pluck('name')->sort()->values()),
            'users_count' => $this->whenCounted('users'),
        ];
    }
}
