<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Laravel\Passport\ClientRepository;
use RuntimeException;

class PassportClientSeeder extends Seeder
{
    public function run(ClientRepository $clients): void
    {
        // Passport 13 necesita un cliente personal para createToken()
        try {
            $clients->personalAccessClient('users');
        } catch (RuntimeException) {
            $clients->createPersonalAccessGrantClient('Personal Access Client', 'users');
        }
    }
}
