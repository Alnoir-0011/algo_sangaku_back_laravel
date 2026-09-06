<?php

use App\Models\Answer;
use App\Models\Sangaku;
use App\Models\Shrine;
use App\Models\User;
use App\Models\UserSangakuSave;
use Laravel\Sanctum\Sanctum;

describe('ProfileController', function () {
    describe('GET /api/v1/user/profile', function () {
        test('認証済みユーザーが自分のプロフィールを取得できる', function () {
            $user = User::factory()->create(['show_answer_count' => false]);

            Sanctum::actingAs($user);

            $response = $this->getJson('/api/v1/user/profile');

            expect($response->status())->toBe(200);
            $attrs = $response->json('data.attributes');
            expect($attrs['email'])->toBe($user->email);
            expect($attrs['nickname'])->toBe($user->nickname);
            expect($attrs['show_answer_count'])->toBe(false);
            expect($attrs['created_at'])->not->toBeNull();
        });

        test('sangaku_count・dedicated_sangaku_count・saved_sangaku_count・answer_countを正しく返す', function () {
            $user = User::factory()->create();
            $author = User::factory()->create();
            $shrine = Shrine::factory()->create();

            Sangaku::factory()->create(['user_id' => $user->id, 'shrine_id' => $shrine->id]);
            Sangaku::factory()->create(['user_id' => $user->id, 'shrine_id' => null]);

            $savedSangaku = Sangaku::factory()->create(['user_id' => $author->id]);
            $userSangakuSave = UserSangakuSave::query()->create(['user_id' => $user->id, 'sangaku_id' => $savedSangaku->id]);
            Answer::withoutEvents(fn () => Answer::query()->forceCreate([
                'user_sangaku_save_id' => $userSangakuSave->id,
                'source' => "puts 'Hello world'",
            ]));

            Sanctum::actingAs($user);

            $response = $this->getJson('/api/v1/user/profile');

            $attrs = $response->json('data.attributes');
            expect($attrs['sangaku_count'])->toBe(2);
            expect($attrs['dedicated_sangaku_count'])->toBe(1);
            expect($attrs['saved_sangaku_count'])->toBe(1);
            expect($attrs['answer_count'])->toBe(1);
        });

        test('show_answer_countがfalseでも自分自身のanswer_countはnullにならず値を返す', function () {
            $user = User::factory()->create(['show_answer_count' => false]);

            Sanctum::actingAs($user);

            $response = $this->getJson('/api/v1/user/profile');

            expect($response->json('data.attributes.answer_count'))->toBe(0);
        });

        test('他ユーザーの算額・保存・解答はカウントに含まれない', function () {
            $user = User::factory()->create();
            $otherUser = User::factory()->create();
            $author = User::factory()->create();

            Sangaku::factory()->create(['user_id' => $otherUser->id]);

            $savedSangaku = Sangaku::factory()->create(['user_id' => $author->id]);
            $otherUserSangakuSave = UserSangakuSave::query()->create(['user_id' => $otherUser->id, 'sangaku_id' => $savedSangaku->id]);
            Answer::withoutEvents(fn () => Answer::query()->forceCreate([
                'user_sangaku_save_id' => $otherUserSangakuSave->id,
                'source' => "puts 'Hello world'",
            ]));

            Sanctum::actingAs($user);

            $response = $this->getJson('/api/v1/user/profile');

            $attrs = $response->json('data.attributes');
            expect($attrs['sangaku_count'])->toBe(0);
            expect($attrs['saved_sangaku_count'])->toBe(0);
            expect($attrs['answer_count'])->toBe(0);
        });

        test('未認証の場合401を返す', function () {
            $response = $this->getJson('/api/v1/user/profile');

            expect($response->status())->toBe(401);
        });
    });

    describe('PATCH /api/v1/user/profile', function () {
        test('nicknameのみ更新できる', function () {
            $user = User::factory()->create(['nickname' => 'before_name']);

            Sanctum::actingAs($user);

            $response = $this->patchJson('/api/v1/user/profile', ['nickname' => 'changed_name']);

            expect($response->status())->toBe(200);
            expect($response->json('data.attributes.nickname'))->toBe('changed_name');
            expect($user->fresh()->nickname)->toBe('changed_name');
        });

        test('show_answer_countのみ更新できる', function () {
            $user = User::factory()->create(['show_answer_count' => false]);

            Sanctum::actingAs($user);

            $response = $this->patchJson('/api/v1/user/profile', ['show_answer_count' => true]);

            expect($response->status())->toBe(200);
            expect($user->fresh()->show_answer_count)->toBe(true);
        });

        test('nicknameとshow_answer_countを同時に更新できる', function () {
            $user = User::factory()->create(['nickname' => 'before_name', 'show_answer_count' => false]);

            Sanctum::actingAs($user);

            $response = $this->patchJson('/api/v1/user/profile', [
                'nickname' => 'changed_name',
                'show_answer_count' => true,
            ]);

            expect($response->status())->toBe(200);
            $user->refresh();
            expect($user->nickname)->toBe('changed_name');
            expect($user->show_answer_count)->toBe(true);
        });

        test('更新後のレスポンスはGET /api/v1/user/profileと同じ形（マイプロフィール）で返る', function () {
            $user = User::factory()->create();

            Sanctum::actingAs($user);

            $response = $this->patchJson('/api/v1/user/profile', ['nickname' => 'changed_name']);

            $attrs = $response->json('data.attributes');
            expect($attrs['email'])->toBe($user->email);
            expect($attrs['nickname'])->toBe('changed_name');
            expect($attrs)->toHaveKeys([
                'show_answer_count',
                'created_at',
                'sangaku_count',
                'dedicated_sangaku_count',
                'saved_sangaku_count',
                'answer_count',
            ]);
        });

        test('nicknameを空文字にすると400を返し、値は変更されない', function () {
            $user = User::factory()->create(['nickname' => 'before_name']);

            Sanctum::actingAs($user);

            $response = $this->patchJson('/api/v1/user/profile', ['nickname' => '']);

            expect($response->status())->toBe(400);
            expect($response->json('errors'))->toHaveKey('nickname');
            expect($user->fresh()->nickname)->toBe('before_name');
        });

        test('未認証の場合401を返す', function () {
            $response = $this->patchJson('/api/v1/user/profile', ['nickname' => 'changed_name']);

            expect($response->status())->toBe(401);
        });
    });
});
