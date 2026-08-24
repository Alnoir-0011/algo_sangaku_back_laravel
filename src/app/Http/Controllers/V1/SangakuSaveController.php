<?php

namespace App\Http\Controllers\V1;

use App\Http\Resources\PublicSangakuResource;
use App\Models\Sangaku;
use Illuminate\Http\Request;

class SangakuSaveController extends BaseController
{
    public function __invoke(Request $request, Sangaku $sangaku)
    {
        // 既に保存済みの場合は user_sangaku_saves の複合 unique 制約に違反し、
        // QueryException が bootstrap/app.php のマッピングで 409 に変換される。
        $request->user()->savedSangakus()->attach($sangaku->id);

        $sangaku->load(['user', 'fixedInputs', 'shrine']);

        return new PublicSangakuResource($sangaku);
    }
}
