<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $fillable = [
        'external_id', 'name', 'short_name', 'tla', 'crest', 'venue', 'founded',
    ];

    public function getRouteKeyName(): string
    {
        return 'external_id';
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, fn (Builder $q, string $search) => $q->whereLike('name', "%{$search}%"))
            ->when($filters['competition'] ?? null, fn (Builder $q, int $id) => $q->whereHas(
                'competitions', fn (Builder $c) => $c->where('external_id', $id),
            ));
    }

    public function competitions(): BelongsToMany
    {
        return $this->belongsToMany(Competition::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }
}
