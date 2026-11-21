<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// de madrugada (hora de Lima) casi nadie consulta y sobra cupo de football-data: se refrescan las ligas
// ya importadas cuyos datos tienen más de 24 horas. Solo encola trabajos; los ejecuta el worker de la cola.
Schedule::command('football:refresh')->dailyAt('03:00')->withoutOverlapping();
