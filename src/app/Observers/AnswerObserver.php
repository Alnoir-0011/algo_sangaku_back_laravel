<?php

namespace App\Observers;

use App\Models\Answer;

class AnswerObserver
{
    /**
     * Handle the Answer "created" event.
     */
    public function created(Answer $answer): void
    {
        $fixedInputs = $answer->sangaku->fixedInputs;
        if ($fixedInputs->isNotEmpty()) {
            $answer->answerResults()->createMany(
                $fixedInputs->map(fn ($fixedInput) => [
                    'fixed_input_id' => $fixedInput->id,
                ])->toArray()
            );
        } else {
            $answer->answerResults()->create(['fixed_input_id' => null]);
        }
    }

    /**
     * Handle the Answer "updated" event.
     */
    public function updated(Answer $answer): void
    {
        //
    }

    /**
     * Handle the Answer "deleted" event.
     */
    public function deleted(Answer $answer): void
    {
        //
    }

    /**
     * Handle the Answer "restored" event.
     */
    public function restored(Answer $answer): void
    {
        //
    }

    /**
     * Handle the Answer "force deleted" event.
     */
    public function forceDeleted(Answer $answer): void
    {
        //
    }
}
