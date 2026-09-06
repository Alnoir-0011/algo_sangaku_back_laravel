<?php

use App\Models\Answer;
use App\Models\Sangaku;
use App\Models\Shrine;
use App\Models\User;
use App\Models\UserSangakuSave;

describe('ProfileController', function () {
    describe('GET /api/v1/profiles/{id}', function () {
        test('認証なしでアクセスでき、200を返す', function () {
            $user = User::factory()->create();

            $response = $this->getJson("/api/v1/profiles/{$user->id}");

            expect($response->status())->toBe(200);
        });

        test('idとtypeとnicknameと登録日を返す', function () {
            $user = User::factory()->create();

            $response = $this->getJson("/api/v1/profiles/{$user->id}");

            expect($response->json('data.id'))->toBe((string) $user->id);
            expect($response->json('data.type'))->toBe('profile');
            expect($response->json('data.attributes.nickname'))->toBe($user->nickname);
            expect($response->json('data.attributes.created_at'))->not->toBeNull();
        });

        test('emailを返さない', function () {
            $user = User::factory()->create();

            $response = $this->getJson("/api/v1/profiles/{$user->id}");

            expect($response->json('data.attributes'))->not->toHaveKey('email');
        });

        test('sangaku_countはそのユーザーが作成した算額の総数を返す', function () {
            $user = User::factory()->create();
            Sangaku::factory()->count(2)->create(['user_id' => $user->id]);

            $response = $this->getJson("/api/v1/profiles/{$user->id}");

            expect($response->json('data.attributes.sangaku_count'))->toBe(2);
        });

        test('dedicated_sangaku_countは奉納済み（shrine_idが非null）の算額数のみを返す', function () {
            $user = User::factory()->create();
            $shrine = Shrine::factory()->create();
            Sangaku::factory()->create(['user_id' => $user->id, 'shrine_id' => $shrine->id]);
            Sangaku::factory()->create(['user_id' => $user->id, 'shrine_id' => null]);

            $response = $this->getJson("/api/v1/profiles/{$user->id}");

            expect($response->json('data.attributes.dedicated_sangaku_count'))->toBe(1);
        });

        test('dedicated_sangakusは奉納済みの算額のみをtitle・shrine_name付きで返す', function () {
            $user = User::factory()->create();
            $shrine = Shrine::factory()->create();
            $dedicated = Sangaku::factory()->create(['user_id' => $user->id, 'shrine_id' => $shrine->id]);
            Sangaku::factory()->create(['user_id' => $user->id, 'shrine_id' => null]);

            $response = $this->getJson("/api/v1/profiles/{$user->id}");

            $data = $response->json('data.attributes.dedicated_sangakus');
            expect($data)->toBe([
                ['id' => $dedicated->id, 'title' => $dedicated->title, 'shrine_name' => $shrine->name],
            ]);
        });

        test('show_answer_countがtrueのユーザーはanswer_countを返す', function () {
            $user = User::factory()->create(['show_answer_count' => true]);
            $author = User::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $author->id]);
            $userSangakuSave = UserSangakuSave::query()->create(['user_id' => $user->id, 'sangaku_id' => $sangaku->id]);
            Answer::withoutEvents(fn () => Answer::query()->forceCreate([
                'user_sangaku_save_id' => $userSangakuSave->id,
                'source' => "puts 'Hello world'",
            ]));

            $response = $this->getJson("/api/v1/profiles/{$user->id}");

            expect($response->json('data.attributes.answer_count'))->toBe(1);
        });

        test('show_answer_countがfalseのユーザーはanswer_countがnullになる', function () {
            $user = User::factory()->create(['show_answer_count' => false]);
            $author = User::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $author->id]);
            $userSangakuSave = UserSangakuSave::query()->create(['user_id' => $user->id, 'sangaku_id' => $sangaku->id]);
            Answer::withoutEvents(fn () => Answer::query()->forceCreate([
                'user_sangaku_save_id' => $userSangakuSave->id,
                'source' => "puts 'Hello world'",
            ]));

            $response = $this->getJson("/api/v1/profiles/{$user->id}");

            expect($response->json('data.attributes.answer_count'))->toBeNull();
        });

        test('存在しないユーザーIDの場合404を返す', function () {
            $response = $this->getJson('/api/v1/profiles/999999');

            expect($response->status())->toBe(404);
        });

        test('bigintに収まらないIDでも500にならず404を返す', function () {
            $response = $this->getJson('/api/v1/profiles/99999999999999999999');

            expect($response->status())->toBe(404);
        });
    });
});
