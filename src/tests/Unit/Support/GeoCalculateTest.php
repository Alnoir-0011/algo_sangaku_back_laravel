<?php

use App\Support\GeoCalculate;

describe('GeoCalculate::calculateDistance', function () {
    test('同一地点の距離は0になる', function () {
        $distance = GeoCalculate::calculateDistance(35.681236, 139.767125, 35.681236, 139.767125);

        expect($distance)->toBe(0.0);
    });

    test('東京駅と大阪駅の距離が実距離に近い値になる', function () {
        // 実距離はおよそ 403km
        $distance = GeoCalculate::calculateDistance(35.681236, 139.767125, 34.702485, 135.495951);

        expect($distance)->toBeGreaterThan(390.0);
        expect($distance)->toBeLessThan(410.0);
    });

    test('緯度方向に約100m離れた2点の距離が約0.1kmになる', function () {
        // 緯度1度はおよそ111.19km なので、0.1km 相当は約0.000899度
        $distance = GeoCalculate::calculateDistance(35.0, 139.0, 35.000899, 139.0);

        expect($distance)->toBeGreaterThan(0.095);
        expect($distance)->toBeLessThan(0.105);
    });

    test('奉納の許容距離の内側と外側を正しく判定できる', function () {
        $threshold = 0.1;

        // 約50m（許容内）
        $near = GeoCalculate::calculateDistance(35.0, 139.0, 35.00045, 139.0);
        // 約200m（許容外）
        $far = GeoCalculate::calculateDistance(35.0, 139.0, 35.0018, 139.0);

        expect($near)->toBeLessThan($threshold);
        expect($far)->toBeGreaterThan($threshold);
    });

    test('起点と終点を入れ替えても同じ距離になる', function () {
        $forward = GeoCalculate::calculateDistance(35.0, 139.0, 36.0, 140.0);
        $backward = GeoCalculate::calculateDistance(36.0, 140.0, 35.0, 139.0);

        expect($forward)->toBe($backward);
    });

    test('経度方向の距離は緯度が高いほど短くなる', function () {
        $atEquator = GeoCalculate::calculateDistance(0.0, 139.0, 0.0, 140.0);
        $atMidLatitude = GeoCalculate::calculateDistance(35.0, 139.0, 35.0, 140.0);

        expect($atMidLatitude)->toBeLessThan($atEquator);
    });
});
