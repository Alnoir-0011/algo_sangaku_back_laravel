<?php

namespace App\Http\Resources;

use App\Models\AnswerResult;
use App\Models\UserSangakuSave;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * @property-read int $id
 * @property-read string $source
 * @property-read UserSangakuSave $userSangakuSave
 * @property-read Collection|AnswerResult[] $answerResults
 */
class AnswerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'type' => 'answer',
            'attributes' => [
                'source' => $this->source,
                'status' => $this->resource->status()->label(),
            ],
            'relationships' => [
                'user_sangaku_save' => [
                    'data' => [
                        'id' => (string) $this->userSangakuSave->id,
                        'type' => 'user_sangaku_save',
                    ],
                ],
                'answer_results' => [
                    'data' => $this->answerResults->map(fn ($result) => [
                        'id' => (string) $result->id,
                        'type' => 'answer_result',
                    ])->values()->all(),
                ],
            ],
        ];
    }
}
