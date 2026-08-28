<?php

namespace App\Models;

use App\Enums\AnswerResultStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnswerResult extends Model
{
    use HasFactory;

    protected $fillable = ['fixed_input_id', 'output', 'status'];

    protected function casts(): array
    {
        return [
            'status' => AnswerResultStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Answer, $this>
     */
    public function answer(): BelongsTo
    {
        return $this->belongsTo(Answer::class);
    }

    /**
     * @return BelongsTo<FixedInput, $this>
     */
    public function fixedInput(): BelongsTo
    {
        return $this->belongsTo(FixedInput::class);
    }
}
