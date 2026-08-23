<?php

use App\Models\FixedInput;
use App\Models\Sangaku;
use App\Models\Shrine;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

describe('SangakusController', function () {
    describe('GET /api/v1/user/sangakus', function () {
        test('自分のsangaku一覧をJSON形式で取得できる（他ユーザー分は含まれない）', function () {
            $user = User::factory()->create();
            $shrine = Shrine::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $user->id, 'shrine_id' => $shrine->id]);

            $anotherUser = User::factory()->create();
            Sangaku::factory()->create(['user_id' => $anotherUser->id]);

            Sanctum::actingAs($user);

            $response = $this->getJson('/api/v1/user/sangakus');

            expect($response->status())->toBe(200);
            expect($response->json('data'))->toHaveCount(1);
            expect($response->json('data.0.id'))->toBe((string) $sangaku->id);
            expect($response->json('data.0.attributes.title'))->toBe($sangaku->title);
        });

        test('titleで検索できる', function () {
            $user = User::factory()->create();
            Sangaku::factory()->create(['user_id' => $user->id, 'title' => 'sample_title']);
            $anotherSangaku = Sangaku::factory()->create(['user_id' => $user->id, 'title' => 'another_title']);

            Sanctum::actingAs($user);

            $response = $this->getJson('/api/v1/user/sangakus?title=another');

            expect($response->status())->toBe(200);
            expect($response->json('data'))->toHaveCount(1);
            expect($response->json('data.0.id'))->toBe((string) $anotherSangaku->id);
            expect($response->json('data.0.attributes.title'))->toBe($anotherSangaku->title);
        });

        test('shrine_idで検索できる', function () {
            $user = User::factory()->create();
            $shrine = Shrine::factory()->create();
            Sangaku::factory()->create(['user_id' => $user->id, 'shrine_id' => $shrine->id]);

            $anotherShrine = Shrine::factory()->create(['name' => 'another_shrine']);
            $anotherSangaku = Sangaku::factory()->create([
                'user_id' => $user->id,
                'shrine_id' => $anotherShrine->id,
                'title' => 'another_shrine',
            ]);

            Sanctum::actingAs($user);

            $response = $this->getJson("/api/v1/user/sangakus?shrine_id={$anotherShrine->id}");

            expect($response->status())->toBe(200);
            expect($response->json('data'))->toHaveCount(1);
            expect($response->json('data.0.id'))->toBe((string) $anotherSangaku->id);
        });

        test('shrine_id=""で奉納前（shrine未設定）のsangakuに絞り込める', function () {
            $user = User::factory()->create();
            $shrine = Shrine::factory()->create();
            Sangaku::factory()->create(['user_id' => $user->id, 'shrine_id' => $shrine->id]);

            $beforeDedicate = Sangaku::factory()->create([
                'user_id' => $user->id,
                'shrine_id' => null,
                'title' => 'before_dedicate',
            ]);

            Sanctum::actingAs($user);

            $response = $this->getJson('/api/v1/user/sangakus?shrine_id=');

            expect($response->status())->toBe(200);
            expect($response->json('data'))->toHaveCount(1);
            expect($response->json('data.0.id'))->toBe((string) $beforeDedicate->id);
        });

        test('shrine_id="any"で奉納済み（shrine設定済み）のsangakuに絞り込める', function () {
            $user = User::factory()->create();
            $shrine = Shrine::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $user->id, 'shrine_id' => $shrine->id]);

            Sangaku::factory()->create([
                'user_id' => $user->id,
                'shrine_id' => null,
                'title' => 'before_dedicate',
            ]);

            Sanctum::actingAs($user);

            $response = $this->getJson('/api/v1/user/sangakus?shrine_id=any');

            expect($response->status())->toBe(200);
            expect($response->json('data'))->toHaveCount(1);
            expect($response->json('data.0.id'))->toBe((string) $sangaku->id);
        });

        test('titleが不正なUTF-8バイト列でも500にならない', function () {
            $user = User::factory()->create();
            Sangaku::factory()->create(['user_id' => $user->id]);

            Sanctum::actingAs($user);

            // "\xC3\x28" は UTF-8 として不正な並び。preg_split('/\s+/u') が false を返す
            $response = $this->getJson('/api/v1/user/sangakus?title=%C3%28');

            expect($response->status())->toBe(200);
        });

        test('shrine_idが"any"でも数値でもない場合400を返す', function () {
            $user = User::factory()->create();
            Sangaku::factory()->create(['user_id' => $user->id, 'shrine_id' => null]);

            Sanctum::actingAs($user);

            $response = $this->getJson('/api/v1/user/sangakus?shrine_id=foo');

            expect($response->status())->toBe(400);
            expect($response->json('errors'))->toHaveKey('shrine_id');
        });
    });

    describe('POST /api/v1/user/sangakus', function () {
        test('認証済みユーザーがsangakuを作成できる', function () {
            $user = User::factory()->create();
            Sanctum::actingAs($user);

            $countBefore = Sangaku::count();

            $params = [
                'sangaku' => [
                    'title' => 'test_title',
                    'description' => 'test_description',
                    'source' => "puts 'Hello world'",
                    'difficulty' => 0,
                ],
                'fixed_inputs' => ['test_input_1'],
            ];

            $response = $this->postJson('/api/v1/user/sangakus', $params);

            expect($response->status())->toBe(201);
            expect(Sangaku::count())->toBe($countBefore + 1);
        });

        test('fixed_inputsが内容と順序を保ったまま保存される', function () {
            $user = User::factory()->create();
            Sanctum::actingAs($user);

            $params = [
                'sangaku' => [
                    'title' => 'test_title',
                    'description' => 'test_description',
                    'source' => "puts 'Hello world'",
                    'difficulty' => 0,
                ],
                'fixed_inputs' => ['first', 'second', 'third'],
            ];

            $response = $this->postJson('/api/v1/user/sangakus', $params);

            expect($response->status())->toBe(201);

            $sangaku = Sangaku::latest('id')->first();
            expect($sangaku->fixedInputs->pluck('content')->all())->toBe(['first', 'second', 'third']);
            expect(array_column($response->json('data.attributes.inputs'), 'content'))
                ->toBe(['first', 'second', 'third']);
        });

        test('titleが未指定の場合400を返し、作成されない', function () {
            $user = User::factory()->create();
            Sanctum::actingAs($user);

            $countBefore = Sangaku::count();

            $response = $this->postJson('/api/v1/user/sangakus', [
                'sangaku' => [
                    'description' => 'test_description',
                    'source' => "puts 'Hello world'",
                    'difficulty' => 0,
                ],
            ]);

            expect($response->status())->toBe(400);
            expect($response->json('errors'))->toHaveKey('sangaku.title');
            expect(Sangaku::count())->toBe($countBefore);
        });

        test('difficultyがEnumの範囲外の場合400を返す', function () {
            $user = User::factory()->create();
            Sanctum::actingAs($user);

            $response = $this->postJson('/api/v1/user/sangakus', [
                'sangaku' => [
                    'title' => 'test_title',
                    'description' => 'test_description',
                    'source' => "puts 'Hello world'",
                    'difficulty' => 99,
                ],
            ]);

            expect($response->status())->toBe(400);
            expect($response->json('errors'))->toHaveKey('sangaku.difficulty');
        });

        test('fixed_inputsに重複した値がある場合400を返す', function () {
            $user = User::factory()->create();
            Sanctum::actingAs($user);

            $response = $this->postJson('/api/v1/user/sangakus', [
                'sangaku' => [
                    'title' => 'test_title',
                    'description' => 'test_description',
                    'source' => "puts 'Hello world'",
                    'difficulty' => 0,
                ],
                'fixed_inputs' => ['same', 'same'],
            ]);

            expect($response->status())->toBe(400);
            expect($response->json('errors'))->toHaveKey('fixed_inputs.0');
        });

        test('fixed_inputsが51件以上の場合400を返す', function () {
            $user = User::factory()->create();
            Sanctum::actingAs($user);

            $response = $this->postJson('/api/v1/user/sangakus', [
                'sangaku' => [
                    'title' => 'test_title',
                    'description' => 'test_description',
                    'source' => "puts 'Hello world'",
                    'difficulty' => 0,
                ],
                'fixed_inputs' => array_map(fn (int $i) => "input_{$i}", range(1, 51)),
            ]);

            expect($response->status())->toBe(400);
            expect($response->json('errors'))->toHaveKey('fixed_inputs');
        });

        test('fixed_inputsがカラム長（255文字）を超える場合、500ではなく400を返す', function () {
            $user = User::factory()->create();
            Sanctum::actingAs($user);

            $countBefore = Sangaku::count();

            $response = $this->postJson('/api/v1/user/sangakus', [
                'sangaku' => [
                    'title' => 'test_title',
                    'description' => 'test_description',
                    'source' => "puts 'Hello world'",
                    'difficulty' => 0,
                ],
                'fixed_inputs' => [str_repeat('a', 256)],
            ]);

            expect($response->status())->toBe(400);
            expect($response->json('errors'))->toHaveKey('fixed_inputs.0');
            expect(Sangaku::count())->toBe($countBefore);
        });
    });

    describe('GET /api/v1/user/sangakus/{id}', function () {
        test('認証済みユーザーが自分のsangakuを取得できる', function () {
            $user = User::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $user->id]);

            Sanctum::actingAs($user);

            $response = $this->getJson("/api/v1/user/sangakus/{$sangaku->id}");

            expect($response->status())->toBe(200);
            expect($response->json('data.id'))->toBe((string) $sangaku->id);
        });
    });

    describe('PATCH /api/v1/user/sangakus/{id}', function () {
        test('認証済みユーザーが自分のsangakuを更新できる', function () {
            $user = User::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $user->id, 'title' => 'before_changed']);

            Sanctum::actingAs($user);

            $params = [
                'sangaku' => [
                    'title' => 'changed_title',
                    'description' => $sangaku->description,
                    'source' => $sangaku->source,
                    'difficulty' => $sangaku->difficulty->value,
                ],
                'fixed_inputs' => ['a'],
            ];

            $response = $this->patchJson("/api/v1/user/sangakus/{$sangaku->id}", $params);

            expect($response->status())->toBe(200);
            expect($response->json('data.attributes.title'))->toBe('changed_title');
        });

        test('fixed_inputsが洗い替えされ、古い値が残らない', function () {
            $user = User::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $user->id]);
            $sangaku->fixedInputs()->createMany([
                ['content' => 'old_1'],
                ['content' => 'old_2'],
            ]);

            Sanctum::actingAs($user);

            $response = $this->patchJson("/api/v1/user/sangakus/{$sangaku->id}", [
                'sangaku' => ['title' => 'changed_title'],
                'fixed_inputs' => ['new_1', 'new_2', 'new_3'],
            ]);

            expect($response->status())->toBe(200);
            expect($sangaku->fixedInputs()->pluck('content')->all())->toBe(['new_1', 'new_2', 'new_3']);
            expect(FixedInput::where('sangaku_id', $sangaku->id)->count())->toBe(3);
            expect(array_column($response->json('data.attributes.inputs'), 'content'))
                ->toBe(['new_1', 'new_2', 'new_3']);
        });

        test('sangakuに未知のキーしか含まない場合、500ではなく400を返す', function () {
            $user = User::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $user->id, 'title' => 'before_changed']);

            Sanctum::actingAs($user);

            $response = $this->patchJson("/api/v1/user/sangakus/{$sangaku->id}", [
                'sangaku' => ['unknown_key' => 'value'],
            ]);

            expect($response->status())->toBe(400);
            expect($sangaku->fresh()->title)->toBe('before_changed');
        });

        test('存在しないidの場合404を返す', function () {
            $user = User::factory()->create();
            Sanctum::actingAs($user);

            $params = [
                'sangaku' => ['title' => 'changed_title'],
            ];

            $response = $this->patchJson('/api/v1/user/sangakus/1000000', $params);

            expect($response->status())->toBe(404);
        });

        test('他ユーザーのsangaku idの場合404を返す', function () {
            $user = User::factory()->create();
            $anotherUser = User::factory()->create();
            $anotherSangaku = Sangaku::factory()->create(['user_id' => $anotherUser->id]);

            Sanctum::actingAs($user);

            $params = [
                'sangaku' => ['title' => 'changed_title'],
            ];

            $response = $this->patchJson("/api/v1/user/sangakus/{$anotherSangaku->id}", $params);

            expect($response->status())->toBe(404);
        });
    });

    describe('DELETE /api/v1/user/sangakus/{id}', function () {
        test('認証済みユーザーが自分のsangakuを削除できる', function () {
            $user = User::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $user->id]);

            Sanctum::actingAs($user);

            $countBefore = Sangaku::count();

            $response = $this->deleteJson("/api/v1/user/sangakus/{$sangaku->id}");

            expect($response->status())->toBe(204);
            expect(Sangaku::count())->toBe($countBefore - 1);
        });

        test('存在しないidの場合404を返し、件数は変化しない', function () {
            $user = User::factory()->create();
            Sangaku::factory()->create(['user_id' => $user->id]);

            Sanctum::actingAs($user);

            $countBefore = Sangaku::count();

            $response = $this->deleteJson('/api/v1/user/sangakus/1000000');

            expect($response->status())->toBe(404);
            expect(Sangaku::count())->toBe($countBefore);
        });

        test('他ユーザーのsangakuの場合404を返し、件数は変化しない', function () {
            $user = User::factory()->create();
            $anotherUser = User::factory()->create();
            $anotherSangaku = Sangaku::factory()->create(['user_id' => $anotherUser->id]);

            Sanctum::actingAs($user);

            $countBefore = Sangaku::count();

            $response = $this->deleteJson("/api/v1/user/sangakus/{$anotherSangaku->id}");

            expect($response->status())->toBe(404);
            expect(Sangaku::count())->toBe($countBefore);
        });
    });
});
