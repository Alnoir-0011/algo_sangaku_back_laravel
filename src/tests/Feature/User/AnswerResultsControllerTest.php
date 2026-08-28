<?php

use App\Enums\AnswerResultStatus;
use App\Models\Answer;
use App\Models\AnswerResult;
use App\Models\Sangaku;
use App\Models\Shrine;
use App\Models\User;
use App\Models\UserSangakuSave;
use Laravel\Sanctum\Sanctum;

// Answer 作成イベント経由で AnswerObserver/AnswerResultObserver が発火し実際に paiza.io へ
// リクエストしてしまうため、フィクスチャ作成は Answer::withoutEvents() でイベントを無効化している。
describe('AnswerResultsController', function () {
    describe('GET /api/v1/user/answer_results/{answerResult}', function () {
        test('アクセストークンありの場合、AnswerResultをJSON形式で返す', function () {
            $user = User::factory()->create();
            $author = User::factory()->create();
            $shrine = Shrine::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $author->id, 'shrine_id' => $shrine->id]);
            $userSangakuSave = UserSangakuSave::query()->create(['user_id' => $user->id, 'sangaku_id' => $sangaku->id]);
            $answerResult = Answer::withoutEvents(function () use ($userSangakuSave) {
                $answer = Answer::query()->forceCreate([
                    'user_sangaku_save_id' => $userSangakuSave->id,
                    'source' => "puts 'Hello world'",
                ]);

                return AnswerResult::query()->forceCreate([
                    'answer_id' => $answer->id,
                    'fixed_input_id' => null,
                    'status' => AnswerResultStatus::PENDING,
                    'output' => null,
                ]);
            });

            Sanctum::actingAs($user);

            $response = $this->getJson("/api/v1/user/answer_results/{$answerResult->id}");

            expect($response->status())->toBe(200);
        });

        test('未認証の場合401を返す', function () {
            $user = User::factory()->create();
            $author = User::factory()->create();
            $shrine = Shrine::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $author->id, 'shrine_id' => $shrine->id]);
            $userSangakuSave = UserSangakuSave::query()->create(['user_id' => $user->id, 'sangaku_id' => $sangaku->id]);
            $answerResult = Answer::withoutEvents(function () use ($userSangakuSave) {
                $answer = Answer::query()->forceCreate([
                    'user_sangaku_save_id' => $userSangakuSave->id,
                    'source' => "puts 'Hello world'",
                ]);

                return AnswerResult::query()->forceCreate([
                    'answer_id' => $answer->id,
                    'fixed_input_id' => null,
                    'status' => AnswerResultStatus::PENDING,
                    'output' => null,
                ]);
            });

            $response = $this->getJson("/api/v1/user/answer_results/{$answerResult->id}");

            expect($response->status())->toBe(401);
        });

        test('存在しないidの場合404を返す', function () {
            $user = User::factory()->create();
            $author = User::factory()->create();
            $shrine = Shrine::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $author->id, 'shrine_id' => $shrine->id]);
            $userSangakuSave = UserSangakuSave::query()->create(['user_id' => $user->id, 'sangaku_id' => $sangaku->id]);
            $answerResult = Answer::withoutEvents(function () use ($userSangakuSave) {
                $answer = Answer::query()->forceCreate([
                    'user_sangaku_save_id' => $userSangakuSave->id,
                    'source' => "puts 'Hello world'",
                ]);

                return AnswerResult::query()->forceCreate([
                    'answer_id' => $answer->id,
                    'fixed_input_id' => null,
                    'status' => AnswerResultStatus::PENDING,
                    'output' => null,
                ]);
            });

            Sanctum::actingAs($user);

            $response = $this->getJson('/api/v1/user/answer_results/'.($answerResult->id + 1_000_000));

            expect($response->status())->toBe(404);
        });

        test('他ユーザーのAnswerResultの場合404を返す', function () {
            $user = User::factory()->create();
            $author = User::factory()->create();
            $anotherUser = User::factory()->create();
            $shrine = Shrine::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $author->id, 'shrine_id' => $shrine->id]);
            $anotherUserSangakuSave = UserSangakuSave::query()->create(['user_id' => $anotherUser->id, 'sangaku_id' => $sangaku->id]);
            $anotherAnswerResult = Answer::withoutEvents(function () use ($anotherUserSangakuSave) {
                $anotherAnswer = Answer::query()->forceCreate([
                    'user_sangaku_save_id' => $anotherUserSangakuSave->id,
                    'source' => "puts 'Hello world'",
                ]);

                return AnswerResult::query()->forceCreate([
                    'answer_id' => $anotherAnswer->id,
                    'fixed_input_id' => null,
                    'status' => AnswerResultStatus::PENDING,
                    'output' => null,
                ]);
            });

            Sanctum::actingAs($user);

            $response = $this->getJson("/api/v1/user/answer_results/{$anotherAnswerResult->id}");

            expect($response->status())->toBe(404);
        });
    });
});
