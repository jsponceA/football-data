<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    // formato recurso.acción
    public const PERMISSIONS = [
        'competitions.view',
        'competitions.import',
        'teams.view',
        'players.view',
        'users.manage',
        'roles.manage',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = 'api';

        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
        }

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => $guard])
            ->syncPermissions(self::PERMISSIONS);

        Role::firstOrCreate(['name' => 'solo-consulta', 'guard_name' => $guard])
            ->syncPermissions(['competitions.view', 'teams.view', 'players.view']);
    }
}
