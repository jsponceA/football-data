<?php

namespace Tests\Feature;

use App\Jobs\ImportCompetition;
use App\Models\Competition;
use App\Models\Team;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\Concerns\FakesFootballData;
use Tests\TestCase;

// el trabajo que refresca una liga en segundo plano y cómo se comporta cuando falta cupo o falla football-data
class ImportCompetitionJobTest extends TestCase
{
    use FakesFootballData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configurarFootballData();
    }

    // ejecuta el trabajo y devuelve los segundos que pidió esperar cada vez que se pospuso (vacío si terminó)
    private function ejecutar(int $externalId): array
    {
        $esperas = [];

        $cola = Mockery::mock(Job::class)->shouldIgnoreMissing();
        $cola->shouldReceive('release')->andReturnUsing(function (int $segundos) use (&$esperas) {
            $esperas[] = $segundos;
        });

        $trabajo = new ImportCompetition($externalId);
        $trabajo->setJob($cola);
        $trabajo->handle($this->app->make(\App\Services\CompetitionService::class));

        return $esperas;
    }

    public function test_importa_la_liga_y_termina_sin_posponerse(): void
    {
        $this->fingirApi();

        $esperas = $this->ejecutar(2021);

        $this->assertSame([], $esperas);
        $this->assertSame(2, Team::count());
        $this->assertNotNull(Competition::where('external_id', 2021)->value('imported_at'));
    }

    public function test_si_la_liga_es_reciente_no_gasta_ninguna_llamada(): void
    {
        Http::fake();
        $this->ligaImportada(horas: 1);

        $this->assertSame([], $this->ejecutar(2021));

        Http::assertNothingSent();
    }

    public function test_sin_cupo_y_con_copia_vieja_se_pospone_un_minuto(): void
    {
        Http::fake();
        $this->ligaImportada(horas: 30);
        $this->agotarCupo();

        $this->assertSame([ImportCompetition::WAIT_SECONDS], $this->ejecutar(2021));

        // no se intentó llamar: el limitador lo evitó
        Http::assertNothingSent();
    }

    public function test_sin_cupo_y_sin_copia_espera_lo_que_indica_el_limitador(): void
    {
        Http::fake();
        $this->agotarCupo();

        $esperas = $this->ejecutar(2021);

        $this->assertCount(1, $esperas);
        $this->assertGreaterThanOrEqual(1, $esperas[0]);
        $this->assertLessThanOrEqual(60, $esperas[0]);
        Http::assertNothingSent();
    }

    public function test_si_football_data_falla_y_hay_copia_vieja_se_pospone_y_la_copia_se_conserva(): void
    {
        Http::swap(new Factory);
        Http::fake(['*' => Http::response([], 500)]);
        $this->ligaImportada(horas: 30);

        $this->assertSame([ImportCompetition::WAIT_SECONDS], $this->ejecutar(2021));

        $this->assertNotNull(Competition::where('external_id', 2021)->value('imported_at'));
    }

    public function test_si_football_data_falla_y_no_hay_copia_tambien_se_pospone(): void
    {
        Http::swap(new Factory);
        Http::fake(['*' => Http::response([], 500)]);

        $this->assertSame([ImportCompetition::WAIT_SECONDS], $this->ejecutar(2021));
    }

    public function test_una_liga_de_pago_se_descarta_sin_reintentar_ni_llamar(): void
    {
        Http::fake();
        Competition::create([
            'external_id' => 2152, 'code' => 'CLI', 'name' => 'Copa Libertadores', 'type' => 'CUP',
            'plan' => 'TIER_FOUR', 'area_name' => 'South America', 'synced_at' => now(),
        ]);

        $this->assertSame([], $this->ejecutar(2152));

        Http::assertNothingSent();
    }

    public function test_una_liga_que_no_existe_se_descarta_sin_reintentar(): void
    {
        Http::fake();
        // la lista es reciente y no la incluye: no existe, no hace falta preguntar afuera
        $this->ligaImportada(horas: 1);

        $this->assertSame([], $this->ejecutar(9999));

        Http::assertNothingSent();
    }

    public function test_es_unico_por_liga_y_se_reintenta_por_plazo_no_por_numero_de_intentos(): void
    {
        $trabajo = new ImportCompetition(2021);

        $this->assertInstanceOf(ShouldQueue::class, $trabajo);
        $this->assertInstanceOf(ShouldBeUnique::class, $trabajo);
        $this->assertSame('2021', $trabajo->uniqueId());
        $this->assertNotSame($trabajo->uniqueId(), (new ImportCompetition(2014))->uniqueId());

        $plazo = $trabajo->retryUntil();
        $this->assertTrue($plazo->greaterThan(now()->addMinutes(29)));
        $this->assertTrue($plazo->lessThanOrEqualTo(now()->addMinutes(31)));
    }
}
