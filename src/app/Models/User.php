<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
}
