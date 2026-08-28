<?php

namespace App\Http\Resources;

use App\Enums\AnswerResultStatus;
use App\Models\FixedInput;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read int $id
 * @property-read AnswerResultStatus $status
 * @property-read string|null $output
 * @property-read int $answer_id
 * @property-read int|null $fixed_input_id
 * @property-read FixedInput|null $fixedInput
 */
class AnswerResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'type' => 'answer_result',
            'attributes' => [
                'status' => $this->status->label(),
                'output' => $this->output,
                'fixed_input_content' => $this->fixedInput !== null ? $this->fixedInput->content : '',
            ],
            'relationships' => [
                'answer' => [
                    'data' => [
                        'id' => (string) $this->answer_id,
                        'type' => 'answer',
                    ],
                ],
                'fixed_input' => [
                    'data' => $this->fixed_input_id ? [
                        'id' => (string) $this->fixed_input_id,
                        'type' => 'fixed_input',
                    ] : null,
                ],
            ],
        ];
    }
}
