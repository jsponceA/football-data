<?php

namespace App\Http\Controllers;

use App\Http\Resources\CompetitionResource;
use App\Services\CompetitionService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CompetitionController extends Controller
{
    public function __construct(private CompetitionService $competitions)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        $result = $this->competitions->list();

        return CompetitionResource::collection($result->data)
            ->additional(['meta' => ['stale' => $result->stale]]);
    }

    // consultar una liga la importa (equipos y jugadores) si hace falta
    public function show(int $competitionId): CompetitionResource
    {
        $result = $this->competitions->import($competitionId);

        return CompetitionResource::make($result->data)
            ->additional(['meta' => ['stale' => $result->stale]]);
    }
}
