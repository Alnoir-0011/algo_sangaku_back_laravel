<?php

namespace App\Http\Controllers\V1;

use App\Http\Resources\ProfileResource;
use App\Models\User;

class ProfilesController extends BaseController
{
    public function show(User $user)
    {
        return new ProfileResource($user);
    }
}
