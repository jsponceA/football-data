<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Player */
class PlayerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // id de football-data, el mismo que se usa en las rutas
            'id' => $this->external_id,
            'name' => $this->name,
            'position' => $this->position,
            'shirt_number' => $this->shirt_number,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'nationality' => $this->nationality,
            'team' => $this->whenLoaded('team', fn () => [
                'id' => $this->team->external_id,
                'name' => $this->team->name,
            ]),
        ];
    }
}
