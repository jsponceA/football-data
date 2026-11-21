<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PassportClientSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PassportClientSeeder::class, RolePermissionSeeder::class]);
    }

    private function usuario(string $rol = 'solo-consulta', array $datos = []): User
    {
        $user = User::factory()->create($datos + ['password' => 'secreto123', 'is_active' => true]);
        $user->assignRole($rol);

        return $user;
    }

    public function test_login_correcto_devuelve_token_y_usuario(): void
    {
        $user = $this->usuario();

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'secreto123'])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'email', 'roles', 'permissions', 'abilities']])
            ->assertJsonPath('user.roles.0', 'solo-consulta');
    }

    public function test_login_con_contrasena_mala_da_401(): void
    {
        $user = $this->usuario();

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'otra'])
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Correo o contraseña incorrectos.']);
    }

    public function test_login_sin_datos_da_422_con_errores(): void
    {
        $this->postJson('/api/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_usuario_desactivado_no_puede_entrar(): void
    {
        $user = $this->usuario(datos: ['is_active' => false]);

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'secreto123'])
            ->assertForbidden();
    }

    public function test_me_sin_token_da_401_en_json(): void
    {
        $this->getJson('/api/me')
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'No has iniciado sesión.']);
    }

    public function test_me_con_token_devuelve_permisos_en_formato_casl(): void
    {
        $user = $this->usuario();

        $token = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'secreto123'])
            ->json('token');

        $this->withToken($token)->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonFragment(['action' => 'view', 'subject' => 'teams'])
            ->assertJsonMissing(['subject' => 'users']);
    }

    public function test_logout_revoca_el_token(): void
    {
        $user = $this->usuario();

        $token = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'secreto123'])
            ->json('token');

        $this->withToken($token)->postJson('/api/logout')->assertOk();

        // el guard se acuerda del usuario dentro del mismo test, lo reinicio
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
    }

    public function test_usuario_desactivado_pierde_acceso_aunque_tenga_token(): void
    {
        $user = $this->usuario();

        $token = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'secreto123'])
            ->json('token');

        $user->update(['is_active' => false]);
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/me')->assertForbidden();
    }

    public function test_ruta_inexistente_da_404_en_json(): void
    {
        $this->getJson('/api/no-existe')
            ->assertNotFound()
            ->assertExactJson(['message' => 'No se encontró lo que buscas.']);
    }
}
