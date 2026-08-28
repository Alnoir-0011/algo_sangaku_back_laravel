<?php

use App\Enums\AnswerResultStatus;
use App\Models\Answer;
use App\Models\AnswerResult;
use App\Models\Sangaku;
use App\Models\Shrine;
use App\Models\User;
use App\Models\UserSangakuSave;
use Laravel\Sanctum\Sanctum;

describe('AnswersController', function () {
    describe('GET /api/v1/user/answers/{answer}', function () {
        test('アクセストークンありの場合、Answerをsource付きJSON形式で返す', function () {
            $user = User::factory()->create();
            $author = User::factory()->create();
            $shrine = Shrine::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $author->id, 'shrine_id' => $shrine->id]);
            $userSangakuSave = UserSangakuSave::query()->create(['user_id' => $user->id, 'sangaku_id' => $sangaku->id]);
            // Answer 作成イベント経由で AnswerObserver/AnswerResultObserver が発火し、
            // 実際に paiza.io へリクエストしてしまうため、フィクスチャ作成はイベント無効化して行う。
            $answer = Answer::withoutEvents(fn () => Answer::query()->forceCreate([
                'user_sangaku_save_id' => $userSangakuSave->id,
                'source' => "puts 'Hello world'",
            ]));

            Sanctum::actingAs($user);

            $response = $this->getJson("/api/v1/user/answers/{$answer->id}");

            expect($response->status())->toBe(200);
            expect($response->json('data.attributes.source'))->toBe($answer->source);
        });

        test('未認証の場合401を返す', function () {
            $user = User::factory()->create();
            $author = User::factory()->create();
            $shrine = Shrine::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $author->id, 'shrine_id' => $shrine->id]);
            $userSangakuSave = UserSangakuSave::query()->create(['user_id' => $user->id, 'sangaku_id' => $sangaku->id]);
            $answer = Answer::withoutEvents(fn () => Answer::query()->forceCreate([
                'user_sangaku_save_id' => $userSangakuSave->id,
                'source' => "puts 'Hello world'",
            ]));

            $response = $this->getJson("/api/v1/user/answers/{$answer->id}");

            expect($response->status())->toBe(401);
        });

        test('全てのAnswerResultがerrorステータスの場合、statusはincorrectを返す', function () {
            $user = User::factory()->create();
            $author = User::factory()->create();
            $shrine = Shrine::factory()->create();
            $sangaku = Sangaku::factory()->create(['user_id' => $author->id, 'shrine_id' => $shrine->id]);
            $userSangakuSave = UserSangakuSave::query()->create(['user_id' => $user->id, 'sangaku_id' => $sangaku->id]);
            $answer = Answer::withoutEvents(function () use ($userSangakuSave) {
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

                return $answer;
            });

            Sanctum::actingAs($user);

            $response = $this->getJson("/api/v1/user/answers/{$answer->id}");

            expect($response->status())->toBe(200);
            expect($response->json('data.attributes.status'))->toBe('incorrect');
        });
    });
});
