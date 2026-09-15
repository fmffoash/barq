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
            'wp_site_id' => null,
            'wp_site_url' => null,
            'wp_admin_url' => null,
            'wp_provisioned_at' => null,
        ];
    }

    // موقع ووردبريس اتعمل بالفعل على الشبكة — Phase 5 tests بتستخدمها لتجربة سلوك الموقع
    // بعد التوفير (redirect للرابط الحقيقي بدل صفحة "لسه بيتجهّز").
    public function wordpressProvisioned(): static
    {
        return $this->state(fn (array $attributes) => [
            'wp_site_id' => fake()->unique()->numberBetween(1, 9999),
            'wp_site_url' => 'https://'.Str::slug($attributes['slug']).'.example-network.test',
            'wp_admin_url' => 'https://'.Str::slug($attributes['slug']).'.example-network.test/wp-admin',
            'wp_provisioned_at' => now(),
        ]);
    }
}
