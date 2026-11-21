<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Competition extends Model
{
    protected $fillable = [
        'external_id', 'code', 'name', 'type', 'emblem', 'plan', 'area_name', 'synced_at', 'imported_at',
    ];

    // las rutas usan el id de football-data (/competitions/2021), no el interno
    public function getRouteKeyName(): string
    {
        return 'external_id';
    }

    protected function casts(): array
    {
        return [
            'synced_at' => 'datetime',
            'imported_at' => 'datetime',
        ];
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class);
    }
}
