<?php

namespace App\Events;

use App\Models\Competition;
use Illuminate\Foundation\Events\Dispatchable;

// se dispara cuando una liga termina de guardarse, venga la petición de una persona o de la cola
class CompetitionImported
{
    use Dispatchable;

    public function __construct(
        public readonly Competition $competition,
        public readonly int $teams,
        public readonly int $players,
    ) {
    }
}
