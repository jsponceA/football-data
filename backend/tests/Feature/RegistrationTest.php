<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PassportClientSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PassportClientSeeder::class, RolePermissionSeeder::class]);
    }

    private function datos(array $cambios = []): array
    {
        return $cambios + [
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'password' => 'clave-segura-1',
            'password_confirmation' => 'clave-segura-1',
        ];
    }

    public function test_el_registro_crea_un_usuario_con_rol_solo_consulta_y_lo_deja_dentro(): void
    {
        $respuesta = $this->postJson('/api/register', $this->datos())
            ->assertCreated()
            ->assertJsonPath('user.email', 'ana@example.com')
            ->assertJsonPath('user.roles.0', 'solo-consulta')
            ->assertJsonPath('user.is_active', true)
            ->assertJsonMissingPath('user.password');

        $user = User::where('email', 'ana@example.com')->first();
        $this->assertTrue($user->hasRole('solo-consulta'));
        $this->assertCount(1, $user->roles);
        $this->assertTrue(Hash::check('clave-segura-1', $user->password));

        // el token que devuelve ya sirve
        $this->withToken($respuesta->json('token'))->getJson('/api/me')
            ->assertOk()->assertJsonPath('data.email', 'ana@example.com');
    }

    public function test_quien_se_registra_solo_puede_ver_no_administrar(): void
    {
        $token = $this->postJson('/api/register', $this->datos())->json('token');

        $this->withToken($token)->getJson('/api/competitions')->assertOk();
        $this->withToken($token)->getJson('/api/users')->assertForbidden();
        $this->withToken($token)->getJson('/api/roles')->assertForbidden();
    }

    public function test_el_formulario_no_puede_elegir_su_rol_ni_su_estado(): void
    {
        $this->postJson('/api/register', $this->datos([
            'roles' => ['admin'], 'role' => 'admin', 'is_active' => false,
        ]))->assertCreated()->assertJsonPath('user.roles.0', 'solo-consulta');

        $user = User::where('email', 'ana@example.com')->first();
        $this->assertFalse($user->hasRole('admin'));
        $this->assertTrue($user->is_active);
    }

    public function test_datos_invalidos_dan_422_y_no_crean_nada(): void
    {
        User::factory()->create(['email' => 'repetido@example.com']);
        $antes = User::count();

        $this->postJson('/api/register', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);

        $this->postJson('/api/register', $this->datos(['email' => 'repetido@example.com']))
            ->assertUnprocessable()->assertJsonValidationErrors(['email']);

        $this->postJson('/api/register', $this->datos(['email' => 'no-es-correo']))
            ->assertUnprocessable()->assertJsonValidationErrors(['email']);

        $this->postJson('/api/register', $this->datos(['password' => 'corta', 'password_confirmation' => 'corta']))
            ->assertUnprocessable()->assertJsonValidationErrors(['password']);

        $this->postJson('/api/register', $this->datos(['password_confirmation' => 'otra-distinta-1']))
            ->assertUnprocessable()->assertJsonValidationErrors(['password']);

        $this->assertSame($antes, User::count());
    }

    public function test_el_registro_tiene_tope_de_intentos_por_minuto(): void
    {
        foreach (range(1, 5) as $i) {
            $this->postJson('/api/register', $this->datos(['email' => "persona{$i}@example.com"]))->assertCreated();
        }

        $this->postJson('/api/register', $this->datos(['email' => 'persona6@example.com']))
            ->assertStatus(429);
    }
}
