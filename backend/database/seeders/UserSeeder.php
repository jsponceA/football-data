<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // usuarios de prueba (van en el README); el cast del modelo guarda la contraseña con hash.
        // La contraseña sale de SEED_USER_PASSWORD para poder cambiarla en un despliegue real
        $password = config('app.seed_password');

        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Administrador', 'password' => $password, 'is_active' => true],
        );
        $admin->syncRoles('admin');

        $consulta = User::updateOrCreate(
            ['email' => 'consulta@example.com'],
            ['name' => 'Usuario de consulta', 'password' => $password, 'is_active' => true],
        );
        $consulta->syncRoles('solo-consulta');
    }
}
