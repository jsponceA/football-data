<?php

namespace Tests\Feature;

use App\Exceptions\ApiException;
use App\Models\Competition;
use App\Models\Player;
use App\Models\Team;
use App\Services\CompetitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\FakesFootballData;
use Tests\TestCase;

class CompetitionServiceTest extends TestCase
{
    use FakesFootballData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configurarFootballData();
    }

    private function servicio(): CompetitionService
    {
        return $this->app->make(CompetitionService::class);
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

    public function test_importa_una_liga_nueva_con_equipos_y_jugadores(): void
    {
        $this->fingirApi();

        $resultado = $this->servicio()->import(2021);

        $this->assertFalse($resultado->stale);
        $this->assertSame(2, Team::count());
        $this->assertSame(3, Player::count());
        $this->assertSame(2, Competition::first()->teams()->count());
        $this->assertNotNull(Competition::where('external_id', 2021)->value('imported_at'));
        // lista + equipos
        Http::assertSentCount(2);
    }

    public function test_el_dorsal_queda_en_null_porque_el_plan_gratis_no_lo_manda(): void
    {
        $this->fingirApi();

        $this->servicio()->import(2021);

        $this->assertNull(Player::where('external_id', 10)->value('shirt_number'));
    }

    public function test_si_la_liga_es_reciente_no_llama_a_la_api(): void
    {
        Http::fake();
        $this->ligaImportada(horas: 1);

        $resultado = $this->servicio()->import(2021);

        $this->assertFalse($resultado->stale);
        Http::assertNothingSent();
    }

    public function test_si_la_liga_esta_vieja_la_vuelve_a_pedir_con_una_sola_llamada(): void
    {
        $this->fingirApi();
        $this->ligaImportada(horas: 30);

        $this->servicio()->import(2021);

        // ya tenía la liga (y su plan), no necesita la lista
        Http::assertSentCount(1);
        $this->assertTrue(Competition::first()->imported_at->greaterThan(now()->subMinute()));
    }

    public function test_liga_de_pago_da_403_sin_gastar_llamadas(): void
    {
        Http::fake();
        Competition::create([
            'external_id' => 2152, 'code' => 'CLI', 'name' => 'Copa Libertadores', 'type' => 'CUP',
            'plan' => 'TIER_FOUR', 'area_name' => 'South America', 'synced_at' => now(),
        ]);

        $this->assertSame(403, $this->falla(fn () => $this->servicio()->import(2152))->getStatusCode());
        Http::assertNothingSent();
    }

    public function test_liga_que_no_esta_en_la_lista_externa_da_404(): void
    {
        $this->fingirApi();

        $this->assertSame(404, $this->falla(fn () => $this->servicio()->import(99999))->getStatusCode());
    }

    public function test_con_la_lista_fresca_una_liga_desconocida_da_404_sin_llamar(): void
    {
        Http::fake();
        $this->ligaImportada(horas: 1);

        $this->assertSame(404, $this->falla(fn () => $this->servicio()->import(99999))->getStatusCode());
        Http::assertNothingSent();
    }

    public function test_sin_cupo_y_con_copia_local_devuelve_la_copia_marcada_como_vieja(): void
    {
        Http::fake();
        $this->ligaImportada(horas: 30);
        $this->agotarCupo();

        $resultado = $this->servicio()->import(2021);

        $this->assertTrue($resultado->stale);
        Http::assertNothingSent();
    }

    public function test_sin_cupo_y_sin_copia_local_da_429_con_retry_after(): void
    {
        Http::fake();
        $this->agotarCupo();

        $error = $this->falla(fn () => $this->servicio()->import(2021));

        $this->assertSame(429, $error->getStatusCode());
        $this->assertGreaterThanOrEqual(1, (int) $error->getHeaders()['Retry-After']);
    }

    public function test_si_la_api_falla_y_hay_copia_local_devuelve_la_copia_vieja(): void
    {
        Http::fake(['*' => Http::response([], 500)]);
        $this->ligaImportada(horas: 30);

        $resultado = $this->servicio()->import(2021);

        $this->assertTrue($resultado->stale);
    }

    public function test_si_la_api_falla_y_no_hay_copia_local_da_502(): void
    {
        Http::fake(['*' => Http::response([], 500)]);
        $this->ligaImportada(horas: 30)->update(['imported_at' => null]);

        $this->assertSame(502, $this->falla(fn () => $this->servicio()->import(2021))->getStatusCode());
    }

    public function test_quita_los_jugadores_que_ya_no_estan_en_la_plantilla(): void
    {
        $this->fingirApi();
        $this->servicio()->import(2021);
        $this->assertSame(2, Team::where('external_id', 57)->first()->players()->count());

        // el jugador 11 se fue del equipo 57
        $this->fingirApi($this->equiposExternos([57 => [[10, 'Jugador Uno']], 58 => [[20, 'Jugador Tres']]]));
        Competition::first()->update(['imported_at' => now()->subHours(30)]);
        $this->servicio()->import(2021);

        $this->assertSame(1, Team::where('external_id', 57)->first()->players()->count());
        $this->assertNull(Player::where('external_id', 11)->first());
    }

    public function test_un_jugador_que_cambia_de_equipo_no_se_borra(): void
    {
        $this->fingirApi();
        $this->servicio()->import(2021);

        // el jugador 11 pasa del equipo 57 al 58
        $this->fingirApi($this->equiposExternos([57 => [[10, 'Jugador Uno']], 58 => [[20, 'Jugador Tres'], [11, 'Jugador Dos']]]));
        Competition::first()->update(['imported_at' => now()->subHours(30)]);
        $this->servicio()->import(2021);

        $this->assertSame(58, Player::where('external_id', 11)->first()->team->external_id);
        $this->assertSame(3, Player::count());
    }

    public function test_la_lista_reciente_se_lee_de_la_base_sin_llamar(): void
    {
        Http::fake();
        $this->ligaImportada(horas: 1);

        $resultado = $this->servicio()->list();

        $this->assertCount(1, $resultado->data);
        $this->assertFalse($resultado->stale);
        Http::assertNothingSent();
    }

    public function test_la_lista_vieja_se_refresca_y_conserva_imported_at(): void
    {
        $this->fingirApi();
        $this->ligaImportada(horas: 30);
        $importadaAntes = Competition::first()->imported_at;

        $resultado = $this->servicio()->list();

        $this->assertCount(2, $resultado->data);
        $this->assertTrue(Competition::where('external_id', 2021)->first()->imported_at->equalTo($importadaAntes));
    }

    public function test_si_la_lista_falla_y_hay_copia_local_la_devuelve_vieja(): void
    {
        Http::fake(['*' => Http::response([], 503)]);
        $this->ligaImportada(horas: 30);

        $resultado = $this->servicio()->list();

        $this->assertTrue($resultado->stale);
        $this->assertCount(1, $resultado->data);
    }
}
