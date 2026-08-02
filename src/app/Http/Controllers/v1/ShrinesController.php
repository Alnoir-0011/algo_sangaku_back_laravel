<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShrineResource;
use App\Models\Shrine;
use App\Services\PlaceApiService;
use Illuminate\Http\Request;

class ShrinesController extends Controller
{
    public function index(Request $request)
    {
        $shrines = match ($request->input('searchType')) {
            'Map' => PlaceApiService::searchByBounds(
                $request->input('lowLat'),
                $request->input('highLat'),
                $request->input('lowLng'),
                $request->input('highLng'),
            ),
            default => null,
        };

        if ($shrines) {
            return ShrineResource::collection($shrines);
        } else {
            return response()->json(['error' => 'Invalid params'], 400);
        }
    }

    public function show(Shrine $shrine)
    {
        return new ShrineResource($shrine);
    }
}
