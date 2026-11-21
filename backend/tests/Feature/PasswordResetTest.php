<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Database\Seeders\PassportClientSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private const MENSAJE = 'Si el correo está registrado, te enviamos un enlace para restablecer tu contraseña.';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PassportClientSeeder::class, RolePermissionSeeder::class]);
    }

    private function usuario(array $datos = []): User
    {
        $user = User::factory()->create($datos + ['email' => 'ana@example.com', 'password' => 'clave-vieja-1', 'is_active' => true]);
        $user->assignRole('solo-consulta');

        return $user;
    }

    private function datosReset(User $user, array $cambios = []): array
    {
        return $cambios + [
            'token' => Password::createToken($user),
            'email' => $user->email,
            'password' => 'clave-nueva-1',
            'password_confirmation' => 'clave-nueva-1',
        ];
    }

    public function test_pedir_el_enlace_envia_el_correo_con_el_enlace_a_la_spa(): void
    {
        Notification::fake();
        config(['app.frontend_url' => 'https://app.ejemplo.com']);
        $user = $this->usuario();

        $this->postJson('/api/forgot-password', ['email' => 'ana@example.com'])
            ->assertOk()->assertJsonPath('message', self::MENSAJE);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notificacion) use ($user) {
            $correo = $notificacion->toMail($user);

            return $correo->subject === 'Restablece tu contraseña'
                && str_starts_with($correo->actionUrl, 'https://app.ejemplo.com/reset-password?token=')
                && str_contains($correo->actionUrl, 'email=ana%40example.com');
        });
    }

    public function test_un_correo_que_no_existe_recibe_la_misma_respuesta_y_no_se_envia_nada(): void
    {
        Notification::fake();

        $this->postJson('/api/forgot-password', ['email' => 'nadie@example.com'])
            ->assertOk()->assertJsonPath('message', self::MENSAJE);

        Notification::assertNothingSent();
    }

    public function test_un_usuario_desactivado_no_recibe_el_enlace(): void
    {
        Notification::fake();
        $this->usuario(['is_active' => false]);

        $this->postJson('/api/forgot-password', ['email' => 'ana@example.com'])
            ->assertOk()->assertJsonPath('message', self::MENSAJE);

        Notification::assertNothingSent();
    }

    public function test_pedir_el_enlace_exige_un_correo_valido_y_tiene_tope_de_intentos(): void
    {
        $this->postJson('/api/forgot-password', [])->assertUnprocessable()->assertJsonValidationErrors(['email']);
        $this->postJson('/api/forgot-password', ['email' => 'no-es-correo'])
            ->assertUnprocessable()->assertJsonValidationErrors(['email']);

        // ya se gastaron 2 intentos de los 5 por minuto
        foreach (range(1, 3) as $i) {
            $this->postJson('/api/forgot-password', ['email' => "x{$i}@example.com"])->assertOk();
        }

        $this->postJson('/api/forgot-password', ['email' => 'x4@example.com'])->assertStatus(429);
    }

    public function test_con_el_token_correcto_se_cambia_la_contrasena_y_se_puede_entrar(): void
    {
        $user = $this->usuario();

        $this->postJson('/api/reset-password', $this->datosReset($user))
            ->assertOk()->assertJsonPath('message', 'Tu contraseña se actualizó. Ya puedes iniciar sesión.');

        $this->assertTrue(Hash::check('clave-nueva-1', $user->fresh()->password));

        $this->postJson('/api/login', ['email' => 'ana@example.com', 'password' => 'clave-nueva-1'])->assertOk();
        $this->postJson('/api/login', ['email' => 'ana@example.com', 'password' => 'clave-vieja-1'])->assertUnauthorized();
    }

    public function test_al_restablecer_se_cierran_las_sesiones_abiertas(): void
    {
        $user = $this->usuario();
        $this->postJson('/api/login', ['email' => 'ana@example.com', 'password' => 'clave-vieja-1'])->assertOk();
        $this->assertSame(1, $user->tokens()->where('revoked', false)->count());

        $this->postJson('/api/reset-password', $this->datosReset($user))->assertOk();

        $this->assertSame(0, $user->tokens()->where('revoked', false)->count());
    }

    public function test_el_token_sirve_una_sola_vez(): void
    {
        $user = $this->usuario();
        $datos = $this->datosReset($user);

        $this->postJson('/api/reset-password', $datos)->assertOk();

        $this->postJson('/api/reset-password', $datos + ['password' => 'otra-clave-123'])
            ->assertUnprocessable()->assertJsonValidationErrors(['email']);
    }

    public function test_un_token_falso_o_de_otro_correo_se_rechaza(): void
    {
        $user = $this->usuario();
        $otro = $this->usuario(['email' => 'luis@example.com']);

        $this->postJson('/api/reset-password', $this->datosReset($user, ['token' => 'token-inventado']))
            ->assertUnprocessable()->assertJsonValidationErrors(['email']);

        // el token de Luis no vale para la cuenta de Ana
        $this->postJson('/api/reset-password', $this->datosReset($user, ['token' => Password::createToken($otro)]))
            ->assertUnprocessable()->assertJsonValidationErrors(['email']);

        $this->assertTrue(Hash::check('clave-vieja-1', $user->fresh()->password));
    }

    public function test_la_contrasena_nueva_debe_ser_valida_y_estar_confirmada(): void
    {
        $user = $this->usuario();

        $this->postJson('/api/reset-password', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['token', 'email', 'password']);

        $this->postJson('/api/reset-password', $this->datosReset($user, ['password' => 'corta', 'password_confirmation' => 'corta']))
            ->assertUnprocessable()->assertJsonValidationErrors(['password']);

        $this->postJson('/api/reset-password', $this->datosReset($user, ['password_confirmation' => 'no-coincide-1']))
            ->assertUnprocessable()->assertJsonValidationErrors(['password']);

        $this->assertTrue(Hash::check('clave-vieja-1', $user->fresh()->password));
    }
}
