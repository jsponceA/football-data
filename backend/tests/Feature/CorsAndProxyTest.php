<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

// la SPA y la API están en dominios distintos y detrás de Nginx: CORS y la IP real del cliente
class CorsAndProxyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['cors.allowed_origins' => ['https://app.example.com']]);
    }

    public function test_el_origen_de_la_spa_puede_consultar_la_api(): void
    {
        $this->getJson('/api/me', ['Origin' => 'https://app.example.com'])
            ->assertUnauthorized()
            ->assertHeader('Access-Control-Allow-Origin', 'https://app.example.com');
    }

    public function test_otro_origen_no_recibe_permiso(): void
    {
        $respuesta = $this->getJson('/api/me', ['Origin' => 'https://otro-sitio.example.org'])
            ->assertUnauthorized();

        // con un solo origen permitido el servidor responde siempre con ese; el navegador compara y bloquea
        // al que no coincide. Lo que no puede pasar es que devuelva el origen del sitio ajeno ni un "*"
        $permitido = $respuesta->headers->get('Access-Control-Allow-Origin');
        $this->assertNotSame('https://otro-sitio.example.org', $permitido);
        $this->assertNotSame('*', $permitido);
    }

    public function test_la_consulta_previa_del_navegador_se_responde_con_los_metodos_y_cabeceras(): void
    {
        $this->call('OPTIONS', '/api/users', [], [], [], [
            'HTTP_ORIGIN' => 'https://app.example.com',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'PUT',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'authorization,content-type',
        ])
            ->assertSuccessful()
            ->assertHeader('Access-Control-Allow-Origin', 'https://app.example.com');
    }

    public function test_la_spa_puede_leer_retry_after_cuando_se_excede_el_limite(): void
    {
        // en un 429 la SPA muestra "vuelve a intentar en N segundos": el navegador solo deja leer
        // esa cabecera de otro dominio si el servidor la expone
        $respuesta = $this->postJson('/api/login', [], ['Origin' => 'https://app.example.com']);

        $this->assertStringContainsString('Retry-After', (string) $respuesta->headers->get('Access-Control-Expose-Headers'));
    }

    public function test_detras_de_un_proxy_la_ip_es_la_del_cliente_y_no_la_del_proxy(): void
    {
        Route::get('/_ip', fn () => request()->ip());

        // Nginx llega desde la red de Docker y reenvía la IP real del visitante
        $this->call('GET', '/_ip', [], [], [], [
            'REMOTE_ADDR' => '172.18.0.1',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.9',
        ])->assertContent('203.0.113.9');
    }

    public function test_una_ip_inventada_por_el_cliente_no_se_cuela(): void
    {
        Route::get('/_ip', fn () => request()->ip());

        // el visitante manda su propio X-Forwarded-For falso; el Nginx del servidor agrega la IP real al final
        $this->call('GET', '/_ip', [], [], [], [
            'REMOTE_ADDR' => '172.18.0.1',
            'HTTP_X_FORWARDED_FOR' => '10.0.0.99, 203.0.113.9',
        ])->assertContent('203.0.113.9');
    }
}
