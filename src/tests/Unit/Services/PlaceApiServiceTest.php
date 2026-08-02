<?php

use App\Models\Shrine;
use App\Services\PlaceApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function fakeGooglePlacesSearch(array $places, int $status = 200): void
{
    Http::fake([
        'places.googleapis.com/*' => Http::response(['places' => $places], $status),
    ]);
}

function shrinePlace(string $id, string $name, string $address = '住所', float $lat = 35.0, float $lng = 139.0): array
{
    return [
        'id' => $id,
        'displayName' => ['text' => $name],
        'formattedAddress' => $address,
        'location' => ['latitude' => $lat, 'longitude' => $lng],
    ];
}

describe('PlaceApiService::searchByBounds', function () {
    describe('when the api returns shrine results', function () {
        test('creates a new shrine record for each result', function () {
            fakeGooglePlacesSearch([
                shrinePlace('place-id-1', '八幡神社', '東京都千代田区1-1', 35.6895, 139.6917),
            ]);

            PlaceApiService::searchByBounds('35.0', '36.0', '139.0', '140.0');

            $shrine = Shrine::where('place_id', 'place-id-1')->first();

            expect($shrine)->not->toBeNull();
            expect($shrine->name)->toBe('八幡神社');
            expect($shrine->address)->toBe('東京都千代田区1-1');
            expect((float) $shrine->latitude)->toBe(35.6895);
            expect((float) $shrine->longitude)->toBe(139.6917);
        });

        test('updates the existing shrine instead of creating a duplicate when place_id matches', function () {
            Shrine::create([
                'place_id' => 'place-id-1',
                'name' => '旧名称',
                'address' => '旧住所',
                'latitude' => 0,
                'longitude' => 0,
            ]);

            fakeGooglePlacesSearch([
                shrinePlace('place-id-1', '八幡神社', '更新後の住所', 35.0, 139.0),
            ]);

            PlaceApiService::searchByBounds('35.0', '36.0', '139.0', '140.0');

            expect(Shrine::where('place_id', 'place-id-1')->count())->toBe(1);

            $shrine = Shrine::where('place_id', 'place-id-1')->first();
            expect($shrine->name)->toBe('八幡神社');
            expect($shrine->address)->toBe('更新後の住所');
        });
    });

    describe('keyword filtering', function () {
        test('excludes places whose name contains an eliminate keyword', function (string $keyword) {
            fakeGooglePlacesSearch([
                shrinePlace('excluded-place', "テスト{$keyword}"),
            ]);

            PlaceApiService::searchByBounds('35.0', '36.0', '139.0', '140.0');

            expect(Shrine::where('place_id', 'excluded-place')->exists())->toBeFalse();
        })->with(['寺', '手水舎', '社務所', '授与所', '鳥居']);

        test('keeps places whose name does not contain any eliminate keyword', function () {
            fakeGooglePlacesSearch([
                shrinePlace('shrine-1', '八幡神社'),
                shrinePlace('temple-1', '○○寺'),
            ]);

            PlaceApiService::searchByBounds('35.0', '36.0', '139.0', '140.0');

            expect(Shrine::where('place_id', 'shrine-1')->exists())->toBeTrue();
            expect(Shrine::where('place_id', 'temple-1')->exists())->toBeFalse();
        });
    });

    describe('when the api request fails', function () {
        test('throws an exception and persists nothing', function () {
            fakeGooglePlacesSearch([], 400);

            expect(fn () => PlaceApiService::searchByBounds('35.0', '36.0', '139.0', '140.0'))
                ->toThrow(Exception::class, 'Google Places API request failed');

            expect(Shrine::count())->toBe(0);
        });
    });

    describe('request payload', function () {
        test('sends the correct url, headers and body to the google places api', function () {
            config(['services.google_map.api_key' => 'test-api-key']);

            fakeGooglePlacesSearch([]);

            PlaceApiService::searchByBounds('35.0', '36.0', '139.0', '140.0');

            Http::assertSent(function (Request $request) {
                return $request->url() === 'https://places.googleapis.com/v1/places:searchText'
                    && $request->hasHeader('Content-Type', 'application/json')
                    && $request->hasHeader('X-Goog-Api-Key', 'test-api-key')
                    && $request['textQuery'] === '神社 -寺'
                    && $request['locationRestriction']['rectangle']['low']['latitude'] === '35.0'
                    && $request['locationRestriction']['rectangle']['low']['longitude'] === '139.0'
                    && $request['locationRestriction']['rectangle']['high']['latitude'] === '36.0'
                    && $request['locationRestriction']['rectangle']['high']['longitude'] === '140.0';
            });
        });
    });
});
