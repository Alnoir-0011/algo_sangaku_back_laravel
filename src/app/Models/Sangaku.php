<?php

namespace App\Models;

use App\Enums\Difficulty;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Sangaku extends Model
{
    use HasFactory;

    /**
     * title 検索で受け付ける最大単語数。
     * 1 語ごとに前方ワイルドカードの LIKE が積まれるため、上限を設けて負荷を抑える。
     */
    private const MAX_SEARCH_WORDS = 5;

    // user_id / shrine_id は mass assignment の対象にしない。
    // user_id はリレーション経由の作成（$user->sangakus()->create(...)）で自動設定され、
    // shrine_id は奉納（DedicateController）の位置検証を通したうえで明示代入するため、
    // ここに含めると検証や所有者スコープを迂回する経路を作ってしまう。
    protected $fillable = [
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
        // 固定入力は提示順に意味があるため、常に登録順で取得する
        return $this->hasMany(FixedInput::class)->orderBy('id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function savedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_sangaku_saves');
    }

    /**
     * @return HasManyThrough<Answer, UserSangakuSave, $this>
     */
    public function answers(): HasManyThrough
    {
        return $this->hasManyThrough(Answer::class, UserSangakuSave::class);
    }

    /**
     * @return HasMany<UserSangakuSave, $this>
     */
    public function userSangakuSaves(): HasMany
    {
        return $this->hasMany(UserSangakuSave::class);
    }

    /**
     * 既に神社へ奉納済みか。
     */
    public function isDedicated(): bool
    {
        return $this->shrine_id !== null;
    }

    /**
     * この算額を指定の神社へ奉納する。奉納できた場合のみ true を返す。
     *
     * 未奉納のものだけを対象に更新し、その件数で競合を判定する。
     * 読み取りと更新の間に別リクエストが奉納を完了していた場合、
     * 条件に合致せず 0 件になるため、後勝ちの上書きが起きない。
     */
    public function dedicateTo(Shrine $shrine): bool
    {
        if ($this->isDedicated()) {
            return false;
        }

        $updated = $this->newQuery()
            ->whereKey($this->getKey())
            ->whereNull('shrine_id')
            ->update(['shrine_id' => $shrine->id]);

        return $updated > 0;
    }

    public function scopeSearch(Builder $query, ?array $params): Builder
    {
        if (empty($params)) {
            return $query;
        }

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

                // filter_var は不正値・bigint 範囲外で false を返す。0 は有効な整数として扱う
                $query->where('shrine_id', $shrineId !== false ? $shrineId : null);
            }
        }

        if (! empty($params['difficulty'])) {
            $difficulty = Difficulty::fromLabel($params['difficulty']);

            if ($difficulty !== null) {
                $query->where('difficulty', $difficulty);
            }
        }

        if (! empty($params['title'])) {
            // 不正な UTF-8 バイト列に対して preg_split は false を返すため、空配列に倒す
            $words = array_slice(
                preg_split('/\s+/u', trim($params['title'])) ?: [],
                0,
                self::MAX_SEARCH_WORDS
            );

            foreach ($words as $word) {
                // % と _ はそのままだとワイルドカードとして解釈されるためエスケープする
                $escaped = addcslashes($word, '%_\\');

                $query->where('title', 'like', "%{$escaped}%");
            }
        }

        return $query;
    }
}
