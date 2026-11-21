<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Player extends Model
{
    protected $fillable = [
        'external_id', 'team_id', 'name', 'position', 'shirt_number', 'date_of_birth', 'nationality',
    ];

    public function getRouteKeyName(): string
    {
        return 'external_id';
    }

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
