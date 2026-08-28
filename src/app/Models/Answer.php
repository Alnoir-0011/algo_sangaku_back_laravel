<?php

namespace App\Models;

use App\Enums\AnswerResultStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Answer extends Model
{
    use HasFactory;

    protected $fillable = ['source', 'user_sangaku_save_id'];

    /**
     * @return BelongsTo<UserSangakuSave, $this>
     */
    public function userSangakuSave(): BelongsTo
    {
        return $this->belongsTo(UserSangakuSave::class);
    }

    /**
     * @return HasMany<AnswerResult, $this>
     */
    public function answerResults(): HasMany
    {
        return $this->hasMany(AnswerResult::class);
    }

    /**
     * 採点結果の集計ステータス。優先順位: pending（1件でもあれば） >
     * incorrect（error を含む） > correct（全件 correct のときのみ）。
     */
    public function status(): AnswerResultStatus
    {
        $statuses = $this->answerResults->pluck('status');

        if ($statuses->contains(AnswerResultStatus::PENDING)) {
            return AnswerResultStatus::PENDING;
        }

        if ($statuses->contains(AnswerResultStatus::INCORRECT) || $statuses->contains(AnswerResultStatus::ERROR)) {
            return AnswerResultStatus::INCORRECT;
        }

        return AnswerResultStatus::CORRECT;
    }

    /**
     * user_sangaku_saves 経由で紐づく Sangaku。answers → user_sangaku_saves →
     * sangakus と FK が常に「手前側」にあるため、標準の hasOneThrough が想定する
     * 向き（through/related 側に FK がある）とは逆になり、キーを明示指定している。
     *
     * @return HasOneThrough<Sangaku, UserSangakuSave, $this>
     */
    public function sangaku(): HasOneThrough
    {
        return $this->hasOneThrough(
            Sangaku::class,
            UserSangakuSave::class,
            'id',
            'id',
            'user_sangaku_save_id',
            'sangaku_id',
        );
    }

    /**
     * user_sangaku_saves 経由で紐づく User。sangaku() と同じ理由でキーを明示指定している。
     *
     * @return HasOneThrough<User, UserSangakuSave, $this>
     */
    public function user(): HasOneThrough
    {
        return $this->hasOneThrough(
            User::class,
            UserSangakuSave::class,
            'id',
            'id',
            'user_sangaku_save_id',
            'user_id',
        );
    }

    /**
     * 紐づく AnswerResult が1件以上あり、そのすべてが correct の Answer に絞り込む。
     * status() の優先順位ロジック（pending > incorrect(errorを含む) > correct）と整合させること。
     */
    public function scopeStatusCorrect(Builder $query): Builder
    {
        return $query->whereDoesntHave('answerResults', function (Builder $query) {
            $query->where('status', '!=', AnswerResultStatus::CORRECT);
        });
    }

    /**
     * pending の AnswerResult を1件も持たず、かつ correct 以外（incorrect または error）を
     * 1件以上持つ Answer に絞り込む。status() の優先順位ロジックと整合させること。
     */
    public function scopeStatusIncorrect(Builder $query): Builder
    {
        return $query
            ->whereDoesntHave('answerResults', function (Builder $query) {
                $query->where('status', AnswerResultStatus::PENDING);
            })
            ->whereHas('answerResults', function (Builder $query) {
                $query->where('status', '!=', AnswerResultStatus::CORRECT);
            });
    }
}
