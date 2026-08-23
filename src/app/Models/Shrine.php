<?php

namespace App\Models;

use App\Support\GeoCalculate;
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

    protected function casts(): array
    {
        return [
            'latitude' => 'double',
            'longitude' => 'double',
        ];
    }

    /**
     * 申告された座標が、この神社へ奉納できる距離まで近いかを判定する。
     *
     * lat/lng はクライアント申告値であり、位置偽装のセキュリティ境界にはならない
     * （神社の座標は公開エンドポイントから取得できるため、値をコピーすれば判定を通せる）。
     * 個人開発規模のなりすましリスクを踏まえ、署名済み位置情報やレート制限の
     * 追加対応は不要と判断している。
     */
    public function isWithinDedicateRange(float $lat, float $lng): bool
    {
        $distance = GeoCalculate::calculateDistance($this->latitude, $this->longitude, $lat, $lng);

        return $distance < config('sangaku.dedicate_distance_km');
    }

    /**
     * @return HasMany<Sangaku, $this>
     */
    public function sangakus(): HasMany
    {
        return $this->hasMany(Sangaku::class);
    }
}
