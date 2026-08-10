<?php

namespace App\Http\Resources;

use App\Models\FixedInput;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @property-read int $id
 * @property-read string $title
 * @property-read string $description
 * @property-read string $source
 * @property-read int $difficulty
 * @property-read int $user_id
 * @property-read int|null $shrine_id
 * @property-read Carbon|null $created_at
 * @property-read Carbon|null $updated_at
 * @property-read Collection|FixedInput[] $fixedInputs
 */
class SangakuResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'type' => 'sangaku',
            'attributes' => [
                'title' => $this->title,
                'description' => $this->description,
                'source' => $this->source,
                'difficulty' => $this->difficulty,
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'id' => (string) $this->user_id,
                        'type' => 'user',
                    ],
                ],
                'shrine' => [
                    'data' => $this->shrine_id ? [
                        'id' => (string) $this->shrine_id,
                        'type' => 'shrine',
                    ] : null,
                ],
                'fixed_inputs' => [
                    'data' => $this->fixedInputs->map(fn ($input) => [
                        'id' => (string) $input->id,
                        'type' => 'fixed_input',
                    ])->values()->all(),
                ],
            ],
        ];
    }
}
