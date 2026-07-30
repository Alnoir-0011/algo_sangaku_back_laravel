<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Shrine;

class PlaceApiService
{
    private const GOOGLE_PLACE_SEARCH_TEXT_URI = 'https://places.googleapis.com/v1/places:searchText';

    private static function headers(): array
    {
        return [
            'Content-Type' => 'application/json',
            'X-Goog-Api-Key' => config('services.google_map.api_key'),
            'X-Goog-Fieldmask' => 'places.displayName,places.id,places.formattedAddress,places.location',
        ];
    }

    private const ELIMINATE_KEYWORDS = ['寺', '手水舎', '社務所', '授与所', '鳥居'];

    static function searchByBounds(string $lowLat, string $highLat, string $lowLng, string $highLng)
    {
        $searchResults = self::textSearchByLocationRestriction(
            $lowLat, $highLat, $lowLng, $highLng);

        return self::persistPlaces(self::eliminateNonShrine($searchResults));
    }

    private static function textSearchByLocationRestriction(string $lowLat, string $highLat, string $lowLng, string $highLng)
    {
        $params = [
            'textQuery' => '神社 -寺',
            'languageCode' => 'JA',
            'pageSize' => 10,
            'locationRestriction' => [
                'rectangle' => [
                    'low' => [
                        'latitude' => $lowLat,
                        'longitude' => $lowLng,
                    ],
                    'high' => [
                        'latitude' => $highLat,
                        'longitude' => $highLng,
                    ],
                ],
            ],
            'regionCode' => 'JP',
            'rankPreference' => 'DISTANCE',
        ];

        return self::performSearchTextRequest($params);
    }

    private static function performSearchTextRequest($params)
    {
        $response = Http::withHeaders(self::headers())
            ->post(self::GOOGLE_PLACE_SEARCH_TEXT_URI, $params);

        if ($response->successful()) {
            return self::eliminateNonShrine($response->json()['places'] ?? []);
        } else {
            // Log the response for debugging
            \Log::info('Google Places API response: '.$response->body());
            throw new \Exception('Google Places API request failed: '.$response->body());
        }
    }

    private static function persistPlaces($filteredPlaces)
    {
        return array_map(function ($place) {
            return Shrine::updateOrCreate(
                ['place_id' => $place['id']],
                [
                    'name' => $place['displayName']['text'],
                    'address' => $place['formattedAddress'],
                    'latitude' => $place['location']['latitude'],
                    'longitude' => $place['location']['longitude'],
                ]
            );
        }, $filteredPlaces);
    }

    private static function eliminateNonShrine($places)
    {
        return array_filter($places, function ($place) {
            foreach (self::ELIMINATE_KEYWORDS as $keyword) {
                if (strpos($place['displayName']['text'], $keyword) !== false) {
                    return false;
                }
            }

            return true;
        });
    }
}
