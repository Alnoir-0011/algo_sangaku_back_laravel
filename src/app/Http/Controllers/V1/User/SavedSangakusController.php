<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\V1\BaseController;
use App\Http\Resources\PublicSangakuResource;
use Illuminate\Http\Request;

class SavedSangakusController extends BaseController
{
    public function index(Request $request)
    {
        $savedSangakus = $request->user()->savedSangakus()->with(['user', 'fixedInputs', 'shrine'])->get();

        return PublicSangakuResource::collection($savedSangakus);
    }

    public function show(Request $request, int $sangakuId)
    {
        $savedSangaku = $request->user()->savedSangakus()->with(['user', 'fixedInputs', 'shrine'])->findOrFail($sangakuId);

        return new PublicSangakuResource($savedSangaku);
    }
}
