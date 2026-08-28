<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\V1\BaseController;
use App\Http\Resources\AnswerResource;
use Illuminate\Http\Request;

class SavedSangakuAnswersController extends BaseController
{
    public function store(Request $request, int $sangakuId)
    {
        $sangakuSave = $request->user()->userSangakuSaves()->where('sangaku_id', $sangakuId)->firstOrFail();

        if ($sangakuSave->answer) {
            return $this->render409('この算額にはすでに解答が存在します');
        }

        $request->validate([
            'source' => 'required|string|max:65535',
        ]);

        $answer = $sangakuSave->answer()->create([
            'source' => $request->input('source'),
        ]);

        return new AnswerResource($answer);
    }

    public function show(Request $request, int $sangakuId)
    {
        $sangakuSave = $request->user()->userSangakuSaves()->where('sangaku_id', $sangakuId)->firstOrFail();
        $answer = $sangakuSave->answer;

        if (! $answer) {
            return response()->json(['message' => 'Answer not found'], 404);
        }

        return new AnswerResource($answer);
    }
}
