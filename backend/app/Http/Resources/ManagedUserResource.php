<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// usuario tal como lo ve el módulo de administración (el de /me trae además los permisos)
/** @mixin \App\Models\User */
class ManagedUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'roles' => $this->roles->pluck('name'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
