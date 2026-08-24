<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedInput extends Model
{
    use HasFactory;

    protected $fillable = [
        'sangaku_id',
        'content',
    ];

    /**
     * @return BelongsTo<Sangaku, $this>
     */
    public function sangaku(): BelongsTo
    {
        return $this->belongsTo(Sangaku::class);
    }
}
