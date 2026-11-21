<?php

namespace Tests\Feature;

use App\Jobs\ImportCompetition;
use App\Models\Competition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\FakesFootballData;
use Tests\TestCase;

// el comando que encola la actualización de las ligas y el horario en que corre cada noche
class RefreshCompetitionsCommandTest extends TestCase
{
    use FakesFootballData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configurarFootballData();
        Queue::fake();
    }

    private function liga(int $id, string $plan, ?int $importadaHaceHoras, int $listaHaceHoras = 1): Competition
    {
        return Competition::create([
            'external_id' => $id, 'code' => "L{$id}", 'name' => "Liga {$id}", 'type' => 'LEAGUE',
            'plan' => $plan, 'area_name' => 'England',
            'synced_at' => now()->subHours($listaHaceHoras),
            'imported_at' => $importadaHaceHoras === null ? null : now()->subHours($importadaHaceHoras),
        ]);
    }

    private function encoladas(): array
    {
        return Queue::pushed(ImportCompetition::class)->map(fn ($trabajo) => $trabajo->externalId)->all();
    }

    public function test_encola_solo_las_ligas_gratuitas_importadas_cuyos_datos_ya_no_son_recientes(): void
    {
        $this->liga(2021, 'TIER_ONE', 30);   // vieja: sí
        $this->liga(2014, 'TIER_ONE', 2);    // reciente: no
        $this->liga(2152, 'TIER_FOUR', 40);  // de pago: no
        $this->liga(2002, 'TIER_ONE', null); // nunca importada: no, sin --all

        $this->artisan('football:refresh')->expectsOutput('1 liga encolada.')->assertSuccessful();

        $this->assertSame([2021], $this->encoladas());
    }

    public function test_con_all_incluye_las_gratuitas_que_nunca_se_importaron(): void
    {
        $this->liga(2021, 'TIER_ONE', 30);
        $this->liga(2002, 'TIER_ONE', null);
        $this->liga(2152, 'TIER_FOUR', null); // de pago: nunca

        $this->artisan('football:refresh', ['--all' => true])->expectsOutput('2 ligas encoladas.')->assertSuccessful();

        $this->assertEqualsCanonicalizing([2002, 2021], $this->encoladas());
    }

    public function test_los_trabajos_se_escalonan_para_no_pedir_todo_a_la_vez(): void
    {
        $this->liga(2002, 'TIER_ONE', 30);
        $this->liga(2014, 'TIER_ONE', 30);
        $this->liga(2021, 'TIER_ONE', 30);

        $this->artisan('football:refresh')->assertSuccessful();

        $trabajos = Queue::pushed(ImportCompetition::class)->values();
        $this->assertCount(3, $trabajos);
        $this->assertTrue($trabajos[1]->delay->greaterThan($trabajos[0]->delay));
        $this->assertTrue($trabajos[2]->delay->greaterThan($trabajos[1]->delay));
    }

    public function test_sin_nada_que_refrescar_no_encola_nada(): void
    {
        $this->liga(2021, 'TIER_ONE', 1);

        $this->artisan('football:refresh')->expectsOutput('0 ligas encoladas.')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_con_all_trae_la_lista_de_ligas_si_esta_vieja(): void
    {
        // instalación nueva: no hay ninguna liga guardada y la lista se pide a football-data
        $this->fingirApi();

        $this->artisan('football:refresh', ['--all' => true])->assertSuccessful();

        Http::assertSentCount(1);
        // la lista trae una liga gratuita (Premier League) y una de pago (Copa Libertadores)
        $this->assertSame([2021], $this->encoladas());
    }

    public function test_con_all_y_sin_ninguna_liga_guardada_si_la_lista_falla_avisa_y_no_encola(): void
    {
        Http::fake(['*' => Http::response([], 500)]);

        $this->artisan('football:refresh', ['--all' => true])
            ->expectsOutputToContain('No se pudo actualizar la lista de ligas')
            ->expectsOutput('0 ligas encoladas.')
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_con_all_y_con_copia_local_si_la_lista_falla_sigue_con_lo_guardado(): void
    {
        // el servicio devuelve la copia vieja en vez de un error, así que el comando no tiene nada que avisar
        Http::fake(['*' => Http::response([], 500)]);
        $this->liga(2021, 'TIER_ONE', 30, listaHaceHoras: 30);

        $this->artisan('football:refresh', ['--all' => true])->assertSuccessful();

        $this->assertSame([2021], $this->encoladas());
    }

    public function test_corre_todas_las_noches_a_las_tres(): void
    {
        Artisan::call('schedule:list');

        $salida = Artisan::output();
        $this->assertStringContainsString('football:refresh', $salida);
        $this->assertStringContainsString('0 3 * * *', $salida);
    }
}
