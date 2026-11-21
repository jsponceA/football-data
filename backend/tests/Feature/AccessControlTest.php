<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PassportClientSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\Concerns\ActsAsUsers;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use ActsAsUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PassportClientSeeder::class, RolePermissionSeeder::class]);
    }

    private function datosUsuario(array $cambios = []): array
    {
        return $cambios + [
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'password' => 'clave-segura-1',
            'is_active' => true,
            'roles' => ['solo-consulta'],
        ];
    }

    public function test_solo_quien_gestiona_usuarios_puede_entrar_al_modulo(): void
    {
        $this->entrarComo('solo-consulta');

        $this->getJson('/api/users')->assertForbidden();
        $this->postJson('/api/users', $this->datosUsuario())->assertForbidden();
        $this->getJson('/api/permissions')->assertForbidden();
        $this->postJson('/api/roles', ['name' => 'x', 'permissions' => []])->assertForbidden();
    }

    public function test_el_admin_lista_usuarios_con_busqueda_y_filtro_por_rol(): void
    {
        $this->entrarComo('admin');
        User::factory()->create(['name' => 'Carlos Ruiz', 'email' => 'carlos@example.com'])->assignRole('solo-consulta');

        $this->getJson('/api/users')->assertOk()->assertJsonPath('meta.total', 2);
        $this->getJson('/api/users?search=carlos')->assertJsonCount(1, 'data')->assertJsonPath('data.0.email', 'carlos@example.com');
        $this->getJson('/api/users?role=solo-consulta')->assertJsonCount(1, 'data');
    }

    public function test_crear_un_usuario_con_rol_y_contrasena_con_hash(): void
    {
        $this->entrarComo('admin');

        $this->postJson('/api/users', $this->datosUsuario())
            ->assertCreated()
            ->assertJsonPath('data.email', 'ana@example.com')
            ->assertJsonPath('data.roles.0', 'solo-consulta')
            ->assertJsonMissingPath('data.password');

        $user = User::where('email', 'ana@example.com')->first();
        $this->assertTrue(Hash::check('clave-segura-1', $user->password));
        $this->assertNotSame('clave-segura-1', $user->password);
        $this->assertTrue($user->hasRole('solo-consulta'));
    }

    public function test_crear_usuario_con_datos_invalidos_da_422(): void
    {
        $this->entrarComo('admin');
        User::factory()->create(['email' => 'repetido@example.com']);

        $this->postJson('/api/users', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password', 'roles']);

        $this->postJson('/api/users', $this->datosUsuario(['email' => 'repetido@example.com']))
            ->assertUnprocessable()->assertJsonValidationErrors(['email']);

        $this->postJson('/api/users', $this->datosUsuario(['password' => 'corta']))
            ->assertUnprocessable()->assertJsonValidationErrors(['password']);

        $this->postJson('/api/users', $this->datosUsuario(['roles' => ['rol-que-no-existe']]))
            ->assertUnprocessable()->assertJsonValidationErrors(['roles.0']);
    }

    public function test_editar_un_usuario_cambia_datos_roles_y_contrasena(): void
    {
        $this->entrarComo('admin');
        $user = User::factory()->create(['password' => 'vieja-clave-1'])->assignRole('solo-consulta');

        $this->putJson("/api/users/{$user->id}", $this->datosUsuario([
            'name' => 'Nombre nuevo', 'email' => $user->email, 'roles' => ['admin'], 'password' => 'nueva-clave-1',
        ]))->assertOk()->assertJsonPath('data.name', 'Nombre nuevo')->assertJsonPath('data.roles.0', 'admin');

        $user->refresh();
        $this->assertTrue(Hash::check('nueva-clave-1', $user->password));
        $this->assertTrue($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('solo-consulta'));
    }

    public function test_editar_sin_contrasena_no_la_cambia(): void
    {
        $this->entrarComo('admin');
        $user = User::factory()->create(['password' => 'misma-clave-1'])->assignRole('solo-consulta');

        $this->putJson("/api/users/{$user->id}", $this->datosUsuario([
            'email' => $user->email, 'password' => null,
        ]))->assertOk();

        $this->assertTrue(Hash::check('misma-clave-1', $user->fresh()->password));
    }

    public function test_desactivar_un_usuario_cierra_sus_sesiones(): void
    {
        $user = User::factory()->create(['email' => 'victima@example.com', 'password' => 'clave-segura-1', 'is_active' => true]);
        $user->assignRole('solo-consulta');
        $token = $this->postJson('/api/login', ['email' => 'victima@example.com', 'password' => 'clave-segura-1'])->json('token');

        $this->entrarComo('admin');
        $this->putJson("/api/users/{$user->id}", $this->datosUsuario([
            'email' => $user->email, 'is_active' => false,
        ]))->assertOk()->assertJsonPath('data.is_active', false);

        $this->assertSame(0, $user->tokens()->where('revoked', false)->count());

        // y ya no puede volver a entrar
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/login', ['email' => 'victima@example.com', 'password' => 'clave-segura-1'])->assertForbidden();
    }

    public function test_un_administrador_no_puede_desactivarse_ni_cambiarse_los_roles(): void
    {
        $admin = $this->entrarComo('admin');

        $this->putJson("/api/users/{$admin->id}", $this->datosUsuario([
            'email' => $admin->email, 'roles' => ['admin'], 'is_active' => false,
        ]))->assertUnprocessable()->assertJsonValidationErrors(['is_active']);

        $this->putJson("/api/users/{$admin->id}", $this->datosUsuario([
            'email' => $admin->email, 'roles' => ['solo-consulta'],
        ]))->assertUnprocessable()->assertJsonValidationErrors(['roles']);

        $this->assertTrue($admin->fresh()->hasRole('admin'));
    }

    public function test_usuario_inexistente_da_404(): void
    {
        $this->entrarComo('admin');

        $this->getJson('/api/users/99999')->assertNotFound();
        $this->putJson('/api/users/99999', $this->datosUsuario())->assertNotFound();
    }

    public function test_quien_solo_gestiona_usuarios_ve_los_roles_pero_no_los_modifica(): void
    {
        Role::create(['name' => 'gestor', 'guard_name' => 'api'])->givePermissionTo('users.manage');
        $this->entrarComo('gestor');

        $this->getJson('/api/roles')->assertOk();
        $this->postJson('/api/roles', ['name' => 'nuevo', 'permissions' => []])->assertForbidden();
    }

    public function test_lista_de_permisos_y_de_roles(): void
    {
        $this->entrarComo('admin');

        $this->getJson('/api/permissions')->assertOk()->assertJsonCount(5, 'data');
        $this->getJson('/api/roles')->assertOk()
            ->assertJsonPath('data.0.name', 'admin')
            ->assertJsonPath('data.0.protected', true)
            ->assertJsonPath('data.1.name', 'solo-consulta')
            ->assertJsonPath('data.1.users_count', 0);
    }

    public function test_crear_un_rol_con_permisos(): void
    {
        $this->entrarComo('admin');

        $this->postJson('/api/roles', ['name' => 'analista', 'permissions' => ['players.view', 'teams.view']])
            ->assertCreated()
            ->assertJsonPath('data.name', 'analista')
            ->assertJsonPath('data.permissions', ['players.view', 'teams.view']);

        $this->assertTrue(Role::findByName('analista', 'api')->hasPermissionTo('players.view'));
    }

    public function test_crear_un_rol_con_datos_invalidos_da_422(): void
    {
        $this->entrarComo('admin');

        $this->postJson('/api/roles', [])->assertUnprocessable()->assertJsonValidationErrors(['name', 'permissions']);
        $this->postJson('/api/roles', ['name' => 'admin', 'permissions' => []])->assertUnprocessable()->assertJsonValidationErrors(['name']);
        $this->postJson('/api/roles', ['name' => 'x', 'permissions' => ['permiso.falso']])
            ->assertUnprocessable()->assertJsonValidationErrors(['permissions.0']);
    }

    public function test_el_rol_admin_esta_protegido(): void
    {
        $this->entrarComo('admin');
        $admin = Role::findByName('admin', 'api');

        $this->putJson("/api/roles/{$admin->id}", ['name' => 'admin', 'permissions' => []])
            ->assertUnprocessable()->assertJsonValidationErrors(['role']);
        $this->deleteJson("/api/roles/{$admin->id}")->assertUnprocessable();

        $this->assertCount(5, $admin->fresh()->permissions);
    }

    public function test_no_se_borra_un_rol_con_usuarios_pero_si_uno_vacio(): void
    {
        $this->entrarComo('admin');
        $conUsuarios = Role::findByName('solo-consulta', 'api');
        $vacio = Role::create(['name' => 'temporal', 'guard_name' => 'api']);
        User::factory()->create()->assignRole('solo-consulta');

        $this->deleteJson("/api/roles/{$conUsuarios->id}")->assertUnprocessable()->assertJsonValidationErrors(['role']);
        $this->deleteJson("/api/roles/{$vacio->id}")->assertNoContent();

        $this->assertNull(Role::where('name', 'temporal')->first());
    }

    public function test_cambiar_los_permisos_de_un_rol_se_aplica_en_el_servidor_al_instante(): void
    {
        $role = Role::create(['name' => 'visitante', 'guard_name' => 'api']);
        $visitante = User::factory()->create(['is_active' => true]);
        $visitante->assignRole('visitante');

        $this->entrarCon($visitante);
        $this->getJson('/api/players')->assertForbidden();

        // el administrador le da el permiso al rol
        $this->entrarComo('admin');
        $this->putJson("/api/roles/{$role->id}", ['name' => 'visitante', 'permissions' => ['players.view']])->assertOk();

        // sin tocar código ni al usuario, ahora sí entra
        $this->entrarCon(User::find($visitante->id));
        $this->getJson('/api/players')->assertOk();

        // y si se lo quita, vuelve a quedar fuera
        $this->entrarComo('admin');
        $this->putJson("/api/roles/{$role->id}", ['name' => 'visitante', 'permissions' => []])->assertOk();
        $this->entrarCon(User::find($visitante->id));
        $this->getJson('/api/players')->assertForbidden();
    }
}
