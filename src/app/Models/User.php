<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['provider', 'uid', 'name', 'email', 'nickname'];

    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'show_answer_count' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Sangaku, $this>
     */
    public function sangakus(): HasMany
    {
        return $this->hasMany(Sangaku::class);
    }

    /**
     * @return BelongsToMany<Sangaku, $this>
     */
    public function savedSangakus(): BelongsToMany
    {
        return $this->belongsToMany(Sangaku::class, 'user_sangaku_saves');
    }

    /**
     * @return HasMany<UserSangakuSave, $this>
     */
    public function userSangakuSaves(): HasMany
    {
        return $this->hasMany(UserSangakuSave::class);
    }

    /**
     * @return HasManyThrough<Answer, UserSangakuSave, $this>
     */
    public function answers(): HasManyThrough
    {
        return $this->hasManyThrough(Answer::class, UserSangakuSave::class);
    }

    /**
     * user_sangaku_saves → answers と2段階の中間テーブルを経由するため、
     * 中間テーブル1つまでしか表現できない hasManyThrough では書けない。
     * そのため Relation ではなく、絞り込み済みのクエリビルダを直接返す
     * （$user->answers() と異なり ->with('answerResults') による積極的ロードはできない）。
     *
     * @return Builder<AnswerResult>
     */
    public function answerResults(): Builder
    {
        return AnswerResult::query()->whereHas('answer.userSangakuSave', function (Builder $query) {
            $query->where('user_id', $this->id);
        });
    }

    /**
     * 奉納済み（shrine_idが非null）の算額を、shrineをeager loadした状態で返す。
     *
     * @return HasMany<Sangaku, $this>
     */
    public function dedicatedSangakusWithShrine(): HasMany
    {
        return $this->sangakus()->whereNotNull('shrine_id')->with('shrine');
    }
}
