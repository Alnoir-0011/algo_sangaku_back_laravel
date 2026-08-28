<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\V1\BaseController;
use App\Http\Resources\AnswerResultResource;
use Illuminate\Http\Request;

class AnswerResultsController extends BaseController
{
    public function show(Request $request, int $id)
    {
        $answerResult = $request->user()->answerResults()->findOrFail($id);

        return new AnswerResultResource($answerResult);
    }
}
