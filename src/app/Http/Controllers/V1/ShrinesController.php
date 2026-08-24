<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\ShrineSearchRequest;
use App\Http\Resources\ShrineResource;
use App\Models\Shrine;
use App\Services\PlaceApiService;

class ShrinesController extends BaseController
{
    public function __construct(
        private PlaceApiService $placeApiService
    ) {}

    public function index(ShrineSearchRequest $request)
    {
        $shrines = $this->placeApiService->searchByBounds(
            (float) $request->input('lowLat'),
            (float) $request->input('highLat'),
            (float) $request->input('lowLng'),
            (float) $request->input('highLng'),
        );

        return ShrineResource::collection($shrines);
    }

    public function show(Shrine $shrine)
    {
        return new ShrineResource($shrine);
    }
}
