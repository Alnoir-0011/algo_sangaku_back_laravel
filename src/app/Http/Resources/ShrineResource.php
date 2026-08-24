<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string $address
 * @property-read float $latitude
 * @property-read float $longitude
 * @property-read string $place_id
 * @property-read Carbon|null $created_at
 * @property-read Carbon|null $updated_at
 */
class ShrineResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'type' => 'shrine',
            'attributes' => [
                'name' => $this->name,
                'address' => $this->address,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'place_id' => $this->place_id,
            ],
            'relationships' => [
            ],
        ];
    }
}
