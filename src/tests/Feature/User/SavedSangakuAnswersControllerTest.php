<?php

use App\Models\Answer;
use App\Models\Sangaku;
use App\Models\Shrine;
use App\Models\User;
use App\Models\UserSangakuSave;
use Laravel\Sanctum\Sanctum;

// Rails 版と異なり Laravel 側に wrap_parameters 相当の機能はないため、
// リクエストボディは SavedSangakuAnswersController::store() が実際に読んでいる
// フラットな source キーで送る（{"answer": {"source": ...}} ではなく {"source": ...}）。
//
// フィクスチャとして直接 Answer を作成する箇所は、created イベント経由で
// AnswerObserver/AnswerResultObserver が発火し実際に paiza.io へリクエストして
// しまうため Answer::withoutEvents() でイベントを無効化している
// （POST エンドポイント自体をテストする箇所は実際の挙動を見るためそのまま）。
describe('SavedSangakuAnswersController', function () {
    describe('POST /api/v1/user/saved_sangakus/{sangaku}/answers', function () {
        test('アクセストークンありの場合、Answerを作成しJSON形式で返す', function () {
            $user = User::factory()->create();
            $author = User::factory()->create();
            $shrine = Shrine::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $author->id, 'shrine_id' => $shrine->id]);
            UserSangakuSave::query()->create(['user_id' => $user->id, 'sangaku_id' => $sangaku->id]);

            Sanctum::actingAs($user);

            $countBefore = Answer::count();

            $response = $this->postJson("/api/v1/user/saved_sangakus/{$sangaku->id}/answers", [
                'source' => "puts 'Hello world'",
            ]);

            expect($response->status())->toBe(201);
            expect(Answer::count())->toBe($countBefore + 1);
            expect($response->json('data.attributes.source'))->toBe("puts 'Hello world'");
        });

        test('未認証の場合401を返す', function () {
            $user = User::factory()->create();
            $author = User::factory()->create();
            $shrine = Shrine::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $author->id, 'shrine_id' => $shrine->id]);
            UserSangakuSave::query()->create(['user_id' => $user->id, 'sangaku_id' => $sangaku->id]);

            $response = $this->postJson("/api/v1/user/saved_sangakus/{$sangaku->id}/answers", [
                'source' => "puts 'Hello world'",
            ]);

            expect($response->status())->toBe(401);
        });

        test('sourceが空の場合400を返し、Answerは作成されない', function () {
            $user = User::factory()->create();
            $author = User::factory()->create();
            $shrine = Shrine::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $author->id, 'shrine_id' => $shrine->id]);
            UserSangakuSave::query()->create(['user_id' => $user->id, 'sangaku_id' => $sangaku->id]);

            Sanctum::actingAs($user);

            $countBefore = Answer::count();

            $response = $this->postJson("/api/v1/user/saved_sangakus/{$sangaku->id}/answers", [
                'source' => '',
            ]);

            expect($response->status())->toBe(400);
            expect($response->json('errors'))->toHaveKey('source');
            expect(Answer::count())->toBe($countBefore);
        });

        test('既に解答済みの場合409を返し、既存のAnswerは削除されない', function () {
            $user = User::factory()->create();
            $author = User::factory()->create();
            $shrine = Shrine::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $author->id, 'shrine_id' => $shrine->id]);
            $userSangakuSave = UserSangakuSave::query()->create(['user_id' => $user->id, 'sangaku_id' => $sangaku->id]);
            $existingAnswer = Answer::withoutEvents(fn () => Answer::query()->forceCreate([
                'user_sangaku_save_id' => $userSangakuSave->id,
                'source' => "puts 'first answer'",
            ]));

            Sanctum::actingAs($user);

            $countBefore = Answer::count();

            $response = $this->postJson("/api/v1/user/saved_sangakus/{$sangaku->id}/answers", [
                'source' => "puts 'new answer'",
            ]);

            expect($response->status())->toBe(409);
            expect(Answer::count())->toBe($countBefore);
            expect(Answer::find($existingAnswer->id))->not->toBeNull();
        });

        test('自分が保存していないsangakuの場合404を返し、Answerは作成されない', function () {
            $user = User::factory()->create();
            $author = User::factory()->create();
            $shrine = Shrine::factory()->create();
            $unsavedSangaku = Sangaku::factory()->create(['user_id' => $author->id, 'shrine_id' => $shrine->id]);

            Sanctum::actingAs($user);

            $countBefore = Answer::count();

            $response = $this->postJson("/api/v1/user/saved_sangakus/{$unsavedSangaku->id}/answers", [
                'source' => "puts 'Hello world'",
            ]);

            expect($response->status())->toBe(404);
            expect(Answer::count())->toBe($countBefore);
        });
    });

    describe('GET /api/v1/user/saved_sangakus/{sangaku}/answers', function () {
        test('アクセストークンありの場合、Answerをsource付きJSON形式で返す', function () {
            $user = User::factory()->create();
            $author = User::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $author->id]);
            $userSangakuSave = UserSangakuSave::query()->create(['user_id' => $user->id, 'sangaku_id' => $sangaku->id]);
            $answer = Answer::withoutEvents(fn () => Answer::query()->forceCreate([
                'user_sangaku_save_id' => $userSangakuSave->id,
                'source' => "puts 'Hello world'",
            ]));

            Sanctum::actingAs($user);

            $response = $this->getJson("/api/v1/user/saved_sangakus/{$sangaku->id}/answers");

            expect($response->status())->toBe(200);
            expect($response->json('data.attributes.source'))->toBe($answer->source);
        });

        test('未認証の場合401を返す', function () {
            $user = User::factory()->create();
            $author = User::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $author->id]);
            $userSangakuSave = UserSangakuSave::query()->create(['user_id' => $user->id, 'sangaku_id' => $sangaku->id]);
            Answer::withoutEvents(fn () => Answer::query()->forceCreate([
                'user_sangaku_save_id' => $userSangakuSave->id,
                'source' => "puts 'Hello world'",
            ]));

            $response = $this->getJson("/api/v1/user/saved_sangakus/{$sangaku->id}/answers");

            expect($response->status())->toBe(401);
        });

        test('まだ解答していないsangakuの場合404を返す', function () {
            $user = User::factory()->create();
            $author = User::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $author->id]);
            UserSangakuSave::query()->create(['user_id' => $user->id, 'sangaku_id' => $sangaku->id]);

            Sanctum::actingAs($user);

            $response = $this->getJson("/api/v1/user/saved_sangakus/{$sangaku->id}/answers");

            expect($response->status())->toBe(404);
        });

        test('他ユーザーが解答済みでも自分は未解答の場合404を返す（他人の解答を漏らさない）', function () {
            $user = User::factory()->create();
            $author = User::factory()->create();
            $otherUser = User::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $author->id]);
            UserSangakuSave::query()->create(['user_id' => $user->id, 'sangaku_id' => $sangaku->id]);
            $otherUserSave = UserSangakuSave::query()->create(['user_id' => $otherUser->id, 'sangaku_id' => $sangaku->id]);
            Answer::withoutEvents(fn () => Answer::query()->forceCreate([
                'user_sangaku_save_id' => $otherUserSave->id,
                'source' => "puts 'other user answer'",
            ]));

            Sanctum::actingAs($user);

            $response = $this->getJson("/api/v1/user/saved_sangakus/{$sangaku->id}/answers");

            expect($response->status())->toBe(404);
        });

        test('自分が保存していないsangakuの場合404を返す', function () {
            $user = User::factory()->create();
            $author = User::factory()->create();
            $unsavedSangaku = Sangaku::factory()->create(['user_id' => $author->id]);

            Sanctum::actingAs($user);

            $response = $this->getJson("/api/v1/user/saved_sangakus/{$unsavedSangaku->id}/answers");

            expect($response->status())->toBe(404);
        });
    });
});
