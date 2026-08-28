<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\V1\BaseController;
use Illuminate\Http\Request;

class ResultsController extends BaseController
{
    public function show(Request $request, int $sangakuId)
    {
        $sangaku = $request->user()->sangakus()->findOrFail($sangakuId);

        return response()->json([
            'data' => [
                'attributes' => [
                    'user_sangaku_save_count' => $sangaku->userSangakuSaves()->count(),
                    'correct_count' => $sangaku->answers()->statusCorrect()->count(),
                    'incorrect_count' => $sangaku->answers()->statusIncorrect()->count(),
                ],
            ],
        ]);
    }
}
