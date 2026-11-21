<?php

namespace App\Services;

use App\Models\Team;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TeamService
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return Team::filter($filters)
            ->withCount('players')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function detail(Team $team): Team
    {
        return $team->load([
            'competitions' => fn ($query) => $query->orderBy('name'),
            'players' => fn ($query) => $query->orderBy('name'),
        ]);
    }
}
