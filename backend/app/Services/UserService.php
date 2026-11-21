<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UserService
{
    public const SELF_REGISTER_ROLE = 'solo-consulta';

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

    // quien se registra solo entra como consulta; un administrador puede darle más permisos después
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'is_active' => true,
            ]);

            $user->assignRole(self::SELF_REGISTER_ROLE);

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

            if ($wasActive && ! $user->is_active) {
                $this->revokeSessions($user);
            }

            return $user->load('roles');
        });
    }

    public function updateProfile(User $user, array $data): User
    {
        $user->fill(['name' => $data['name'], 'email' => $data['email']]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return $user->load('roles');
    }

    // activar o desactivar desde el listado, sin tocar el resto de los datos
    public function setActive(User $user, bool $active, User $actor): User
    {
        if (! $active && $user->is($actor)) {
            throw ValidationException::withMessages(['is_active' => 'No puedes desactivar tu propio usuario.']);
        }

        $wasActive = $user->is_active;
        $user->update(['is_active' => $active]);

        if ($wasActive && ! $active) {
            $this->revokeSessions($user);
        }

        return $user->load('roles');
    }

    // al desactivarlo se cierran todas sus sesiones
    private function revokeSessions(User $user): void
    {
        $user->tokens()->get()->each->revoke();
    }

    public function replacePhoto(User $user, UploadedFile $file): User
    {
        $old = $user->photo_path;

        $user->update(['photo_path' => $file->store('avatars', 'public')]);

        // la foto anterior ya no sirve; así el disco no se llena de archivos huérfanos
        if ($old) {
            Storage::disk('public')->delete($old);
        }

        return $user->load('roles');
    }

    public function removePhoto(User $user): User
    {
        if ($user->photo_path) {
            Storage::disk('public')->delete($user->photo_path);
            $user->update(['photo_path' => null]);
        }

        return $user->load('roles');
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
