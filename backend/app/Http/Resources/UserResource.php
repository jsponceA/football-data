<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $permissions = $this->getAllPermissions()->pluck('name');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'photo_url' => $this->photo_url,
            'roles' => $this->getRoleNames(),
            'permissions' => $permissions,
            // formato CASL: "teams.view" -> view sobre teams
            'abilities' => $permissions->map(function (string $name) {
                [$subject, $action] = explode('.', $name, 2);

                return ['action' => $action, 'subject' => $subject];
            })->values(),
        ];
    }
}
