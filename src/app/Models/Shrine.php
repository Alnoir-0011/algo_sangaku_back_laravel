<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shrine extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'place_id',
    ];

    protected $casts = [
        'latitude' => 'double',
        'longitude' => 'double',
    ];

    public function sangakus(): HasMany
    {
        return $this->hasMany(Sangaku::class);
    }
}
