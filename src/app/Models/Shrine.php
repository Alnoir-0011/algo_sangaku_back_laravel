<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
