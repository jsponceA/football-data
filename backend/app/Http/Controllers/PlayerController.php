<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlayerIndexRequest;
use App\Http\Resources\PlayerResource;
use App\Services\PlayerService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PlayerController extends Controller
{
    public function __construct(private PlayerService $players)
    {
    }

    public function index(PlayerIndexRequest $request): AnonymousResourceCollection
    {
        return PlayerResource::collection(
            $this->players->paginate($request->validated(), $this->perPage($request)),
        );
    }
}
