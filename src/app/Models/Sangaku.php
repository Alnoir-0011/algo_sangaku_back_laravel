<?php

namespace App\Models;

use App\Enums\Difficulty;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sangaku extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shrine_id',
        'title',
        'description',
        'source',
        'difficulty',
    ];

    protected function casts(): array
    {
        return [
            'difficulty' => Difficulty::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Shrine, $this>
     */
    public function shrine(): BelongsTo
    {
        return $this->belongsTo(Shrine::class);
    }

    /**
     * @return HasMany<FixedInput, $this>
     */
    public function fixedInputs(): HasMany
    {
        return $this->hasMany(FixedInput::class);
    }

    public function scopeSearch(Builder $query, ?array $params): Builder
    {
        if (empty($params)) {
            return $query->distinct();
        }

        $query->distinct();

        // shrine_id は空文字で「奉納前（未設定）」を表すが、ConvertEmptyStringsToNull により
        // null で届くため、isset ではなく array_key_exists でキーの有無を見る
        if (array_key_exists('shrine_id', $params)) {
            if ($params['shrine_id'] === 'any') {
                $query->whereNotNull('shrine_id');
            } else {
                $shrineId = filter_var(
                    $params['shrine_id'] ?? '',
                    FILTER_VALIDATE_INT,
                );

                $query->where('shrine_id', $shrineId ?: null);
            }
        }

        if (! empty($params['difficulty'])) {
            $difficulty = Difficulty::fromLabel($params['difficulty']);

            if ($difficulty !== null) {
                $query->where('difficulty', $difficulty);
            }
        }

        if (! empty($params['title'])) {
            $words = preg_split('/\s+/u', trim($params['title']));

            foreach ($words as $word) {
                $query->where('title', 'like', "%{$word}%");
            }
        }

        return $query;
    }
}
