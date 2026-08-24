<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSangakuSave extends Model
{
    protected $table = 'user_sangaku_saves';

    protected $fillable = [
        'user_id',
        'sangaku_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sangaku(): BelongsTo
    {
        return $this->belongsTo(Sangaku::class);
    }
}
