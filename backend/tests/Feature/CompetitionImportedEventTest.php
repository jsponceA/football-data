<?php

namespace Tests\Feature;

use App\Events\CompetitionImported;
use App\Listeners\LogCompetitionImport;
use App\Models\Competition;
use App\Models\Team;
use App\Services\CompetitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Tests\Concerns\FakesFootballData;
use Tests\TestCase;

// el evento que se dispara cuando una liga termina de guardarse y el listener que lo registra
class CompetitionImportedEventTest extends TestCase
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

    public function test_al_importar_una_liga_se_dispara_con_los_equipos_y_jugadores_guardados(): void
    {
        Event::fake([CompetitionImported::class]);
        $this->fingirApi();

        $this->servicio()->import(2021);

        Event::assertDispatchedTimes(CompetitionImported::class, 1);
        Event::assertDispatched(CompetitionImported::class, fn (CompetitionImported $e) => $e->competition->external_id === 2021
            && $e->teams === 2
            && $e->players === 3);
    }

    public function test_si_la_liga_es_reciente_no_hay_importacion_y_no_se_dispara(): void
    {
        Event::fake([CompetitionImported::class]);
        Http::fake();
        $this->ligaImportada(horas: 1);

        $this->servicio()->import(2021);

        Event::assertNotDispatched(CompetitionImported::class);
    }

    public function test_al_dispararse_el_evento_el_listener_registrado_deja_constancia(): void
    {
        $liga = Competition::create([
            'external_id' => 2021, 'code' => 'PL', 'name' => 'Premier League', 'type' => 'LEAGUE',
            'plan' => 'TIER_ONE', 'area_name' => 'England',
        ]);

        // sin llamar al listener a mano: Laravel lo encuentra por el tipo del evento
        Log::shouldReceive('info')->once()->with('Liga importada', Mockery::type('array'));

        CompetitionImported::dispatch($liga, 20, 500);
    }

    public function test_el_listener_deja_constancia_de_la_importacion(): void
    {
        $liga = Competition::create([
            'external_id' => 2021, 'code' => 'PL', 'name' => 'Premier League', 'type' => 'LEAGUE',
            'plan' => 'TIER_ONE', 'area_name' => 'England',
        ]);

        Log::shouldReceive('info')->once()->with('Liga importada', Mockery::on(
            fn (array $datos) => $datos === ['competition' => 2021, 'name' => 'Premier League', 'teams' => 20, 'players' => 500],
        ));

        (new LogCompetitionImport)->handle(new CompetitionImported($liga, 20, 500));
    }

    public function test_si_un_listener_falla_la_importacion_se_guarda_igual(): void
    {
        $this->fingirApi();
        Event::listen(CompetitionImported::class, fn () => throw new RuntimeException('listener roto'));

        $resultado = $this->servicio()->import(2021);

        $this->assertFalse($resultado->stale);
        $this->assertSame(2, Team::count());
        $this->assertNotNull(Competition::where('external_id', 2021)->value('imported_at'));
    }
}
