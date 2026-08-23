<?php

use App\Models\Sangaku;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

describe('SangakusController', function () {
    describe('GET /api/v1/sangakus/{id}', function () {
        test('ユーザーが他ユーザーのsangakuをJSON形式で取得できる', function () {
            $owner = User::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $owner->id]);

            $response = $this->getJson("/api/v1/sangakus/{$sangaku->id}");

            expect($response->status())->toBe(200);
            expect($response->json('data.id'))->toBe((string) $sangaku->id);
            expect($response->json('data.attributes.title'))->toBe($sangaku->title);
        });

        test('レスポンスにsource属性が含まれない', function () {
            $user = User::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $user->id]);

            Sanctum::actingAs($user);

            $response = $this->getJson("/api/v1/sangakus/{$sangaku->id}");

            expect($response->status())->toBe(200);
            expect($response->json('data.attributes.source'))->toBe(null);
        });

        test('存在しないIDの場合404を返す', function () {
            $user = User::factory()->create();
            Sanctum::actingAs($user);

            $response = $this->getJson('/api/v1/sangakus/999999');

            expect($response->status())->toBe(404);
        });
    });
});
