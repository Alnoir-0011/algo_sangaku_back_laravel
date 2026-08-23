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
            ->with(['user', 'fixedInputs'])
            ->orderBy('id')
            ->paginate(config('sangaku.per_page'));

        // 全件がこの神社に紐づくため、shrine は eager load せず手元のインスタンスを渡す
        $sangakus->getCollection()->each->setRelation('shrine', $shrine);

        return PublicSangakuResource::collection($sangakus);
    }
}
