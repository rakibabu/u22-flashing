<?php

namespace Database\Factories;

use App\Models\BasketballTrainerPlaybookLink;
use App\Models\TeamDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BasketballTrainerPlaybookLink>
 */
class BasketballTrainerPlaybookLinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_document_id' => TeamDocument::factory()->state([
                'type' => TeamDocument::Playbook,
                ...TeamDocument::defaultRows()[TeamDocument::Playbook],
            ]),
            'external_playbook_hash' => (string) fake()->uuid(),
            'external_title' => fake()->words(3, true),
            'external_updated_at' => now()->subDay(),
            'metadata' => [
                'season' => '2026/2027',
                'plays_count' => fake()->numberBetween(1, 20),
            ],
            'last_checked_at' => now(),
            'last_error' => null,
            'linked_by_user_id' => User::factory()->coach(),
        ];
    }
}
