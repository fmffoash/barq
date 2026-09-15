<?php

namespace Database\Factories;

use App\Models\GeneratedSite;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GeneratedSite>
 */
class GeneratedSiteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'slug' => Str::slug(fake()->unique()->words(3, true)).'-'.fake()->unique()->numberBetween(1000, 9999),
            'content_json' => [],
            'status' => 'draft',
            'exported_at' => null,
            'last_generated_at' => null,
        ];
    }
}
