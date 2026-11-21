<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeamIndexRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use App\Services\TeamService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TeamController extends Controller
{
    public function __construct(private TeamService $teams)
    {
    }

    public function index(TeamIndexRequest $request): AnonymousResourceCollection
    {
        return TeamResource::collection(
            $this->teams->paginate($request->validated(), $request->integer('per_page', 20)),
        );
    }

    public function show(Team $team): TeamResource
    {
        return TeamResource::make($this->teams->detail($team));
    }
}
