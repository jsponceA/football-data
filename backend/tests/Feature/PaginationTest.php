<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\ActsAsUsers;
use Tests\Concerns\FakesFootballData;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    use ActsAsUsers, FakesFootballData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configurarFootballData();
        $this->seed(RolePermissionSeeder::class);
        $this->entrarComo('admin');
    }

    private function crearEquiposConJugadores(int $cantidad): void
    {
        for ($i = 1; $i <= $cantidad; $i++) {
            $team = Team::create(['external_id' => $i, 'name' => "Equipo $i"]);
            Player::create(['external_id' => $i, 'team_id' => $team->id, 'name' => "Jugador $i"]);
        }
    }

    public function test_equipos_jugadores_y_usuarios_van_de_15_en_15(): void
    {
        $this->crearEquiposConJugadores(16);
        User::factory()->count(15)->create();

        foreach (['/api/team', '/api/players', '/api/users'] as $ruta) {
            $this->getJson($ruta)
                ->assertOk()
                ->assertJsonCount(15, 'data')
                ->assertJsonPath('meta.per_page', 15)
                ->assertJsonPath('meta.last_page', 2);
        }
    }

    public function test_la_segunda_pagina_trae_el_resto(): void
    {
        $this->crearEquiposConJugadores(16);

        $this->getJson('/api/team?page=2')->assertJsonCount(1, 'data');
        $this->getJson('/api/players?page=2')->assertJsonCount(1, 'data');
    }

    public function test_ligas_roles_y_permisos_tambien_se_paginan(): void
    {
        Http::fake();
        foreach ([1, 2, 3] as $i) {
            Competition::create([
                'external_id' => $i, 'code' => "L$i", 'name' => "Liga $i", 'type' => 'LEAGUE',
                'plan' => 'TIER_ONE', 'area_name' => 'X', 'synced_at' => now(),
            ]);
        }

        $this->getJson('/api/competitions?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.stale', false);

        $this->getJson('/api/roles?per_page=1')->assertJsonCount(1, 'data')->assertJsonPath('meta.total', 2);
        $this->getJson('/api/permissions?per_page=2')->assertJsonCount(2, 'data')->assertJsonPath('meta.last_page', 3);

        Http::assertNothingSent();
    }

    public function test_por_defecto_ligas_roles_y_permisos_caben_en_una_pagina_de_15(): void
    {
        $this->ligaImportada(horas: 1);

        $this->getJson('/api/competitions')->assertJsonPath('meta.per_page', 15)->assertJsonPath('meta.last_page', 1);
        $this->getJson('/api/roles')->assertJsonPath('meta.per_page', 15);
        $this->getJson('/api/permissions')->assertJsonPath('meta.per_page', 15)->assertJsonCount(5, 'data');
    }

    public function test_per_page_invalido_da_422_en_todos_los_listados(): void
    {
        foreach (['competitions', 'team', 'players', 'users', 'roles', 'permissions'] as $ruta) {
            $this->getJson("/api/{$ruta}?per_page=0")->assertUnprocessable()->assertJsonValidationErrors(['per_page']);
            $this->getJson("/api/{$ruta}?per_page=101")->assertUnprocessable()->assertJsonValidationErrors(['per_page']);
        }
    }
}
