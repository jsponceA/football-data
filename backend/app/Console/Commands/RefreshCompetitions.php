<?php

namespace App\Console\Commands;

use App\Exceptions\ApiException;
use App\Jobs\ImportCompetition;
use App\Models\Competition;
use App\Services\CompetitionService;
use Illuminate\Console\Command;

// no importa nada por sí mismo: encola un trabajo por liga y deja que la cola los reparta respetando el límite
class RefreshCompetitions extends Command
{
    // segundos entre un trabajo y el siguiente: cada importación cuesta una o dos llamadas y el tope es de 9 por minuto
    private const SPACING_SECONDS = 15;

    protected $signature = 'football:refresh
        {--all : Incluye también las ligas gratuitas que todavía no se han importado}';

    protected $description = 'Encola la actualización de las ligas cuyos datos ya no son recientes';

    public function handle(CompetitionService $competitions): int
    {
        if ($this->option('all')) {
            // en una instalación nueva no hay ligas guardadas: se trae la lista para saber cuáles existen
            try {
                $competitions->list(1);
            } catch (ApiException $e) {
                $this->warn("No se pudo actualizar la lista de ligas: {$e->getMessage()}");
            }
        }

        $ids = $this->due();

        foreach ($ids as $position => $externalId) {
            ImportCompetition::dispatch($externalId)->delay(now()->addSeconds($position * self::SPACING_SECONDS));
        }

        $this->info(count($ids).' '.(count($ids) === 1 ? 'liga encolada' : 'ligas encoladas').'.');

        return self::SUCCESS;
    }

    // las ligas gratuitas cuya última importación ya no es reciente (o que nunca se hicieron, con --all)
    private function due(): array
    {
        $limit = now()->subHours(config('services.football_data.fresh_hours'));

        return Competition::query()
            ->where('plan', CompetitionService::FREE_PLAN)
            ->where(function ($query) use ($limit) {
                $query->where('imported_at', '<', $limit);

                if ($this->option('all')) {
                    $query->orWhereNull('imported_at');
                }
            })
            ->orderBy('external_id')
            ->pluck('external_id')
            ->all();
    }
}
