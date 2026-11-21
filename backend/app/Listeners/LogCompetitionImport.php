<?php

namespace App\Listeners;

use App\Events\CompetitionImported;
use Illuminate\Support\Facades\Log;

// deja constancia de cada importación; Laravel lo asocia al evento por el tipo del parámetro de handle()
class LogCompetitionImport
{
    public function handle(CompetitionImported $event): void
    {
        Log::info('Liga importada', [
            'competition' => $event->competition->external_id,
            'name' => $event->competition->name,
            'teams' => $event->teams,
            'players' => $event->players,
        ]);
    }
}
