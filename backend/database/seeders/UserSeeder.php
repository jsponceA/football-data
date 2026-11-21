<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // usuarios de prueba, solo para desarrollo (van en el README)
        // la contraseña se guarda con hash por el cast del modelo
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Administrador', 'password' => 'password', 'is_active' => true],
        );
        $admin->syncRoles('admin');

        $consulta = User::updateOrCreate(
            ['email' => 'consulta@example.com'],
            ['name' => 'Usuario de consulta', 'password' => 'password', 'is_active' => true],
        );
        $consulta->syncRoles('solo-consulta');
    }
}
