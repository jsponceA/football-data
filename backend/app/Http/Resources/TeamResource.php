<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Team */
class TeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->external_id,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'tla' => $this->tla,
            'crest' => $this->crest,
            'venue' => $this->venue,
            'founded' => $this->founded,
            'players_count' => $this->whenCounted('players'),
            'competitions' => CompetitionSummaryResource::collection($this->whenLoaded('competitions')),
            'players' => PlayerResource::collection($this->whenLoaded('players')),
        ];
    }
}
