<?php

namespace App\Services;

use App\Models\Player;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PlayerService
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return Player::filter($filters)
            ->with('team')
            ->orderBy('name')
            ->paginate($perPage);
    }
}
