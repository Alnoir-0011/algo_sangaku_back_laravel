<?php

use App\Models\Sangaku;
use App\Models\User;
use App\Models\UserSangakuSave;
use Laravel\Sanctum\Sanctum;

describe('SavedSangakuIdsController', function () {
    describe('GET /api/v1/user/saved_sangaku_ids', function () {
        test('一部のsangakuのみ保存済みの場合、保存済みのidのみ返す', function () {
            $user = User::factory()->create();
            $author = User::factory()->create();
            $sangakuA = Sangaku::factory()->create(['user_id' => $author->id]);
            $sangakuB = Sangaku::factory()->create(['user_id' => $author->id]);
            $sangakuC = Sangaku::factory()->create(['user_id' => $author->id]);
            UserSangakuSave::query()->create(['user_id' => $user->id, 'sangaku_id' => $sangakuA->id]);

            Sanctum::actingAs($user);

            $query = http_build_query(['sangaku_ids' => [$sangakuA->id, $sangakuB->id, $sangakuC->id]]);
            $response = $this->getJson("/api/v1/user/saved_sangaku_ids?{$query}");

            expect($response->status())->toBe(200);
            expect($response->json('saved_sangaku_ids'))->toBe([$sangakuA->id]);
        });

        test('リクエストしたsangakuが全て保存済みの場合、全てのidを返す', function () {
            $user = User::factory()->create();
            $author = User::factory()->create();
            $sangakuA = Sangaku::factory()->create(['user_id' => $author->id]);
            $sangakuB = Sangaku::factory()->create(['user_id' => $author->id]);
            UserSangakuSave::query()->create(['user_id' => $user->id, 'sangaku_id' => $sangakuA->id]);
            UserSangakuSave::query()->create(['user_id' => $user->id, 'sangaku_id' => $sangakuB->id]);

            Sanctum::actingAs($user);

            $query = http_build_query(['sangaku_ids' => [$sangakuA->id, $sangakuB->id]]);
            $response = $this->getJson("/api/v1/user/saved_sangaku_ids?{$query}");

            expect($response->status())->toBe(200);
            $ids = $response->json('saved_sangaku_ids');
            sort($ids);
            $expected = [$sangakuA->id, $sangakuB->id];
            sort($expected);
            expect($ids)->toBe($expected);
        });

        test('リクエストしたsangakuが1件も保存されていない場合、空配列を返す', function () {
            $user = User::factory()->create();
            $author = User::factory()->create();
            $sangakuB = Sangaku::factory()->create(['user_id' => $author->id]);
            $sangakuC = Sangaku::factory()->create(['user_id' => $author->id]);

            Sanctum::actingAs($user);

            $query = http_build_query(['sangaku_ids' => [$sangakuB->id, $sangakuC->id]]);
            $response = $this->getJson("/api/v1/user/saved_sangaku_ids?{$query}");

            expect($response->status())->toBe(200);
            expect($response->json('saved_sangaku_ids'))->toBe([]);
        });

        test('他ユーザーが保存したsangakuは含まれない', function () {
            $user = User::factory()->create();
            $author = User::factory()->create();
            $otherUser = User::factory()->create();
            $sangakuA = Sangaku::factory()->create(['user_id' => $author->id]);
            $sangakuB = Sangaku::factory()->create(['user_id' => $author->id]);
            UserSangakuSave::query()->create(['user_id' => $otherUser->id, 'sangaku_id' => $sangakuB->id]);

            Sanctum::actingAs($user);

            $query = http_build_query(['sangaku_ids' => [$sangakuA->id, $sangakuB->id]]);
            $response = $this->getJson("/api/v1/user/saved_sangaku_ids?{$query}");

            expect($response->status())->toBe(200);
            expect($response->json('saved_sangaku_ids'))->not->toContain($sangakuB->id);
        });

        test('sangaku_idsパラメータが未指定の場合、エラーにならず空配列を返す', function () {
            $user = User::factory()->create();

            Sanctum::actingAs($user);

            $response = $this->getJson('/api/v1/user/saved_sangaku_ids');

            expect($response->status())->toBe(200);
            expect($response->json('saved_sangaku_ids'))->toBe([]);
        });

        test('sangaku_idsに存在しないidが含まれていても無視される', function () {
            $user = User::factory()->create();
            $author = User::factory()->create();
            $sangakuA = Sangaku::factory()->create(['user_id' => $author->id]);
            UserSangakuSave::query()->create(['user_id' => $user->id, 'sangaku_id' => $sangakuA->id]);

            Sanctum::actingAs($user);

            $query = http_build_query(['sangaku_ids' => [$sangakuA->id, 999999]]);
            $response = $this->getJson("/api/v1/user/saved_sangaku_ids?{$query}");

            expect($response->status())->toBe(200);
            expect($response->json('saved_sangaku_ids'))->toBe([$sangakuA->id]);
        });

        test('sangaku_idsに数値以外の値が含まれていてもエラーにならず、有効なidのみ返す', function () {
            $user = User::factory()->create();
            $author = User::factory()->create();
            $sangakuA = Sangaku::factory()->create(['user_id' => $author->id]);
            UserSangakuSave::query()->create(['user_id' => $user->id, 'sangaku_id' => $sangakuA->id]);

            Sanctum::actingAs($user);

            $query = http_build_query(['sangaku_ids' => [$sangakuA->id, 'abc']]);
            $response = $this->getJson("/api/v1/user/saved_sangaku_ids?{$query}");

            expect($response->status())->toBe(200);
            expect($response->json('saved_sangaku_ids'))->toBe([$sangakuA->id]);
        });

        test('sangaku_idsが上限件数を超える場合、切り詰められエラーにならない', function () {
            $user = User::factory()->create();
            $author = User::factory()->create();
            $sangakuA = Sangaku::factory()->create(['user_id' => $author->id]);
            UserSangakuSave::query()->create(['user_id' => $user->id, 'sangaku_id' => $sangakuA->id]);

            Sanctum::actingAs($user);

            // 上限（Rails版は Pagy::DEFAULT[:limit]、通常20〜数十件程度）を確実に超える
            // 200件のダミーidの末尾に有効なidを追加する。切り詰めにより有効なidが
            // 落とされ、結果は空配列になることを期待する。
            // ダミーidはテストDBの自動採番と衝突しないよう十分大きい範囲にする。
            $ids = array_merge(range(1_000_000, 1_000_199), [$sangakuA->id]);
            $query = http_build_query(['sangaku_ids' => $ids]);
            $response = $this->getJson("/api/v1/user/saved_sangaku_ids?{$query}");

            expect($response->status())->toBe(200);
            expect($response->json('saved_sangaku_ids'))->toBe([]);
        });

        test('未認証の場合401を返す', function () {
            $user = User::factory()->create();
            $author = User::factory()->create();
            $sangakuA = Sangaku::factory()->create(['user_id' => $author->id]);

            $query = http_build_query(['sangaku_ids' => [$sangakuA->id]]);
            $response = $this->getJson("/api/v1/user/saved_sangaku_ids?{$query}");

            expect($response->status())->toBe(401);
        });
    });
});
