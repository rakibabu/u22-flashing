<?php

namespace Database\Factories;

use App\Models\TeamDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeamDocument>
 */
class TeamDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(array_keys(TeamDocument::defaultRows()));
        $defaults = TeamDocument::defaultRows()[$type];

        return [
            'type' => $type,
            'title' => $defaults['title'],
            'description' => $defaults['description'],
            'pdf_path' => null,
            'original_filename' => null,
            'uploaded_by_user_id' => User::factory()->coach(),
            'uploaded_at' => null,
            'toc_status' => TeamDocument::TocMissing,
            'toc_error' => null,
        ];
    }
}
