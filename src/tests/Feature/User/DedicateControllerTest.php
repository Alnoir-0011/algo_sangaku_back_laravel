<?php

use App\Models\Sangaku;
use App\Models\Shrine;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

describe('DedicateController', function () {
    describe('POST /api/v1/user/sangakus/{id}/dedicate', function () {
        test('神社に近い座標なら自分のsangakuを奉納でき、shrine_idが設定される', function () {
            $user = User::factory()->create();
            $shrine = Shrine::factory()->create(['latitude' => 35.70204829610801, 'longitude' => 139.76789333814216]);
            $sangaku = Sangaku::factory()->create(['user_id' => $user->id, 'shrine_id' => null]);

            Sanctum::actingAs($user);

            $params = [
                'shrine_id' => $shrine->id,
                'lat' => 35.70204829610801,
                'lng' => 139.76789333814216,
            ];

            $response = $this->postJson("/api/v1/user/sangakus/{$sangaku->id}/dedicate", $params);

            expect($response->status())->toBe(200);
            expect($sangaku->fresh()->shrine_id)->toBe($shrine->id);
        });

        test('神社から明らかに離れた座標の場合400を返し、shrine_idは更新されない', function () {
            $user = User::factory()->create();
            $shrine = Shrine::factory()->create(['latitude' => 35.70204829610801, 'longitude' => 139.76789333814216]);
            $sangaku = Sangaku::factory()->create(['user_id' => $user->id, 'shrine_id' => null]);

            Sanctum::actingAs($user);

            $params = [
                'shrine_id' => $shrine->id,
                'lat' => 43.06866357653171,
                'lng' => 141.35067826706085,
            ];

            $response = $this->postJson("/api/v1/user/sangakus/{$sangaku->id}/dedicate", $params);

            expect($response->status())->toBe(400);
            expect($sangaku->fresh()->shrine_id)->toBeNull();
        });

        test('奉納済みのsangakuを別の神社に再奉納しようとすると409を返し、shrine_idは変わらない', function () {
            $user = User::factory()->create();
            $dedicatedShrine = Shrine::factory()->create(['latitude' => 35.70204829610801, 'longitude' => 139.76789333814216]);
            $anotherShrine = Shrine::factory()->create(['latitude' => 43.06866357653171, 'longitude' => 141.35067826706085]);
            $sangaku = Sangaku::factory()->create(['user_id' => $user->id, 'shrine_id' => $dedicatedShrine->id]);

            Sanctum::actingAs($user);

            $params = [
                'shrine_id' => $anotherShrine->id,
                'lat' => 43.06866357653171,
                'lng' => 141.35067826706085,
            ];

            $response = $this->postJson("/api/v1/user/sangakus/{$sangaku->id}/dedicate", $params);

            expect($response->status())->toBe(409);
            expect($sangaku->fresh()->shrine_id)->toBe($dedicatedShrine->id);
        });

        test('奉納済みのsangakuを同じ神社に再奉納しようとしても409を返す', function () {
            $user = User::factory()->create();
            $shrine = Shrine::factory()->create(['latitude' => 35.70204829610801, 'longitude' => 139.76789333814216]);
            $sangaku = Sangaku::factory()->create(['user_id' => $user->id, 'shrine_id' => $shrine->id]);

            Sanctum::actingAs($user);

            $params = [
                'shrine_id' => $shrine->id,
                'lat' => 35.70204829610801,
                'lng' => 139.76789333814216,
            ];

            $response = $this->postJson("/api/v1/user/sangakus/{$sangaku->id}/dedicate", $params);

            expect($response->status())->toBe(409);
            expect($sangaku->fresh()->shrine_id)->toBe($shrine->id);
        });

        test('奉納済みかつ神社から離れた座標の場合、距離エラーより先に409を返す', function () {
            $user = User::factory()->create();
            $dedicatedShrine = Shrine::factory()->create(['latitude' => 35.70204829610801, 'longitude' => 139.76789333814216]);
            $anotherShrine = Shrine::factory()->create(['latitude' => 43.06866357653171, 'longitude' => 141.35067826706085]);
            $sangaku = Sangaku::factory()->create(['user_id' => $user->id, 'shrine_id' => $dedicatedShrine->id]);

            Sanctum::actingAs($user);

            $params = [
                'shrine_id' => $anotherShrine->id,
                'lat' => 34.69373568343073,
                'lng' => 135.50230145178906,
            ];

            $response = $this->postJson("/api/v1/user/sangakus/{$sangaku->id}/dedicate", $params);

            expect($response->status())->toBe(409);
            expect($sangaku->fresh()->shrine_id)->toBe($dedicatedShrine->id);
        });

        test('他ユーザーのsangakuの場合404を返す', function () {
            $user = User::factory()->create();
            $anotherUser = User::factory()->create();
            $shrine = Shrine::factory()->create();
            $anotherSangaku = Sangaku::factory()->create(['user_id' => $anotherUser->id, 'shrine_id' => null]);

            Sanctum::actingAs($user);

            $params = [
                'shrine_id' => $shrine->id,
                'lat' => $shrine->latitude,
                'lng' => $shrine->longitude,
            ];

            $response = $this->postJson("/api/v1/user/sangakus/{$anotherSangaku->id}/dedicate", $params);

            expect($response->status())->toBe(404);
        });

        test('存在しないsangaku idの場合404を返す', function () {
            $user = User::factory()->create();
            $shrine = Shrine::factory()->create();

            Sanctum::actingAs($user);

            $params = [
                'shrine_id' => $shrine->id,
                'lat' => $shrine->latitude,
                'lng' => $shrine->longitude,
            ];

            $response = $this->postJson('/api/v1/user/sangakus/999999/dedicate', $params);

            expect($response->status())->toBe(404);
        });

        test('取得後に別リクエストが先に奉納した場合でも409を返し、上書きされない', function () {
            $user = User::factory()->create();
            $dedicatedShrine = Shrine::factory()->create(['latitude' => 35.70204829610801, 'longitude' => 139.76789333814216]);
            $anotherShrine = Shrine::factory()->create(['latitude' => 35.70204829610801, 'longitude' => 139.76789333814216]);
            $sangaku = Sangaku::factory()->create(['user_id' => $user->id, 'shrine_id' => null]);

            Sanctum::actingAs($user);

            // コントローラが算額を取得した直後に、別リクエストが先に奉納を終えた状況を再現する。
            // モデルイベントを介さない直接 UPDATE なので、取得済みインスタンスは未奉納のまま。
            Sangaku::retrieved(function (Sangaku $retrieved) use ($sangaku, $dedicatedShrine) {
                if ($retrieved->id === $sangaku->id && $retrieved->shrine_id === null) {
                    DB::table('sangakus')
                        ->where('id', $sangaku->id)
                        ->update(['shrine_id' => $dedicatedShrine->id]);
                }
            });

            $params = [
                'shrine_id' => $anotherShrine->id,
                'lat' => 35.70204829610801,
                'lng' => 139.76789333814216,
            ];

            $response = $this->postJson("/api/v1/user/sangakus/{$sangaku->id}/dedicate", $params);

            expect($response->status())->toBe(409);
            expect($sangaku->fresh()->shrine_id)->toBe($dedicatedShrine->id);
        });

        test('shrine_idが未指定の場合400を返す', function () {
            $user = User::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $user->id, 'shrine_id' => null]);

            Sanctum::actingAs($user);

            $response = $this->postJson("/api/v1/user/sangakus/{$sangaku->id}/dedicate", [
                'lat' => 35.70204829610801,
                'lng' => 139.76789333814216,
            ]);

            expect($response->status())->toBe(400);
            expect($response->json('errors'))->toHaveKey('shrine_id');
            expect($sangaku->fresh()->shrine_id)->toBeNull();
        });

        test('存在しないshrine_idの場合400を返す', function () {
            $user = User::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $user->id, 'shrine_id' => null]);

            Sanctum::actingAs($user);

            $response = $this->postJson("/api/v1/user/sangakus/{$sangaku->id}/dedicate", [
                'shrine_id' => 999999,
                'lat' => 35.70204829610801,
                'lng' => 139.76789333814216,
            ]);

            expect($response->status())->toBe(400);
            expect($response->json('errors'))->toHaveKey('shrine_id');
        });

        test('lat/lngが未指定の場合400を返す', function () {
            $user = User::factory()->create();
            $shrine = Shrine::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $user->id, 'shrine_id' => null]);

            Sanctum::actingAs($user);

            $response = $this->postJson("/api/v1/user/sangakus/{$sangaku->id}/dedicate", [
                'shrine_id' => $shrine->id,
            ]);

            expect($response->status())->toBe(400);
            expect($response->json('errors'))->toHaveKeys(['lat', 'lng']);
        });

        test('緯度経度が範囲外の場合400を返す', function () {
            $user = User::factory()->create();
            $shrine = Shrine::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $user->id, 'shrine_id' => null]);

            Sanctum::actingAs($user);

            $response = $this->postJson("/api/v1/user/sangakus/{$sangaku->id}/dedicate", [
                'shrine_id' => $shrine->id,
                'lat' => 91,
                'lng' => 181,
            ]);

            expect($response->status())->toBe(400);
            expect($response->json('errors'))->toHaveKeys(['lat', 'lng']);
            expect($sangaku->fresh()->shrine_id)->toBeNull();
        });

        test('lat/lngが数値でない場合400を返す', function () {
            $user = User::factory()->create();
            $shrine = Shrine::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $user->id, 'shrine_id' => null]);

            Sanctum::actingAs($user);

            $response = $this->postJson("/api/v1/user/sangakus/{$sangaku->id}/dedicate", [
                'shrine_id' => $shrine->id,
                'lat' => 'not-a-number',
                'lng' => 'not-a-number',
            ]);

            expect($response->status())->toBe(400);
            expect($response->json('errors'))->toHaveKeys(['lat', 'lng']);
        });

        test('未認証の場合401を返す', function () {
            $shrine = Shrine::factory()->create();
            $sangaku = Sangaku::factory()->create(['shrine_id' => null]);

            $params = [
                'shrine_id' => $shrine->id,
                'lat' => $shrine->latitude,
                'lng' => $shrine->longitude,
            ];

            $response = $this->postJson("/api/v1/user/sangakus/{$sangaku->id}/dedicate", $params);

            expect($response->status())->toBe(401);
        });
    });
});
