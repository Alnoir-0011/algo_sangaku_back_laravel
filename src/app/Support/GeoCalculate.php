<?php

namespace App\Support;

class GeoCalculate
{
    /**
     * 地球の半径（km）。ハバーサイン公式で用いる球体近似の値。
     */
    private const EARTH_RADIUS_KM = 6371;

    /**
     * 2 地点間の大圏距離をハバーサイン公式で求める（単位: km）。
     */
    public static function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $lat1Rad = deg2rad($lat1);
        $lng1Rad = deg2rad($lng1);
        $lat2Rad = deg2rad($lat2);
        $lng2Rad = deg2rad($lng2);

        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLng = $lng2Rad - $lng1Rad;

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
            cos($lat1Rad) * cos($lat2Rad) *
            sin($deltaLng / 2) * sin($deltaLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_KM * $c;
    }
}
