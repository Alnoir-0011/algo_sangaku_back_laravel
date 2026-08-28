<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\V1\BaseController;
use Illuminate\Http\Request;

class SavedSangakuIdsController extends BaseController
{
    public function index(Request $request)
    {
        $savedSangakuIds = $request->user()->userSangakuSaves()
            ->whereIn('sangaku_id', $this->requestedSangakuIds($request))
            ->pluck('sangaku_id')
            ->values()
            ->all();

        return response()->json([
            'saved_sangaku_ids' => $savedSangakuIds,
        ]);
    }

    /**
     *
     * @return array<int, int>
     */
    private function requestedSangakuIds(Request $request): array
    {
        $ids = $request->input('sangaku_ids', []);

        if (! is_array($ids)) {
            return [];
        }

        $validIds = array_values(array_filter(
            array_map(fn ($id) => filter_var($id, FILTER_VALIDATE_INT), $ids),
            fn ($id) => $id !== false,
        ));

        return array_slice($validIds, 0, (int) config('sangaku.per_page'));
    }
}
