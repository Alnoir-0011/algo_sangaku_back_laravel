<?php

namespace Database\Factories;

use App\Models\FixedInput;
use App\Models\Sangaku;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FixedInput>
 */
class FixedInputFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sangaku_id' => Sangaku::factory(),
            'content' => fake()->word(),
        ];
    }
}
