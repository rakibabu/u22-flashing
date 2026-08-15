<?php

namespace Database\Factories;

use App\Enums\ExerciseScope;
use App\Models\ExerciseLibraryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ExerciseLibraryItem> */
class ExerciseLibraryItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'scope' => ExerciseScope::Both,
            'category' => 'warming_up',
            'name' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'execution' => fake()->paragraph(),
            'coaching_cues' => fake()->sentence(),
            'default_duration_minutes' => 10,
            'min_players' => 4,
            'max_players' => 12,
            'baskets_required' => 1,
            'coaching_points' => ['Communiceer', 'Blijf laag'],
        ];
    }
}
