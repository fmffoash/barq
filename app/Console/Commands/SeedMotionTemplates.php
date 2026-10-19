<?php

namespace App\Console\Commands;

use App\Models\Template;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('barq:seed-motion-templates')]
#[Description('إضافة قوالب الملف المطوّرة بالحركة و3D مع الحفاظ على القوالب والمشاريع الحالية')]
class SeedMotionTemplates extends Command
{
    public function handle(): int
    {
        $definitions = json_decode(file_get_contents(database_path('data/motion-templates.json')), true, flags: JSON_THROW_ON_ERROR);
        $created = 0;

        foreach ($definitions as $definition) {
            $created += DB::transaction(function () use ($definition): int {
                if (Template::query()->where('slug', $definition['slug'])->exists()) {
                    return 0;
                }

                $template = Template::create([
                    'slug' => $definition['slug'],
                    'name' => $definition['name'],
                    'category' => $definition['category'],
                    'kind' => 'landing',
                    'layout' => 'motion',
                    'motion_profile' => $definition['profile'],
                    'is_active' => true,
                ]);
                $template->slots()->createMany($definition['slots']);
                $template->variants()->create([
                    'slug' => 'default',
                    'name' => 'موشن 3D',
                    'colors_json' => $definition['colors'],
                    'font' => 'tajawal',
                    'heading_font' => 'tajawal',
                    'sections_json' => ['hero', 'about', 'services', 'gallery', 'testimonials', 'contact'],
                    'is_default' => true,
                ]);

                return 1;
            });
        }

        $this->info("Motion templates: {$created} added; existing templates and customizations preserved.");

        return self::SUCCESS;
    }
}
