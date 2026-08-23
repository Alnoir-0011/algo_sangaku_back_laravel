<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\PublicSangakuIndexRequest;
use App\Http\Resources\PublicSangakuResource;
use App\Models\Shrine;

class ShrineSangakusController extends BaseController
{
    public function index(PublicSangakuIndexRequest $request, Shrine $shrine)
    {
        $sangakus = $shrine->sangakus()
            ->search($request->validated())
            ->with(['user', 'fixedInputs', 'shrine'])
            ->orderBy('id')
            ->paginate(config('sangaku.per_page'));

        return PublicSangakuResource::collection($sangakus);
    }
}
