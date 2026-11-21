<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PassportClientSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\ActsAsUsers;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use ActsAsUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PassportClientSeeder::class, RolePermissionSeeder::class]);
    }

    public function test_sin_sesion_no_se_toca_el_perfil(): void
    {
        $this->putJson('/api/me', ['name' => 'X', 'email' => 'x@example.com'])->assertUnauthorized();
        $this->postJson('/api/me/photo', [])->assertUnauthorized();
        $this->deleteJson('/api/me/photo')->assertUnauthorized();
    }

    public function test_cualquier_usuario_cambia_su_nombre_y_correo_sin_permisos_especiales(): void
    {
        $user = $this->entrarComo('solo-consulta');

        $this->putJson('/api/me', ['name' => 'Nombre nuevo', 'email' => 'nuevo@example.com'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nombre nuevo')
            ->assertJsonPath('data.email', 'nuevo@example.com')
            // la respuesta es la misma que /me, así el front actualiza la sesión sin otra llamada
            ->assertJsonStructure(['data' => ['id', 'roles', 'permissions', 'abilities', 'photo_url']]);

        $this->assertSame('Nombre nuevo', $user->fresh()->name);
    }

    public function test_no_se_puede_usar_el_correo_de_otra_persona_pero_si_el_propio(): void
    {
        User::factory()->create(['email' => 'ocupado@example.com']);
        $user = $this->entrarComo('solo-consulta');

        $this->putJson('/api/me', ['name' => 'Yo', 'email' => 'ocupado@example.com'])
            ->assertUnprocessable()->assertJsonValidationErrors(['email']);

        $this->putJson('/api/me', ['name' => 'Yo', 'email' => $user->email])->assertOk();
    }

    public function test_editar_el_perfil_no_toca_roles_ni_estado(): void
    {
        $user = $this->entrarComo('solo-consulta');

        $this->putJson('/api/me', [
            'name' => 'Yo', 'email' => $user->email, 'roles' => ['admin'], 'is_active' => false,
        ])->assertOk();

        $user->refresh();
        $this->assertTrue($user->hasRole('solo-consulta'));
        $this->assertFalse($user->hasRole('admin'));
        $this->assertTrue($user->is_active);
    }

    public function test_cambiar_la_contrasena_exige_la_actual(): void
    {
        $user = User::factory()->create(['password' => 'clave-actual-1', 'is_active' => true]);
        $user->assignRole('solo-consulta');
        $this->entrarCon($user);

        $datos = ['name' => $user->name, 'email' => $user->email, 'password' => 'clave-nueva-1'];

        $this->putJson('/api/me', $datos)
            ->assertUnprocessable()->assertJsonValidationErrors(['current_password']);

        $this->putJson('/api/me', $datos + ['current_password' => 'incorrecta'])
            ->assertUnprocessable()->assertJsonValidationErrors(['current_password']);

        $this->assertTrue(Hash::check('clave-actual-1', $user->fresh()->password));

        $this->putJson('/api/me', $datos + ['current_password' => 'clave-actual-1'])->assertOk();

        $this->assertTrue(Hash::check('clave-nueva-1', $user->fresh()->password));
    }

    public function test_sin_contrasena_nueva_la_actual_no_cambia_ni_se_pide(): void
    {
        $user = User::factory()->create(['password' => 'clave-actual-1', 'is_active' => true]);
        $user->assignRole('solo-consulta');
        $this->entrarCon($user);

        $this->putJson('/api/me', ['name' => 'Otro', 'email' => $user->email, 'password' => null])->assertOk();

        $this->assertTrue(Hash::check('clave-actual-1', $user->fresh()->password));
    }

    public function test_cada_quien_sube_y_quita_su_propia_foto(): void
    {
        Storage::fake('public');
        $user = $this->entrarComo('solo-consulta');

        $this->postJson('/api/me/photo', ['photo' => UploadedFile::fake()->image('yo.jpg')])
            ->assertOk()
            ->assertJsonPath('data.photo_url', Storage::disk('public')->url($user->fresh()->photo_path));

        $path = $user->fresh()->photo_path;
        Storage::disk('public')->assertExists($path);

        $this->deleteJson('/api/me/photo')->assertOk()->assertJsonPath('data.photo_url', null);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_la_foto_propia_tambien_debe_ser_una_imagen_valida(): void
    {
        Storage::fake('public');
        $this->entrarComo('solo-consulta');

        $this->postJson('/api/me/photo', ['photo' => UploadedFile::fake()->create('nota.pdf', 10, 'application/pdf')])
            ->assertUnprocessable()->assertJsonValidationErrors(['photo']);
    }
}
