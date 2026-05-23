<?php

namespace Database\Factories;

use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Player>
 */
class PlayerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'active' => true,
            'program_type' => fake()->randomElement([Player::Conditioning, Player::MuscleGain, Player::Maintenance]),
            'age' => fake()->numberBetween(17, 22),
            'height_cm' => fake()->numberBetween(175, 205),
            'start_weight_kg' => fake()->randomFloat(1, 60, 95),
        ];
    }
}
