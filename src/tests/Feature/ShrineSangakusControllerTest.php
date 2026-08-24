<?php

use App\Models\Sangaku;
use App\Models\Shrine;

describe('ShrineSangakusController', function () {
    describe('GET /api/v1/shrines/{shrine}/sangakus', function () {
        test('認証不要で神社に紐づくsangaku一覧をJSON形式で取得できる', function () {
            $shrine = Shrine::factory()->create();
            $sangaku = Sangaku::factory()->create(['shrine_id' => $shrine->id, 'title' => 'test_title']);

            $response = $this->getJson("/api/v1/shrines/{$shrine->id}/sangakus");

            expect($response->status())->toBe(200);
            expect($response->json('data.0.id'))->toBe((string) $sangaku->id);
            expect($response->json('data.0.attributes.title'))->toBe($sangaku->title);
        });

        test('レスポンスにsource属性が含まれない', function () {
            $shrine = Shrine::factory()->create();
            Sangaku::factory()->create(['shrine_id' => $shrine->id]);

            $response = $this->getJson("/api/v1/shrines/{$shrine->id}/sangakus");

            expect($response->status())->toBe(200);
            expect($response->json('data.0.attributes.source'))->toBe(null);
        });

        test('別の神社に紐づくsangakuは含まれない', function () {
            $shrine = Shrine::factory()->create();
            $sangaku = Sangaku::factory()->create(['shrine_id' => $shrine->id]);

            $anotherShrine = Shrine::factory()->create();
            Sangaku::factory()->create(['shrine_id' => $anotherShrine->id]);

            $response = $this->getJson("/api/v1/shrines/{$shrine->id}/sangakus");

            expect($response->status())->toBe(200);
            expect($response->json('data'))->toHaveCount(1);
            expect($response->json('data.0.id'))->toBe((string) $sangaku->id);
        });

        test('存在しない神社IDの場合404を返す', function () {
            $response = $this->getJson('/api/v1/shrines/999999/sangakus');

            expect($response->status())->toBe(404);
        });
    });
});
