<?php

namespace Database\Factories;

use App\Enums\Difficulty;
use App\Models\Sangaku;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sangaku>
 */
class SangakuFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'shrine_id' => null,
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'source' => "puts 'Hello world'",
            'difficulty' => Difficulty::EASY,
        ];
    }
}
