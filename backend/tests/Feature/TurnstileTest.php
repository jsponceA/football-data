<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PassportClientSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

// el captcha es opcional: solo se exige cuando hay un secreto configurado
class TurnstileTest extends TestCase
{
    use RefreshDatabase;

    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PassportClientSeeder::class, RolePermissionSeeder::class]);
    }

    private function registro(array $cambios = []): array
    {
        return $cambios + [
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'password' => 'clave-segura-1',
            'password_confirmation' => 'clave-segura-1',
        ];
    }

    private function conCaptcha(): void
    {
        config(['services.turnstile.secret' => 'secreto-de-prueba']);
    }

    public function test_sin_secreto_el_captcha_esta_apagado_y_no_se_consulta_a_cloudflare(): void
    {
        Http::fake();

        $this->postJson('/api/register', $this->registro())->assertCreated();

        Http::assertNothingSent();
    }

    public function test_con_secreto_el_registro_exige_el_token(): void
    {
        $this->conCaptcha();
        Http::fake();

        $this->postJson('/api/register', $this->registro())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['turnstile_token']);

        $this->assertDatabaseMissing('users', ['email' => 'ana@example.com']);
    }

    public function test_con_token_valido_el_registro_pasa_y_se_manda_el_secreto_a_cloudflare(): void
    {
        $this->conCaptcha();
        Http::fake([self::VERIFY_URL => Http::response(['success' => true])]);

        $this->postJson('/api/register', $this->registro(['turnstile_token' => 'token-bueno']))->assertCreated();

        Http::assertSent(fn (Request $request) => $request->url() === self::VERIFY_URL
            && $request['secret'] === 'secreto-de-prueba'
            && $request['response'] === 'token-bueno');
    }

    public function test_con_token_rechazado_no_se_crea_la_cuenta(): void
    {
        $this->conCaptcha();
        Http::fake([self::VERIFY_URL => Http::response(['success' => false, 'error-codes' => ['invalid-input-response']])]);

        $this->postJson('/api/register', $this->registro(['turnstile_token' => 'token-malo']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['turnstile_token']);

        $this->assertDatabaseMissing('users', ['email' => 'ana@example.com']);
    }

    public function test_si_cloudflare_no_responde_no_se_deja_pasar(): void
    {
        $this->conCaptcha();
        Http::fake(fn () => throw new ConnectionException('sin red'));

        $this->postJson('/api/register', $this->registro(['turnstile_token' => 'cualquiera']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['turnstile_token']);
    }

    public function test_recuperar_contrasena_tambien_exige_el_captcha_y_no_manda_correo_si_falla(): void
    {
        $this->conCaptcha();
        Notification::fake();
        Http::fake([self::VERIFY_URL => Http::response(['success' => false])]);
        $user = User::factory()->create();

        $this->postJson('/api/forgot-password', ['email' => $user->email])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['turnstile_token']);

        $this->postJson('/api/forgot-password', ['email' => $user->email, 'turnstile_token' => 'token-malo'])
            ->assertUnprocessable();

        Notification::assertNothingSent();
    }

    public function test_recuperar_contrasena_con_token_valido_envia_el_correo(): void
    {
        $this->conCaptcha();
        Notification::fake();
        Http::fake([self::VERIFY_URL => Http::response(['success' => true])]);
        $user = User::factory()->create();

        $this->postJson('/api/forgot-password', ['email' => $user->email, 'turnstile_token' => 'token-bueno'])
            ->assertOk();

        Notification::assertSentTo($user, \App\Notifications\ResetPasswordNotification::class);
    }

    public function test_el_login_no_pide_captcha(): void
    {
        $this->conCaptcha();
        Http::fake();

        $this->postJson('/api/login', ['email' => 'nadie@example.com', 'password' => 'x'])
            ->assertStatus(401);

        Http::assertNothingSent();
    }
}
