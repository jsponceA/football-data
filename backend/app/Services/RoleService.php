<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleService
{
    public const PROTECTED_ROLE = 'admin';

    private const GUARD = 'api';

    public function paginate(int $perPage): LengthAwarePaginator
    {
        return Role::where('guard_name', self::GUARD)
            ->with('permissions')
            ->withCount('users')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function paginatePermissions(int $perPage): LengthAwarePaginator
    {
        return Permission::where('guard_name', self::GUARD)->orderBy('name')->paginate($perPage);
    }

    public function detail(Role $role): Role
    {
        return $role->load('permissions')->loadCount('users');
    }

    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create(['name' => $data['name'], 'guard_name' => self::GUARD]);
            $role->syncPermissions($data['permissions']);

            return $this->detail($role);
        });
    }

    public function update(Role $role, array $data): Role
    {
        $this->ensureNotProtected($role, 'No se puede modificar el rol admin.');

        return DB::transaction(function () use ($role, $data) {
            $role->update(['name' => $data['name']]);
            $role->syncPermissions($data['permissions']);

            return $this->detail($role);
        });
    }

    public function delete(Role $role): void
    {
        $this->ensureNotProtected($role, 'No se puede eliminar el rol admin.');

        if ($role->users()->exists()) {
            throw ValidationException::withMessages(['role' => 'No se puede eliminar un rol que tiene usuarios asignados.']);
        }

        $role->delete();
    }

    // si admin perdiera permisos nadie podría volver a administrar el sistema
    private function ensureNotProtected(Role $role, string $message): void
    {
        if ($role->name === self::PROTECTED_ROLE) {
            throw ValidationException::withMessages(['role' => $message]);
        }
    }
}
