<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // primero los roles, porque los usuarios los necesitan
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
        ]);
    }
}
