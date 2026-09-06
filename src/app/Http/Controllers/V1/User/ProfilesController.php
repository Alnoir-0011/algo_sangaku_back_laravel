<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\V1\BaseController;
use App\Http\Resources\MyProfileResource;
use Illuminate\Http\Request;

class ProfilesController extends BaseController
{
    public function show(Request $request)
    {
        $user = $request->user();

        return new MyProfileResource($user);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validatedData = $request->validate([
            'nickname' => 'sometimes|string|max:255',
            'show_answer_count' => 'sometimes|boolean',
        ]);

        if (isset($validatedData['nickname'])) {
            $user->nickname = $validatedData['nickname'];
        }
        if (isset($validatedData['show_answer_count'])) {
            $user->show_answer_count = $validatedData['show_answer_count'];
        }
        $user->save();

        return new MyProfileResource($user);
    }
}
