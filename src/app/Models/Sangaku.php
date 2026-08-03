<?php

namespace App\Models;

use App\Enums\Difficulty;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

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

    protected $casts = [
        'difficulty' => Difficulty::class,
    ];

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

        if (isset($params['shrine_id'])) {
            if ($params['shrine_id'] === 'any') {
                $query->whereNotNull('shrine_id');
            } else {
                $shrineId = filter_var(
                    $params['shrine_id'],
                    FILTER_VALIDATE_INT,
                );

                $query->where('shrine_id', $shrineId ?: null);
            }
        }

        if (! empty($params['difficulty'])) {
            $difficulty = Difficulty::tryFrom($params['difficulty']);

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
