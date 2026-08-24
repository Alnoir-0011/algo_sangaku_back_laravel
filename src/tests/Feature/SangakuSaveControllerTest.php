<?php

use App\Models\Sangaku;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

describe('SangakuSaveController', function () {
    describe('POST /api/v1/sangakus/{sangaku}/save', function () {
        test('認証済みユーザーが算額を保存でき、保存件数が1件増える', function () {
            $user = User::factory()->create();
            $sangaku = Sangaku::factory()->create();

            Sanctum::actingAs($user);

            $countBefore = $user->savedSangakus()->count();

            $response = $this->postJson("/api/v1/sangakus/{$sangaku->id}/save");

            expect($response->status())->toBe(200);
            expect($user->savedSangakus()->count())->toBe($countBefore + 1);
            expect($user->savedSangakus()->pluck('sangakus.id')->all())->toBe([$sangaku->id]);
        });

        test('レスポンスが保存した算額をJSON形式で返す', function () {
            $user = User::factory()->create();
            $sangaku = Sangaku::factory()->create();

            Sanctum::actingAs($user);

            $response = $this->postJson("/api/v1/sangakus/{$sangaku->id}/save");

            expect($response->status())->toBe(200);
            expect($response->json('data.id'))->toBe((string) $sangaku->id);
            expect($response->json('data.type'))->toBe('sangaku');
            expect($response->json('data.attributes.title'))->toBe($sangaku->title);
        });

        test('レスポンスにsource属性が含まれない', function () {
            $user = User::factory()->create();
            $sangaku = Sangaku::factory()->create(['source' => "puts 'secret answer'"]);

            Sanctum::actingAs($user);

            $response = $this->postJson("/api/v1/sangakus/{$sangaku->id}/save");

            expect($response->status())->toBe(200);
            expect($response->json('data.attributes.source'))->toBe(null);
            expect($response->getContent())->not->toContain('secret answer');
        });

        test('自分が投稿した算額でも保存できる', function () {
            $user = User::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $user->id]);

            Sanctum::actingAs($user);

            $response = $this->postJson("/api/v1/sangakus/{$sangaku->id}/save");

            expect($response->status())->toBe(200);
            expect($user->savedSangakus()->count())->toBe(1);
        });

        test('既に保存済みの算額を再度保存した場合409を返し、保存件数は増えない', function () {
            $user = User::factory()->create();
            $sangaku = Sangaku::factory()->create();

            Sanctum::actingAs($user);

            $this->postJson("/api/v1/sangakus/{$sangaku->id}/save");
            $countBefore = $user->savedSangakus()->count();

            $response = $this->postJson("/api/v1/sangakus/{$sangaku->id}/save");

            expect($response->status())->toBe(409);
            expect($user->savedSangakus()->count())->toBe($countBefore);
        });

        test('別のユーザーが同じ算額を保存できる', function () {
            $sangaku = Sangaku::factory()->create();

            $user = User::factory()->create();
            Sanctum::actingAs($user);
            $this->postJson("/api/v1/sangakus/{$sangaku->id}/save");

            $anotherUser = User::factory()->create();
            Sanctum::actingAs($anotherUser);

            $response = $this->postJson("/api/v1/sangakus/{$sangaku->id}/save");

            expect($response->status())->toBe(200);
            expect($anotherUser->savedSangakus()->count())->toBe(1);
            expect($sangaku->savedByUsers()->count())->toBe(2);
        });

        test('1人のユーザーが複数の算額を保存できる', function () {
            $user = User::factory()->create();
            $sangakus = Sangaku::factory()->count(2)->create();

            Sanctum::actingAs($user);

            foreach ($sangakus as $sangaku) {
                expect($this->postJson("/api/v1/sangakus/{$sangaku->id}/save")->status())->toBe(200);
            }

            expect($user->savedSangakus()->count())->toBe(2);
        });

        test('未認証の場合401を返し、保存されない', function () {
            $sangaku = Sangaku::factory()->create();

            $response = $this->postJson("/api/v1/sangakus/{$sangaku->id}/save");

            expect($response->status())->toBe(401);
            expect($sangaku->savedByUsers()->count())->toBe(0);
        });

        test('存在しないsangaku_idの場合404を返す', function () {
            $user = User::factory()->create();
            Sanctum::actingAs($user);

            $response = $this->postJson('/api/v1/sangakus/999999/save');

            expect($response->status())->toBe(404);
            expect($user->savedSangakus()->count())->toBe(0);
        });

        test('数値以外のidの場合、500ではなく404を返す', function () {
            $user = User::factory()->create();
            Sanctum::actingAs($user);

            $response = $this->postJson('/api/v1/sangakus/abc/save');

            expect($response->status())->toBe(404);
        });

        test('bigintに収まらないidでも500にならず404を返す', function () {
            $user = User::factory()->create();
            Sanctum::actingAs($user);

            $response = $this->postJson('/api/v1/sangakus/99999999999999999999/save');

            expect($response->status())->toBe(404);
        });
    });
});
