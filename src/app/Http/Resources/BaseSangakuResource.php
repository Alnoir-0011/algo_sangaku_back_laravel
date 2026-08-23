<?php

namespace App\Http\Resources;

use App\Models\FixedInput;
use App\Models\Shrine;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * 算額レスポンスの共通形。
 *
 * 所有者向け（SangakuResource）と公開用（PublicSangakuResource）の違いは
 * source を返すかどうかだけなので、出し分けだけをサブクラスに委ねる。
 *
 * @property-read int $id
 * @property-read string $title
 * @property-read string $description
 * @property-read string $source
 * @property-read int $difficulty
 * @property-read int $user_id
 * @property-read int|null $shrine_id
 * @property-read Carbon|null $created_at
 * @property-read Carbon|null $updated_at
 * @property-read User $user
 * @property-read Shrine|null $shrine
 * @property-read Collection|FixedInput[] $fixedInputs
 */
abstract class BaseSangakuResource extends JsonResource
{
    /**
     * attributes.source に載せる値。公開用は解答を伏せるため null を返す。
     */
    abstract protected function resolveSource(): ?string;

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
                'source' => $this->resolveSource(),
                'difficulty' => $this->difficulty,
                'author_name' => $this->user->nickname,
                'shrine_name' => $this->shrine?->name,
                'inputs' => $this->fixedInputs->map(fn (FixedInput $input) => [
                    'id' => $input->id,
                    'content' => $input->content,
                ])->values()->all(),
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
            ],
        ];
    }
}
