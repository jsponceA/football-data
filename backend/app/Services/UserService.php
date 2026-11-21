<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return User::query()
            ->with('roles')
            ->when($filters['search'] ?? null, fn (Builder $q, string $search) => $q->where(
                fn (Builder $w) => $w->whereLike('name', "%{$search}%")->orWhereLike('email', "%{$search}%"),
            ))
            ->when($filters['role'] ?? null, fn (Builder $q, string $role) => $q->whereHas(
                'roles', fn (Builder $r) => $r->where('name', $role),
            ))
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'is_active' => $data['is_active'] ?? true,
            ]);

            $user->syncRoles($data['roles']);

            return $user->load('roles');
        });
    }

    public function update(User $user, array $data, User $actor): User
    {
        // un administrador no puede quedarse fuera de su propio sistema
        if ($user->is($actor)) {
            $this->ensureNotLockingOut($user, $data);
        }

        return DB::transaction(function () use ($user, $data) {
            $wasActive = $user->is_active;

            $user->fill([
                'name' => $data['name'],
                'email' => $data['email'],
                'is_active' => $data['is_active'],
            ]);

            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }

            $user->save();
            $user->syncRoles($data['roles']);

            // al desactivarlo se cierran todas sus sesiones
            if ($wasActive && ! $user->is_active) {
                $user->tokens()->get()->each->revoke();
            }

            return $user->load('roles');
        });
    }

    private function ensureNotLockingOut(User $user, array $data): void
    {
        if (! $data['is_active']) {
            throw ValidationException::withMessages(['is_active' => 'No puedes desactivar tu propio usuario.']);
        }

        $current = $user->getRoleNames()->sort()->values()->all();
        $requested = collect($data['roles'])->sort()->values()->all();

        if ($current !== $requested) {
            throw ValidationException::withMessages(['roles' => 'No puedes cambiar tus propios roles.']);
        }
    }
}
