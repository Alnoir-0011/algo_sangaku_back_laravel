<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\V1\BaseController;
use App\Http\Requests\SangakuIndexRequest;
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
            ->with(['user', 'fixedInputs', 'shrine'])
            ->orderBy('id')
            ->paginate(config('sangaku.per_page'));

        return SangakuResource::collection($sangakus);
    }

    public function show(Request $request, int $id)
    {
        $sangaku = $request->user()->sangakus()->with(['user', 'fixedInputs', 'shrine'])->findOrFail($id);

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

        $sangaku->load(['user', 'fixedInputs', 'shrine']);

        return new SangakuResource($sangaku);
    }

    public function update(SangakuUpdateRequest $request, int $id)
    {
        /** @var Sangaku $sangaku */
        $sangaku = $request->user()->sangakus()->findOrFail($id);
        $validated = $request->validated();

        DB::transaction(function () use ($sangaku, $validated) {
            $sangaku->update($validated['sangaku']);

            if (array_key_exists('fixed_inputs', $validated)) {
                $sangaku->fixedInputs()->delete();
                $this->createFixedInputs($sangaku, $validated['fixed_inputs']);
            }
        });

        // fixedInputs は洗い替え済みのため、古いリレーションを持ち越さないよう読み直す
        $sangaku->load(['user', 'fixedInputs', 'shrine']);

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
        $sangaku->fixedInputs()->createMany(
            array_map(fn (string $content) => ['content' => $content], $contents)
        );
    }
}
