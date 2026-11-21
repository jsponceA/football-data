<?php

namespace Tests\Concerns;

use App\Models\User;
use Laravel\Passport\Passport;

trait ActsAsUsers
{
    protected function entrarComo(string $rol): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($rol);

        return $this->entrarCon($user);
    }

    protected function entrarCon(User $user): User
    {
        // el guard recuerda al usuario anterior dentro del mismo test; se reinicia al cambiar
        $this->app['auth']->forgetGuards();
        Passport::actingAs($user, [], 'api');

        return $user;
    }
}
