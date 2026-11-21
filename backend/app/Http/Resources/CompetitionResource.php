<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Competition */
class CompetitionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->external_id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'emblem' => $this->emblem,
            'plan' => $this->plan,
            'area' => $this->area_name,
            // null = todavía no se han importado sus equipos
            'imported_at' => $this->imported_at?->toIso8601String(),
            'teams' => TeamResource::collection($this->whenLoaded('teams')),
        ];
    }
}
