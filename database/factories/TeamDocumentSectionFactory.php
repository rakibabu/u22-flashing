<?php

namespace Database\Factories;

use App\Models\TeamDocument;
use App\Models\TeamDocumentSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeamDocumentSection>
 */
class TeamDocumentSectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_document_id' => TeamDocument::factory(),
            'title' => fake()->words(3, true),
            'page_number' => fake()->numberBetween(1, 12),
            'sort_order' => fake()->numberBetween(1, 12),
            'source' => 'text',
        ];
    }
}
