<?php

namespace Tests\Feature;

use App\Console\Commands\SeedTemplateLibrary;
use App\Models\GeneratedSite;
use App\Models\Project;
use App\Models\Template;
use App\Models\TemplateVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// مكتبة القوالب (2026-10-10، فؤاد: "راجع القوالب اللي متكررة"): قبل كده الـ15 قالب في كل فئة كانوا
// بنفس العنوان والوصف والصور والخط، وألوانهم 7-9 عيلات بس. التستات دي بتقفل التنوع ده، وبتتأكد إن
// تحديث المكتبة ميغيّرش شكل أي موقع قايم.
class TemplateLibrarySeedTest extends TestCase
{
    use RefreshDatabase;

    private function seedLibrary(): void
    {
        $this->artisan('barq:seed-template-library')->assertSuccessful();
    }

    public function test_every_category_has_fifteen_genuinely_different_templates(): void
    {
        $this->seedLibrary();

        $this->assertSame(300, Template::count());

        $byCategory = Template::with(['variants', 'slots'])->get()->groupBy('category');
        $this->assertCount(20, $byCategory);

        foreach ($byCategory as $category => $templates) {
            $variants = $templates->map(fn (Template $t) => $t->defaultVariant());
            $heroTitles = $templates->map(fn (Template $t) => $t->slots->firstWhere('key', 'hero_title')->default_value);
            $families = $variants->map(fn ($v) => collect(SeedTemplateLibrary::PALETTES)->first(fn ($p) => $p['colors'] == $v->colors_json)['family'] ?? $v->colors_json['primary']);

            $this->assertCount(15, $templates, $category);
            $this->assertSame(15, $templates->pluck('layout')->unique()->count(), "{$category}: layouts");
            $this->assertSame(15, $heroTitles->unique()->count(), "{$category}: hero titles");
            $this->assertGreaterThanOrEqual(12, $families->unique()->count(), "{$category}: colour families");
            $this->assertGreaterThanOrEqual(6, $variants->pluck('font')->merge($variants->pluck('heading_font'))->unique()->count(), "{$category}: fonts");
            $this->assertGreaterThanOrEqual(2, $templates->map(fn (Template $t) => $t->slots->firstWhere('key', 'hero_image')->default_value)->unique()->count(), "{$category}: cover photos");
        }
    }

    public function test_all_templates_share_the_same_twenty_slot_keys(): void
    {
        $this->seedLibrary();

        $keySets = Template::with('slots')->get()
            ->map(fn (Template $t) => $t->slots->pluck('key')->sort()->values()->implode(','))
            ->unique();

        $this->assertCount(1, $keySets, 'AI template switching needs identical keys everywhere');
        $keys = explode(',', $keySets->first());
        $this->assertCount(20, $keys);
        $this->assertContains('gallery_image_6', $keys);
        $this->assertSame('تواصل معنا', Template::first()->slots->firstWhere('key', 'contact_link')->label_ar);
    }

    public function test_fonts_exist_and_every_default_picture_is_on_disk(): void
    {
        $this->seedLibrary();

        foreach (TemplateVariant::all() as $variant) {
            $this->assertArrayHasKey($variant->font, TemplateVariant::FONTS);
            $this->assertArrayHasKey($variant->heading_font, TemplateVariant::FONTS);
        }

        Template::with('slots')->get()->flatMap->slots
            ->where('slot_type', 'image')
            ->pluck('default_value')->filter()->unique()
            ->each(fn (string $path) => $this->assertFileExists(public_path(ltrim($path, '/'))));
    }

    public function test_muted_text_is_readable_on_every_palette(): void
    {
        foreach (SeedTemplateLibrary::PALETTES as $key => $palette) {
            $colors = $palette['colors'];
            foreach (['background', 'surface'] as $behind) {
                $this->assertGreaterThanOrEqual(4.5, $this->contrast($colors['muted'], $colors[$behind]), "{$key}: muted on {$behind}");
                $this->assertGreaterThanOrEqual(7.0, $this->contrast($colors['text'], $colors[$behind]), "{$key}: text on {$behind}");
            }
        }
    }

    public function test_running_it_again_changes_nothing(): void
    {
        $this->seedLibrary();
        $before = Template::max('updated_at');

        $this->artisan('barq:seed-template-library')
            ->expectsOutputToContain('0 قالب جديد، 0 محدّث، 300 من غير تغيير')
            ->assertSuccessful();

        $this->assertSame(300, Template::count());
        $this->assertSame($before, Template::max('updated_at'));
        $this->artisan('barq:seed-template-library --if-outdated')->expectsOutputToContain('up to date')->assertSuccessful();
    }

    public function test_updating_the_library_keeps_existing_sites_looking_the_same(): void
    {
        $this->seedLibrary();
        $template = Template::with(['slots', 'variants'])->where('slug', 'mtaaam-okafyhat-mtaam-oagh-dafy')->firstOrFail();
        $variant = $template->defaultVariant();
        $project = Project::factory()->for($template)->create(['template_variant_id' => $variant->id]);
        $site = GeneratedSite::factory()->for($project)->create(['content_json' => ['hero_title' => 'عنوان فؤاد']]);

        // نسخة "قديمة" من المكتبة: ألوان وخط وصورة غلاف ووصف مختلفين عن البيانات الحالية.
        $oldColors = ['primary' => '#123456', 'background' => '#000000', 'surface' => '#111111', 'text' => '#ffffff', 'muted' => '#aaaaaa'];
        $variant->update(['colors_json' => $oldColors, 'font' => 'cairo', 'heading_font' => null]);
        $template->slots->firstWhere('key', 'hero_image')->update(['default_value' => '/images/template-library/restaurants/hero.jpg']);
        $template->slots->firstWhere('key', 'hero_subtitle')->update(['default_value' => 'وصف قديم']);

        $this->seedLibrary();

        $site->refresh();
        $this->assertSame('عنوان فؤاد', $site->content_json['hero_title']);
        $this->assertSame('وصف قديم', $site->content_json['hero_subtitle']);
        $this->assertSame($oldColors, $site->colors_override_json);
        $this->assertSame('cairo', $site->font_override);
        $this->assertNotSame('وصف قديم', $template->slots()->where('key', 'hero_subtitle')->value('default_value'), 'the library itself did update');
    }

    private function contrast(string $a, string $b): float
    {
        [$la, $lb] = [$this->luminance($a), $this->luminance($b)];

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    private function luminance(string $hex): float
    {
        $channels = array_map(function (string $pair) {
            $c = hexdec($pair) / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, str_split(ltrim($hex, '#'), 2));

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }
}
