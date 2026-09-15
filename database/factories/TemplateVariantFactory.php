<?php

namespace Database\Factories;

use App\Models\Template;
use App\Models\TemplateVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TemplateVariant>
 */
class TemplateVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'template_id' => Template::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'colors_json' => null,
            'sections_json' => null,
            'is_default' => false,
        ];
    }
}
