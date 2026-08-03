<?php

namespace App\Http\Controllers\v1\user;

use App\Http\Controllers\Controller;
use App\Http\Requests\SangakuRequest;
use App\Http\Resources\SangakuResource;
use App\Models\Sangaku;
use Illuminate\Http\Request;

class SangakusController extends Controller
{
    public function index(Request $request)
    {
        $sangakus = $request->user()->sangakus()->search()->with(['user', 'shrine', 'fixedInputs'])->paginate(10);

        return SangakuResource::collection($sangakus);
    }

    public function show(Sangaku $sangaku)
    {
        return new SangakuResource($sangaku);
    }

    public function store(Request $request)
    {
        $sangaku = $request->user()->sangakus()->create($request->all());

        return new SangakuResource($sangaku);
    }

    public function update(Sangaku $sangaku, SangakuRequest $request)
    {
        $sangaku->update($request->all());

        return new SangakuResource($sangaku);
    }

    public function destroy(Sangaku $sangaku)
    {
        $sangaku->delete();

        return response()->noContent();
    }
}
