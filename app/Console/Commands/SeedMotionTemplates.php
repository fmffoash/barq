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
        $refreshed = 0;

        // قالب الأدمن مسحه (Template::deletePermanently) مايرجعش تاني.
        $deleted = SeedTemplateLibrary::deletedSlugs();

        foreach ($definitions as $definition) {
            if (in_array($definition['slug'], $deleted, true)) {
                continue;
            }

            $created += DB::transaction(function () use ($definition, &$refreshed): int {
                $existing = Template::query()->where('slug', $definition['slug'])->first();
                if ($existing) {
                    // (2026-10-19) القالب موجود: بيتحدّث شكله بس (التكوين والرسمة 3D) لو اتغيّر في الملف.
                    // الخانات والألوان والمحتوى ومشاريع اليوزرات مابتتلمسش.
                    if ($existing->layout === 'motion' && $existing->motion_profile != $definition['profile']) {
                        $existing->update(['motion_profile' => $definition['profile']]);
                        $refreshed++;
                    }

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

        $this->info("Motion templates: {$created} added, {$refreshed} look profiles refreshed; slots, colors and projects preserved.");

        return self::SUCCESS;
    }
}
