<?php

namespace App\Http\Controllers\V1;

use App\Http\Resources\PublicSangakuResource;
use App\Models\Sangaku;

class SangakusController extends BaseController
{
    public function show(Sangaku $sangaku)
    {
        $sangaku->load(['user', 'fixedInputs', 'shrine']);

        return new PublicSangakuResource($sangaku);
    }
}
