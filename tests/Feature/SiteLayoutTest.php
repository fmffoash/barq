<?php

namespace Tests\Feature;

use App\Models\GeneratedSite;
use App\Models\Project;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// التصميمات البصرية الجديدة (modern/gallery، Phase 7) بتشتغل على نفس بيانات الخانات بالظبط
// زي "classic" — الاختلاف بس في شكل العرض. الاختبارات دي بتتأكد إن كل تصميم بيرندر المحتوى
// صح وبيحافظ على نفس قواعد "الخانة الفاضية متترندرش" الموجودة من زمان.
class SiteLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function siteUrl(GeneratedSite $site): string
    {
        return 'http://'.$site->slug.'.'.config('barq.base_domain').'/';
    }

    private function buildTemplate(string $layout): Template
    {
        $template = Template::factory()->create(['kind' => 'landing', 'layout' => $layout]);

        $template->slots()->create([
            'section_key' => 'hero', 'key' => 'hero_title', 'label_ar' => 'العنوان',
            'slot_type' => 'text', 'sort_order' => 1,
        ]);
        $template->slots()->create([
            'section_key' => 'hero', 'key' => 'hero_cta', 'label_ar' => 'اطلب دلوقتي',
            'slot_type' => 'link', 'sort_order' => 2,
        ]);
        $template->slots()->create([
            'section_key' => 'hero', 'key' => 'hero_image', 'label_ar' => 'صورة الغلاف',
            'slot_type' => 'image', 'sort_order' => 3,
        ]);
        $template->slots()->create([
            'section_key' => 'services', 'key' => 'services_list', 'label_ar' => 'الخدمات',
            'slot_type' => 'list', 'sort_order' => 1,
        ]);
        $template->slots()->create([
            'section_key' => 'gallery', 'key' => 'gallery_image', 'label_ar' => 'صورة',
            'slot_type' => 'image', 'sort_order' => 1,
        ]);
        $template->slots()->create([
            'section_key' => 'contact', 'key' => 'contact_link', 'label_ar' => 'تواصل معنا',
            'slot_type' => 'link', 'sort_order' => 1,
        ]);

        return $template;
    }

    private function buildSite(Template $template): GeneratedSite
    {
        $project = Project::factory()->for($template)->create();

        return GeneratedSite::factory()->for($project)->create([
            'content_json' => [
                'hero_title' => 'أهلاً بيكم في مطعمنا',
                'hero_cta' => 'https://wa.me/201000000000',
                'hero_image' => '/storage/hero.jpg',
                'services_list' => ['فطار', 'غدا', 'عشا'],
                'gallery_image' => '/storage/demo.jpg',
                'contact_link' => 'https://wa.me/201000000000',
            ],
        ]);
    }

    public function test_modern_layout_renders_nav_hero_and_grid_sections(): void
    {
        $site = $this->buildSite($this->buildTemplate('modern'));

        $response = $this->get($this->siteUrl($site));

        $response->assertOk();
        $response->assertSee('أهلاً بيكم في مطعمنا');
        $response->assertSee('فطار');
        $response->assertSee('id="hero"', false);
        $response->assertSee('id="services"', false);
        $response->assertSee('id="gallery"', false);
        $response->assertSee('id="contact"', false);
    }

    public function test_gallery_layout_renders_hero_background_image_and_zigzag_sections(): void
    {
        $site = $this->buildSite($this->buildTemplate('gallery'));

        $response = $this->get($this->siteUrl($site));

        $response->assertOk();
        $response->assertSee('أهلاً بيكم في مطعمنا');
        $response->assertSee("url('/storage/hero.jpg')");
        $response->assertSee('src="/storage/demo.jpg"', false);
        $response->assertSee('فطار');
    }

    public function test_layouts_still_hide_sections_with_no_filled_content(): void
    {
        $template = $this->buildTemplate('modern');
        $project = Project::factory()->for($template)->create();
        $site = GeneratedSite::factory()->for($project)->create([
            'content_json' => ['hero_title' => 'أهلاً بيكم'],
        ]);

        $response = $this->get($this->siteUrl($site));

        $response->assertOk();
        $response->assertSee('id="hero"', false);
        $response->assertDontSee('id="services"', false);
        $response->assertDontSee('id="gallery"', false);
    }

    public function test_an_invalid_layout_value_is_rejected_when_updating_a_template(): void
    {
        $user = \App\Models\User::factory()->create();
        $template = Template::factory()->create();

        $response = $this->actingAs($user)->put(route('templates.update', $template), [
            'name' => $template->name,
            'kind' => $template->kind,
            'layout' => 'not-a-real-layout',
            'is_active' => '1',
        ]);

        $response->assertSessionHasErrors('layout');
    }
}
