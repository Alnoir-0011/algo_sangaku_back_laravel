<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\V1\BaseController;
use App\Http\Requests\User\SangakuDedicateRequest;
use App\Http\Resources\SangakuResource;
use App\Models\Shrine;

class DedicateController extends BaseController
{
    public function __invoke(SangakuDedicateRequest $request, int $sangakuId)
    {
        $validated = $request->validated();

        $sangaku = $request->user()->sangakus()->findOrFail($sangakuId);
        $shrine = Shrine::findOrFail($validated['shrine_id']);

        // 既に奉納済みであることは、座標が遠いことより優先して伝える。
        if ($sangaku->isDedicated()) {
            return $this->render409('この算額は既に奉納されています');
        }

        if (! $shrine->isWithinDedicateRange((float) $validated['lat'], (float) $validated['lng'])) {
            return $this->render400('算額を奉納できませんでした');
        }

        // ここで false になるのは、上の判定後に別リクエストが先に奉納を終えた場合。
        if (! $sangaku->dedicateTo($shrine)) {
            return $this->render409('この算額は既に奉納されています');
        }

        $sangaku->refresh()->load(['user', 'fixedInputs', 'shrine']);

        return new SangakuResource($sangaku);
    }
}
