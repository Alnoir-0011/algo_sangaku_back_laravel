<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\V1\BaseController;
use App\Http\Requests\User\SangakuIndexRequest;
use App\Http\Requests\User\SangakuStoreRequest;
use App\Http\Requests\User\SangakuUpdateRequest;
use App\Http\Resources\SangakuResource;
use App\Models\Sangaku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SangakusController extends BaseController
{
    public function index(SangakuIndexRequest $request)
    {
        $sangakus = $request->user()->sangakus()
            ->search($request->validated())
            ->with(['fixedInputs', 'shrine'])
            ->orderBy('id')
            ->paginate(config('sangaku.per_page'));

        // user は常に認証ユーザー自身なので、eager load せず手元のインスタンスを渡す
        $sangakus->getCollection()->each->setRelation('user', $request->user());

        return SangakuResource::collection($sangakus);
    }

    public function show(Request $request, int $id)
    {
        $sangaku = $request->user()->sangakus()->with(['fixedInputs', 'shrine'])->findOrFail($id);
        $sangaku->setRelation('user', $request->user());

        return new SangakuResource($sangaku);
    }

    public function store(SangakuStoreRequest $request)
    {
        $validated = $request->validated();

        $sangaku = DB::transaction(function () use ($request, $validated) {
            /** @var Sangaku $sangaku */
            $sangaku = $request->user()->sangakus()->create($validated['sangaku']);
            $this->createFixedInputs($sangaku, $validated['fixed_inputs'] ?? []);

            return $sangaku;
        });

        $sangaku->load(['fixedInputs', 'shrine']);
        $sangaku->setRelation('user', $request->user());

        return new SangakuResource($sangaku);
    }

    public function update(SangakuUpdateRequest $request, int $id)
    {
        /** @var Sangaku $sangaku */
        $sangaku = $request->user()->sangakus()->findOrFail($id);
        $validated = $request->validated();

        DB::transaction(function () use ($sangaku, $validated) {
            $sangaku->update($validated['sangaku'] ?? []);

            if (array_key_exists('fixed_inputs', $validated)) {
                $this->syncFixedInputs($sangaku, $validated['fixed_inputs']);
            }
        });

        // fixedInputs は差分同期で増減しているため、古いリレーションを持ち越さないよう読み直す
        $sangaku->load(['fixedInputs', 'shrine']);
        $sangaku->setRelation('user', $request->user());

        return new SangakuResource($sangaku);
    }

    public function destroy(Request $request, int $id)
    {
        $sangaku = $request->user()->sangakus()->findOrFail($id);
        $sangaku->delete();

        return response()->noContent();
    }

    /**
     * @param  array<int, string>  $contents
     */
    private function createFixedInputs(Sangaku $sangaku, array $contents): void
    {
        if ($contents === []) {
            return;
        }

        $sangaku->fixedInputs()->createMany(
            array_map(fn (string $content) => ['content' => $content], $contents)
        );
    }

    /**
     * content をキーに差分だけを反映する。
     *
     * 内容が変わっていない行は id と updated_at をそのまま保つため、全件を
     * 洗い替えせず、消えた content だけを削除し、増えた content だけを作成する。
     * その代わり並び順は id 順のままなので、content の並び替えだけを送っても
     * レスポンスの順序はリクエスト順には追従しない。
     *
     * @param  array<int, string>  $contents  リクエスト内で重複しない content の配列（distinct ルールで担保）
     */
    private function syncFixedInputs(Sangaku $sangaku, array $contents): void
    {
        $existing = $sangaku->fixedInputs()->pluck('content')->all();

        $removed = array_values(array_diff($existing, $contents));
        if ($removed !== []) {
            $sangaku->fixedInputs()->whereIn('content', $removed)->delete();
        }

        $this->createFixedInputs($sangaku, array_values(array_diff($contents, $existing)));
    }
}
