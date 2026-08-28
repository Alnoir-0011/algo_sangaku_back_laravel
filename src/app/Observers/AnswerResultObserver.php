<?php

namespace App\Observers;

use App\Enums\AnswerResultStatus;
use App\Models\AnswerResult;
use App\Models\FixedInput;
use App\Services\PaizaIoApiService;

class AnswerResultObserver
{
    /**
     * Handle the AnswerResult "created" event.
     */
    public function created(AnswerResult $answerResult): void
    {
        // outputを生成し正誤判定をする
        $answer = $answerResult->answer;
        $sangaku = $answer->sangaku;
        // fixed_input_id は nullable なため、対応する FixedInput が存在しない場合がある。
        $fixedInput = FixedInput::find($answerResult->fixed_input_id);
        $input = $fixedInput !== null ? $fixedInput->content : '';

        $paizaIoClient = new PaizaIoApiService;
        $expected = $paizaIoClient->runSource($sangaku->source, $input);
        $result = $paizaIoClient->runSource($answer->source, $input);

        if (! empty($result['stderr'])) {
            $status = AnswerResultStatus::INCORRECT;
            $output = $result['stderr'];
        } else {
            $status = $result['stdout'] === $expected['stdout'] ? AnswerResultStatus::CORRECT : AnswerResultStatus::INCORRECT;
            $output = $result['stdout'];
        }

        $answerResult->update([
            'output' => $output,
            'status' => $status,
        ]);
    }

    /**
     * Handle the AnswerResult "updated" event.
     */
    public function updated(AnswerResult $answerResult): void
    {
        //
    }

    /**
     * Handle the AnswerResult "deleted" event.
     */
    public function deleted(AnswerResult $answerResult): void
    {
        //
    }

    /**
     * Handle the AnswerResult "restored" event.
     */
    public function restored(AnswerResult $answerResult): void
    {
        //
    }

    /**
     * Handle the AnswerResult "force deleted" event.
     */
    public function forceDeleted(AnswerResult $answerResult): void
    {
        //
    }
}
