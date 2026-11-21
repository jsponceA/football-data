<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Team;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\Concerns\ActsAsUsers;
use Tests\Concerns\FakesFootballData;
use Tests\TestCase;

class EndpointsTest extends TestCase
{
    use ActsAsUsers, FakesFootballData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configurarFootballData();
        $this->seed(RolePermissionSeeder::class);
    }

    private function equipoConJugadores(int $id, string $nombre, array $jugadores): Team
    {
        $team = Team::create(['external_id' => $id, 'name' => $nombre]);
        foreach ($jugadores as $i => $jugador) {
            Player::create([
                'external_id' => $id * 100 + $i, 'team_id' => $team->id, 'name' => $jugador,
                'position' => 'Midfield', 'nationality' => 'Spain',
            ]);
        }

        return $team;
    }

    public function test_sin_token_todas_las_rutas_dan_401(): void
    {
        foreach (['/api/competitions', '/api/competitions/2021', '/api/team', '/api/team/57', '/api/players'] as $ruta) {
            $this->getJson($ruta)->assertUnauthorized();
        }
    }

    public function test_lista_de_ligas_con_meta_stale(): void
    {
        $this->ligaImportada(horas: 1);
        $this->entrarComo('solo-consulta');

        $this->getJson('/api/competitions')
            ->assertOk()
            ->assertJsonPath('data.0.id', 2021)
            ->assertJsonPath('data.0.code', 'PL')
            ->assertJsonPath('meta.stale', false);
    }

    public function test_consultar_una_liga_la_importa_y_el_rol_de_solo_consulta_puede(): void
    {
        $this->fingirApi();
        $this->entrarComo('solo-consulta');

        $this->getJson('/api/competitions/2021')
            ->assertOk()
            ->assertJsonPath('data.name', 'Premier League')
            ->assertJsonCount(2, 'data.teams')
            ->assertJsonPath('data.teams.0.players.0.name', 'Jugador Uno')
            ->assertJsonPath('meta.stale', false);

        $this->assertSame(3, Player::count());
    }

    public function test_consultar_la_misma_liga_dos_veces_no_duplica_ni_vuelve_a_llamar(): void
    {
        $this->fingirApi();
        $this->entrarComo('admin');

        $this->getJson('/api/competitions/2021')->assertOk();
        $this->getJson('/api/competitions/2021')->assertOk();

        $this->assertSame(2, Team::count());
        $this->assertSame(3, Player::count());
        Http::assertSentCount(2);
    }

    public function test_liga_de_pago_da_403_en_json(): void
    {
        $this->fingirApi();
        $this->entrarComo('admin');
        $this->getJson('/api/competitions')->assertOk();

        $this->getJson('/api/competitions/2152')
            ->assertForbidden()
            ->assertExactJson(['message' => 'Esa liga no está disponible con el plan gratuito.']);
    }

    public function test_liga_inexistente_o_con_id_no_numerico_da_404(): void
    {
        $this->fingirApi();
        $this->entrarComo('admin');
        $this->getJson('/api/competitions')->assertOk();

        $this->getJson('/api/competitions/999999')->assertNotFound();
        $this->getJson('/api/competitions/abc')->assertNotFound();
    }

    public function test_sin_cupo_devuelve_lo_guardado_marcado_como_desactualizado(): void
    {
        Http::fake();
        $this->ligaImportada(horas: 30);
        $this->agotarCupo();
        $this->entrarComo('solo-consulta');

        $this->getJson('/api/competitions/2021')
            ->assertOk()
            ->assertJsonPath('meta.stale', true);
    }

    public function test_sin_cupo_y_sin_copia_da_429_con_retry_after(): void
    {
        Http::fake();
        $this->agotarCupo();
        $this->entrarComo('admin');

        $this->getJson('/api/competitions/2021')
            ->assertStatus(429)
            ->assertHeader('Retry-After');
    }

    public function test_lista_de_equipos_paginada_y_con_busqueda(): void
    {
        $this->equipoConJugadores(57, 'Arsenal', ['A', 'B']);
        $this->equipoConJugadores(58, 'Aston Villa', ['C']);
        $this->equipoConJugadores(61, 'Chelsea', []);
        $this->entrarComo('solo-consulta');

        $this->getJson('/api/team?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('data.0.players_count', 2);

        $this->getJson('/api/team?search=chel')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Chelsea');
    }

    public function test_detalle_de_equipo_con_jugadores(): void
    {
        $this->equipoConJugadores(57, 'Arsenal', ['Zubimendi', 'Saka']);
        $this->entrarComo('solo-consulta');

        $this->getJson('/api/team/57')
            ->assertOk()
            ->assertJsonPath('data.name', 'Arsenal')
            ->assertJsonCount(2, 'data.players')
            ->assertJsonPath('data.players.0.name', 'Saka');
    }

    public function test_equipo_que_no_existe_da_404_en_json(): void
    {
        $this->entrarComo('solo-consulta');

        $this->getJson('/api/team/999')
            ->assertNotFound()
            ->assertExactJson(['message' => 'No se encontró lo que buscas.']);
    }

    public function test_lista_de_jugadores_con_filtros(): void
    {
        $this->equipoConJugadores(57, 'Arsenal', ['Saka', 'Rice']);
        $this->equipoConJugadores(58, 'Aston Villa', ['Watkins']);
        $this->entrarComo('solo-consulta');

        $this->getJson('/api/players')
            ->assertOk()
            ->assertJsonPath('meta.total', 3)
            ->assertJsonStructure(['data' => [['id', 'name', 'position', 'shirt_number', 'team' => ['id', 'name']]]]);

        $this->getJson('/api/players?team=57')->assertJsonPath('meta.total', 2);
        $this->getJson('/api/players?search=wat')->assertJsonPath('data.0.name', 'Watkins');
    }

    public function test_parametros_invalidos_dan_422(): void
    {
        $this->entrarComo('solo-consulta');

        $this->getJson('/api/players?per_page=0')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);

        $this->getJson('/api/team?competition=abc')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['competition']);
    }

    public function test_un_rol_sin_permisos_recibe_403(): void
    {
        Role::create(['name' => 'sin-permisos', 'guard_name' => 'api']);
        $this->entrarComo('sin-permisos');

        $this->getJson('/api/players')
            ->assertForbidden()
            ->assertExactJson(['message' => 'No tienes permiso para hacer esto.']);
        $this->getJson('/api/competitions')->assertForbidden();
    }
}
