<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\V1\BaseController;
use App\Http\Resources\AnswerResource;
use Illuminate\Http\Request;

class AnswersController extends BaseController
{
    public function show(Request $request, int $id)
    {
        $answer = $request->user()->answers()->findOrFail($id);

        return new AnswerResource($answer);
    }
}
