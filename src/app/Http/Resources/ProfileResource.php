<?php

namespace App\Http\Resources;

use App\Models\Answer;
use App\Models\Sangaku;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @property-read int $id
 * @property-read string $nickname
 * @property-read bool $show_answer_count
 * @property-read Carbon|null $created_at
 * @property-read Collection|Sangaku[] $sangakus
 * @property-read Collection|Answer[] $answers
 */
class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $dedicatedSangakus = $this->resource->dedicatedSangakusWithShrine()->get();

        return [
            'id' => (string) $this->id,
            'type' => 'profile',
            'attributes' => [
                'nickname' => $this->nickname,
                'created_at' => $this->created_at,
                'sangaku_count' => $this->sangakus->count(),
                'dedicated_sangaku_count' => $dedicatedSangakus->count(),
                'answer_count' => $this->show_answer_count ? $this->answers->count() : null,
                'dedicated_sangakus' => $dedicatedSangakus->map(fn ($sangaku) => [
                    'id' => $sangaku->id,
                    'title' => $sangaku->title,
                    'shrine_name' => $sangaku->shrine->name,
                ])->values()->all(),
            ],
        ];
    }
}
