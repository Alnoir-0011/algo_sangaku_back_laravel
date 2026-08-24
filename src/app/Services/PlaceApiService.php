<?php

namespace App\Services;

use App\Exceptions\GooglePlacesApiException;
use App\Models\Shrine;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PlaceApiService
{
    private const GOOGLE_PLACE_SEARCH_TEXT_URI = 'https://places.googleapis.com/v1/places:searchText';

    private const ELIMINATE_KEYWORDS = ['寺', '手水舎', '社務所', '授与所', '鳥居'];

    public function searchByBounds(float $lowLat, float $highLat, float $lowLng, float $highLng): array
    {
        $searchResults = $this->textSearchByLocationRestriction($lowLat, $highLat, $lowLng, $highLng);

        return $this->persistPlaces($searchResults);
    }

    private function headers(): array
    {
        return [
            'Content-Type' => 'application/json',
            'X-Goog-Api-Key' => config('services.google_map.api_key'),
            'X-Goog-Fieldmask' => 'places.displayName,places.id,places.formattedAddress,places.location',
        ];
    }

    private function textSearchByLocationRestriction(float $lowLat, float $highLat, float $lowLng, float $highLng): array
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

        return $this->performSearchTextRequest($params);
    }

    private function performSearchTextRequest(array $params): array
    {
        $response = Http::connectTimeout(3)
            ->timeout(10)
            ->withHeaders($this->headers())
            ->post(self::GOOGLE_PLACE_SEARCH_TEXT_URI, $params);

        if (! $response->successful()) {
            Log::warning('Google Places API request failed', [
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
            ]);

            throw new GooglePlacesApiException;
        }

        return $this->eliminateNonShrine($response->json()['places'] ?? []);
    }

    private function persistPlaces(array $filteredPlaces): array
    {
        $shrines = array_map(function (array $place) {
            if (! $this->validatePlace($place)) {
                Log::warning('Skipped invalid place data from Google Places API', ['place' => $place]);

                return null;
            }

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

        return array_values(array_filter($shrines));
    }

    private function validatePlace(array $place): bool
    {
        $validator = Validator::make($place, [
            'id' => 'required|string|max:255',
            'displayName.text' => 'required|string|max:255',
            'formattedAddress' => 'required|string|max:255',
            'location.latitude' => 'required|numeric|min:-90|max:90',
            'location.longitude' => 'required|numeric|min:-180|max:180',
        ]);

        return $validator->passes();
    }

    private function eliminateNonShrine(array $places): array
    {
        return array_values(array_filter($places, function (array $place) {
            foreach (self::ELIMINATE_KEYWORDS as $keyword) {
                if (str_contains($place['displayName']['text'] ?? '', $keyword)) {
                    return false;
                }
            }

            return true;
        }));
    }
}
