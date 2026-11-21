<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Versión corta de una liga para mostrarla dentro de equipos y jugadores.
 *
 * @mixin \App\Models\Competition
 */
class CompetitionSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->external_id,
            'code' => $this->code,
            'name' => $this->name,
            'emblem' => $this->emblem,
        ];
    }
}
