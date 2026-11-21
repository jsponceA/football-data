<?php

namespace Tests\Feature;

use App\Exceptions\ApiException;
use App\Services\FootballData\ApiRateLimiter;
use App\Services\FootballData\FootballDataClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FootballDataClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.football_data.token' => 'token-de-prueba',
            'services.football_data.base_url' => 'https://api.football-data.org/v4/',
            'services.football_data.rate_limit' => 9,
        ]);
    }

    private function cliente(): FootballDataClient
    {
        return $this->app->make(FootballDataClient::class);
    }

    private function falla(callable $llamada): ApiException
    {
        try {
            $llamada();
        } catch (ApiException $e) {
            return $e;
        }

        $this->fail('Se esperaba una ApiException');
    }

    public function test_pide_la_url_correcta_con_el_token(): void
    {
        Http::fake(['*' => Http::response(['count' => 20])]);

        $datos = $this->cliente()->competitionTeams(2021);

        $this->assertSame(['count' => 20], $datos);
        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.football-data.org/v4/competitions/2021/teams'
            && $r->header('X-Auth-Token') === ['token-de-prueba']);
    }

    public function test_liga_de_pago_da_403(): void
    {
        Http::fake(['*' => Http::response([], 403)]);

        $this->assertSame(403, $this->falla(fn () => $this->cliente()->competition(2152))->getStatusCode());
    }

    public function test_liga_inexistente_da_404(): void
    {
        Http::fake(['*' => Http::response([], 404)]);

        $this->assertSame(404, $this->falla(fn () => $this->cliente()->competition(999999))->getStatusCode());
    }

    public function test_limite_del_servicio_externo_da_429_con_retry_after(): void
    {
        Http::fake(['*' => Http::response([], 429, ['Retry-After' => '30'])]);

        $error = $this->falla(fn () => $this->cliente()->competitions());

        $this->assertSame(429, $error->getStatusCode());
        $this->assertEquals(30, $error->getHeaders()['Retry-After']);
    }

    public function test_error_del_servicio_externo_da_502(): void
    {
        Http::fake(['*' => Http::response([], 500)]);

        $this->assertSame(502, $this->falla(fn () => $this->cliente()->competitions())->getStatusCode());
    }

    public function test_si_no_responde_da_503(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->assertSame(503, $this->falla(fn () => $this->cliente()->competitions())->getStatusCode());
    }

    public function test_sin_token_configurado_da_503_y_no_llama(): void
    {
        config(['services.football_data.token' => null]);
        Http::fake();

        $this->assertSame(503, $this->falla(fn () => $this->cliente()->competitions())->getStatusCode());
        Http::assertNothingSent();
    }

    public function test_sin_cupo_local_da_429_sin_llamar_afuera(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);
        config(['services.football_data.rate_limit' => 2]);
        $this->app->forgetInstance(ApiRateLimiter::class);

        $cliente = $this->cliente();
        $cliente->competitions();
        $cliente->competitions();

        $error = $this->falla(fn () => $cliente->competitions());

        $this->assertSame(429, $error->getStatusCode());
        $this->assertGreaterThanOrEqual(1, (int) $error->getHeaders()['Retry-After']);
        Http::assertSentCount(2);
    }

    public function test_cada_llamada_gasta_cupo_aunque_falle(): void
    {
        Http::fake(['*' => Http::response([], 500)]);
        $limiter = $this->app->make(ApiRateLimiter::class);

        $this->falla(fn () => $this->cliente()->competitions());

        $this->assertSame(8, $limiter->remaining());
    }
}
