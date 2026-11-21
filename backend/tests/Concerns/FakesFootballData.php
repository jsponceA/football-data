<?php

namespace Tests\Concerns;

use App\Models\Competition;
use App\Services\FootballData\ApiRateLimiter;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;

// datos falsos de football-data para no gastar llamadas reales en los tests
trait FakesFootballData
{
    protected function configurarFootballData(): void
    {
        config([
            'services.football_data.token' => 'token-de-prueba',
            'services.football_data.rate_limit' => 9,
            'services.football_data.fresh_hours' => 24,
        ]);
    }

    protected function listaExterna(): array
    {
        return ['count' => 2, 'competitions' => [
            ['id' => 2021, 'code' => 'PL', 'name' => 'Premier League', 'type' => 'LEAGUE', 'emblem' => 'pl.png', 'plan' => 'TIER_ONE', 'area' => ['name' => 'England']],
            ['id' => 2152, 'code' => 'CLI', 'name' => 'Copa Libertadores', 'type' => 'CUP', 'emblem' => null, 'plan' => 'TIER_FOUR', 'area' => ['name' => 'South America']],
        ]];
    }

    protected function equiposExternos(?array $plantillas = null): array
    {
        $plantillas ??= [
            57 => [[10, 'Jugador Uno'], [11, 'Jugador Dos']],
            58 => [[20, 'Jugador Tres']],
        ];

        $teams = [];
        foreach ($plantillas as $teamId => $squad) {
            $teams[] = [
                'id' => $teamId, 'name' => "Equipo $teamId", 'shortName' => "EQ$teamId", 'tla' => "E$teamId",
                'crest' => null, 'venue' => 'Estadio', 'founded' => 1900,
                'squad' => array_map(fn ($p) => [
                    'id' => $p[0], 'name' => $p[1], 'position' => 'Defence',
                    'dateOfBirth' => '1999-05-01', 'nationality' => 'Spain',
                ], $squad),
            ];
        }

        return ['count' => count($teams), 'competition' => ['id' => 2021, 'code' => 'PL'], 'teams' => $teams];
    }

    protected function fingirApi(?array $equipos = null): void
    {
        // sin reiniciar, Http::fake acumula y gana la primera respuesta que coincide
        Http::swap(new Factory);

        Http::fake([
            '*/competitions/*/teams' => Http::response($equipos ?? $this->equiposExternos()),
            '*/competitions' => Http::response($this->listaExterna()),
        ]);
    }

    protected function ligaImportada(int $horas = 1): Competition
    {
        return Competition::create([
            'external_id' => 2021, 'code' => 'PL', 'name' => 'Premier League', 'type' => 'LEAGUE',
            'plan' => 'TIER_ONE', 'area_name' => 'England',
            'synced_at' => now()->subHours($horas), 'imported_at' => now()->subHours($horas),
        ]);
    }

    protected function agotarCupo(): void
    {
        $limiter = $this->app->make(ApiRateLimiter::class);
        for ($i = 0; $i < 9; $i++) {
            $limiter->hit();
        }
    }
}
