<?php

namespace Database\Factories;

use App\Models\Shrine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shrine>
 */
class ShrineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().'神社',
            'address' => fake()->address(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'place_id' => fake()->unique()->uuid(),
        ];
    }
}
