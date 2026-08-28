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
describe('ResultsController', function () {
    describe('GET /api/v1/user/sangakus/{sangaku}/result', function () {
        test('アクセストークンありの場合、集計結果をJSON形式で返す', function () {
            $user = User::factory()->create();
            $anotherUser = User::factory()->create();
            $shrine = Shrine::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $user->id, 'shrine_id' => $shrine->id]);
            $userSangakuSave = UserSangakuSave::query()->create(['user_id' => $anotherUser->id, 'sangaku_id' => $sangaku->id]);
            Answer::withoutEvents(function () use ($userSangakuSave) {
                $answer = Answer::query()->forceCreate([
                    'user_sangaku_save_id' => $userSangakuSave->id,
                    'source' => "puts 'Hello world'",
                ]);

                AnswerResult::query()->forceCreate([
                    'answer_id' => $answer->id,
                    'fixed_input_id' => null,
                    'status' => AnswerResultStatus::CORRECT,
                    'output' => "Hello world\n",
                ]);
            });

            Sanctum::actingAs($user);

            $response = $this->getJson("/api/v1/user/sangakus/{$sangaku->id}/result");

            expect($response->status())->toBe(200);
            expect($response->json('data.attributes.user_sangaku_save_count'))->toBe(1);
            expect($response->json('data.attributes.correct_count'))->toBe(1);
            expect($response->json('data.attributes.incorrect_count'))->toBe(0);
        });

        test('AnswerResultが全てerrorの場合、incorrectとして集計する', function () {
            $user = User::factory()->create();
            $anotherUser = User::factory()->create();
            $shrine = Shrine::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $user->id, 'shrine_id' => $shrine->id]);
            $userSangakuSave = UserSangakuSave::query()->create(['user_id' => $anotherUser->id, 'sangaku_id' => $sangaku->id]);
            Answer::withoutEvents(function () use ($userSangakuSave) {
                $answer = Answer::query()->forceCreate([
                    'user_sangaku_save_id' => $userSangakuSave->id,
                    'source' => "puts 'Hello world'",
                ]);

                AnswerResult::query()->forceCreate([
                    'answer_id' => $answer->id,
                    'fixed_input_id' => null,
                    'status' => AnswerResultStatus::ERROR,
                    'output' => 'error output',
                ]);
            });

            Sanctum::actingAs($user);

            $response = $this->getJson("/api/v1/user/sangakus/{$sangaku->id}/result");

            expect($response->status())->toBe(200);
            expect($response->json('data.attributes.correct_count'))->toBe(0);
            expect($response->json('data.attributes.incorrect_count'))->toBe(1);
        });

        test('未認証の場合401を返す', function () {
            $user = User::factory()->create();
            $shrine = Shrine::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $user->id, 'shrine_id' => $shrine->id]);

            $response = $this->getJson("/api/v1/user/sangakus/{$sangaku->id}/result");

            expect($response->status())->toBe(401);
        });

        test('存在しないsangaku_idの場合404を返す', function () {
            $user = User::factory()->create();
            $shrine = Shrine::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $user->id, 'shrine_id' => $shrine->id]);

            Sanctum::actingAs($user);

            $response = $this->getJson('/api/v1/user/sangakus/'.($sangaku->id + 1_000_000).'/result');

            expect($response->status())->toBe(404);
        });

        test('他ユーザー（算額の作者ではない）の場合404を返す', function () {
            $user = User::factory()->create();
            $anotherUser = User::factory()->create();
            $shrine = Shrine::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $user->id, 'shrine_id' => $shrine->id]);
            // another_user はこの sangaku を保存しているだけで、作者ではない
            UserSangakuSave::query()->create(['user_id' => $anotherUser->id, 'sangaku_id' => $sangaku->id]);

            Sanctum::actingAs($anotherUser);

            $response = $this->getJson("/api/v1/user/sangakus/{$sangaku->id}/result");

            expect($response->status())->toBe(404);
        });
    });
});
