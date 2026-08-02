<?php

use App\Models\Shrine;
use App\Services\PlaceApiService;

describe('ShrinesController', function () {
    describe('GET /api/v1/shrines', function () {
        test('returns shrines found via PlaceApiService when searchType is Map', function () {
            $shrine = Shrine::factory()->create();

            $mock = Mockery::mock('alias:'.PlaceApiService::class);
            $mock->shouldReceive('searchByBounds')
                ->once()
                ->with('35.0', '36.0', '139.0', '140.0')
                ->andReturn([$shrine]);

            $response = $this->getJson('/api/v1/shrines?searchType=Map&lowLat=35.0&highLat=36.0&lowLng=139.0&highLng=140.0');
            var_dump($response->json());

            expect($response->status())->toBe(200);
            expect($response->json('data.id'))->toBe($shrine->id);
        });

        test('returns 400 when searchType is not provided', function () {
            $response = $this->getJson('/api/v1/shrines');

            expect($response->status())->toBe(400);
            expect($response->json())->toMatchArray([
                'error' => 'Invalid params',
            ]);
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
