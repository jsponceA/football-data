<?php

namespace App\Services;

use App\Events\CompetitionImported;
use App\Exceptions\ApiException;
use App\Models\Competition;
use App\Models\Player;
use App\Models\Team;
use App\Services\FootballData\ApiRateLimiter;
use App\Services\FootballData\FootballDataClient;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CompetitionService
{
    // el plan gratuito de football-data solo da acceso a las ligas de este nivel
    public const FREE_PLAN = 'TIER_ONE';

    // errores donde, si hay copia local, mejor la devuelvo marcada como vieja
    private const RECOVERABLE = [
        Response::HTTP_TOO_MANY_REQUESTS,
        Response::HTTP_BAD_GATEWAY,
        Response::HTTP_SERVICE_UNAVAILABLE,
    ];

    public function __construct(
        private FootballDataClient $client,
        private ApiRateLimiter $limiter,
    ) {
    }

    public function list(int $perPage = 15): SyncResult
    {
        $hasLocal = Competition::exists();

        if ($hasLocal && $this->listIsFresh()) {
            return new SyncResult($this->page($perPage));
        }

        try {
            $this->syncList();
        } catch (ApiException $e) {
            if (! $hasLocal || ! in_array($e->getStatusCode(), self::RECOVERABLE)) {
                throw $e;
            }

            return new SyncResult($this->page($perPage), stale: true);
        }

        return new SyncResult($this->page($perPage));
    }

    private function page(int $perPage): LengthAwarePaginator
    {
        return Competition::orderBy('name')->paginate($perPage);
    }

    public function import(int $externalId): SyncResult
    {
        $competition = Competition::where('external_id', $externalId)->first();

        if ($competition && $this->isFresh($competition->imported_at)) {
            return new SyncResult($this->withSquads($competition));
        }

        if ($competition) {
            $this->ensureFreePlan($competition);
        } elseif ($this->listIsFresh()) {
            // la lista es reciente y no está: no existe, no gasto una llamada para comprobarlo
            throw new ApiException(Response::HTTP_NOT_FOUND, 'La liga no existe en el servicio externo.');
        }

        // si no tengo la liga hay que pedir también la lista, para saber su plan
        if (! $this->limiter->hasCapacity($competition ? 1 : 2)) {
            return $this->fallback($competition, new ApiException(Response::HTTP_TOO_MANY_REQUESTS, 'Se alcanzó el límite de consultas al servicio externo.', [
                'Retry-After' => $this->limiter->retryAfter(),
            ]));
        }

        try {
            if (! $competition) {
                $this->syncList();
                $competition = Competition::where('external_id', $externalId)->first()
                    ?? throw new ApiException(Response::HTTP_NOT_FOUND, 'La liga no existe en el servicio externo.');
                $this->ensureFreePlan($competition);
            }

            $data = $this->client->competitionTeams($externalId);
        } catch (ApiException $e) {
            return $this->fallback($competition, $e);
        }

        $this->store($competition, $data);

        return new SyncResult($this->withSquads($competition));
    }

    // si hay copia local y el fallo es de cupo o de red, la devuelvo vieja en vez de dar error
    private function fallback(?Competition $competition, ApiException $e): SyncResult
    {
        if ($competition?->imported_at && in_array($e->getStatusCode(), self::RECOVERABLE)) {
            return new SyncResult($this->withSquads($competition), stale: true);
        }

        throw $e;
    }

    // las ligas de pago se rechazan sin gastar ninguna llamada
    private function ensureFreePlan(Competition $competition): void
    {
        if ($competition->plan !== self::FREE_PLAN) {
            throw new ApiException(Response::HTTP_FORBIDDEN, 'Esa liga no está disponible con el plan gratuito.');
        }
    }

    private function isFresh(?CarbonInterface $date): bool
    {
        return $date !== null
            && $date->greaterThan(now()->subHours(config('services.football_data.fresh_hours')));
    }

    private function listIsFresh(): bool
    {
        $synced = Competition::max('synced_at');

        return $synced !== null && $this->isFresh(Carbon::parse($synced));
    }

    private function withSquads(Competition $competition): Competition
    {
        return $competition->load(['teams' => fn ($query) => $query->orderBy('name'), 'teams.players']);
    }

    private function syncList(): void
    {
        $now = now();

        $rows = collect($this->client->competitions()['competitions'])->map(fn (array $c) => [
            'external_id' => $c['id'],
            'code' => $c['code'],
            'name' => $c['name'],
            'type' => $c['type'],
            'emblem' => $c['emblem'] ?? null,
            'plan' => $c['plan'],
            'area_name' => $c['area']['name'],
            'synced_at' => $now,
        ])->all();

        // imported_at no está en la lista de columnas a actualizar, así que no se pisa
        Competition::upsert($rows, ['external_id'], [
            'code', 'name', 'type', 'emblem', 'plan', 'area_name', 'synced_at',
        ]);
    }

    private function store(Competition $competition, array $data): void
    {
        [$teamCount, $playerCount] = DB::transaction(function () use ($competition, $data) {
            $teams = $data['teams'];

            Team::upsert(collect($teams)->map(fn (array $t) => [
                'external_id' => $t['id'],
                'name' => $t['name'],
                'short_name' => $t['shortName'] ?? null,
                'tla' => $t['tla'] ?? null,
                'crest' => $t['crest'] ?? null,
                'venue' => $t['venue'] ?? null,
                'founded' => $t['founded'] ?? null,
            ])->all(), ['external_id'], ['name', 'short_name', 'tla', 'crest', 'venue', 'founded']);

            $teamIds = Team::whereIn('external_id', array_column($teams, 'id'))->pluck('id', 'external_id');

            $competition->teams()->sync($teamIds->values());

            // indexado por id para que un jugador repetido no rompa el upsert
            $players = [];
            foreach ($teams as $team) {
                foreach ($team['squad'] ?? [] as $p) {
                    $players[$p['id']] = [
                        'external_id' => $p['id'],
                        'team_id' => $teamIds[$team['id']],
                        'name' => $p['name'],
                        'position' => $p['position'] ?? null,
                        'shirt_number' => $p['shirtNumber'] ?? null,
                        'date_of_birth' => $p['dateOfBirth'] ?? null,
                        'nationality' => $p['nationality'] ?? null,
                    ];
                }
            }

            foreach (array_chunk($players, 200) as $chunk) {
                Player::upsert($chunk, ['external_id'], [
                    'team_id', 'name', 'position', 'shirt_number', 'date_of_birth', 'nationality',
                ]);
            }

            // los que ya no están en la plantilla se quitan; si la API no mandó a nadie no borro nada
            if ($players) {
                Player::whereIn('team_id', $teamIds->values())
                    ->whereNotIn('external_id', array_keys($players))
                    ->delete();
            }

            $competition->update(['imported_at' => now()]);

            return [count($teams), count($players)];
        });

        // fuera de la transacción y sin romper la importación: lo guardado ya está confirmado,
        // y que un listener falle no debe convertir una importación buena en un error para quien la pidió
        try {
            CompetitionImported::dispatch($competition, $teamCount, $playerCount);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
