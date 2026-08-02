<?php

use App\Models\Shrine;
use Illuminate\Support\Facades\Http;

describe('ShrinesController', function () {
    describe('GET /api/v1/shrines', function () {
        test('returns shrines found via PlaceApiService when searchType is Map', function () {
            Http::fake([
                'places.googleapis.com/*' => Http::response([
                    'places' => [
                        [
                            'id' => 'place-id-1',
                            'displayName' => ['text' => '八幡神社'],
                            'formattedAddress' => '東京都千代田区1-1',
                            'location' => ['latitude' => 35.6895, 'longitude' => 139.6917],
                        ],
                    ],
                ], 200),
            ]);

            $response = $this->getJson('/api/v1/shrines?searchType=Map&lowLat=35.0&highLat=35.5&lowLng=139.0&highLng=139.5');

            expect($response->status())->toBe(200);
            expect($response->json('data.0.place_id'))->toBe('place-id-1');
            expect($response->json('data.0.name'))->toBe('八幡神社');
        });

        test('returns 200 with empty data when no shrines are found', function () {
            Http::fake([
                'places.googleapis.com/*' => Http::response(['places' => []], 200),
            ]);

            $response = $this->getJson('/api/v1/shrines?searchType=Map&lowLat=35.0&highLat=35.5&lowLng=139.0&highLng=139.5');

            expect($response->status())->toBe(200);
            expect($response->json('data'))->toBe([]);
        });

        test('keeps the data field as a JSON array even when some places are filtered out', function () {
            Http::fake([
                'places.googleapis.com/*' => Http::response([
                    'places' => [
                        [
                            'id' => 'temple-1',
                            'displayName' => ['text' => '○○寺'],
                            'formattedAddress' => '住所1',
                            'location' => ['latitude' => 35.1, 'longitude' => 139.1],
                        ],
                        [
                            'id' => 'shrine-1',
                            'displayName' => ['text' => '八幡神社'],
                            'formattedAddress' => '住所2',
                            'location' => ['latitude' => 35.2, 'longitude' => 139.2],
                        ],
                    ],
                ], 200),
            ]);

            $response = $this->getJson('/api/v1/shrines?searchType=Map&lowLat=35.0&highLat=35.5&lowLng=139.0&highLng=139.5');

            expect($response->status())->toBe(200);
            expect(array_is_list($response->json('data')))->toBeTrue();
            expect($response->json('data.0.place_id'))->toBe('shrine-1');
        });

        test('returns 502 when the Google Places API request fails', function () {
            Http::fake([
                'places.googleapis.com/*' => Http::response([], 500),
            ]);

            $response = $this->getJson('/api/v1/shrines?searchType=Map&lowLat=35.0&highLat=35.5&lowLng=139.0&highLng=139.5');

            expect($response->status())->toBe(502);
        });

        test('returns 400 when searchType is not provided', function () {
            $response = $this->getJson('/api/v1/shrines');

            expect($response->status())->toBe(400);
            expect($response->json('message'))->toBe('Bad Request');
            expect($response->json('errors'))->toHaveKeys(['searchType', 'lowLat', 'highLat', 'lowLng', 'highLng']);
        });

        test('returns 400 when lowLat is not numeric', function () {
            $response = $this->getJson('/api/v1/shrines?searchType=Map&lowLat=abc&highLat=35.5&lowLng=139.0&highLng=139.5');

            expect($response->status())->toBe(400);
            expect($response->json('errors'))->toHaveKey('lowLat');
        });

        test('returns 400 when highLat is not greater than lowLat', function () {
            $response = $this->getJson('/api/v1/shrines?searchType=Map&lowLat=35.5&highLat=35.0&lowLng=139.0&highLng=139.5');

            expect($response->status())->toBe(400);
            expect($response->json('errors'))->toHaveKey('highLat');
        });

        test('accepts a large search area (no rectangle size limit)', function () {
            Http::fake([
                'places.googleapis.com/*' => Http::response(['places' => []], 200),
            ]);

            $response = $this->getJson('/api/v1/shrines?searchType=Map&lowLat=30.0&highLat=45.0&lowLng=129.0&highLng=146.0');

            expect($response->status())->toBe(200);
        });
    });

    describe('GET /api/v1/shrines/{id}', function () {
        test('returns a specific shrine', function () {
            $shrine = Shrine::factory()->create();

            $response = $this->getJson("/api/v1/shrines/{$shrine->id}");

            expect($response->status())->toBe(200);
            expect($response->json('data'))->toMatchArray([
                'id' => $shrine->id,
                'name' => $shrine->name,
                'address' => $shrine->address,
                'place_id' => $shrine->place_id,
            ]);
        });

        test('returns 404 if shrine not found', function () {
            $response = $this->getJson('/api/v1/shrines/999999');

            expect($response->status())->toBe(404);
        });
    });
});
