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
        return route('site.show', ['siteSlug' => $site->slug]);
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

    // اسم التست القديم كان "renders_hero_background_image" — اتحوّل هيرو gallery من
    // background-image: url(...) على الـsection لـ<img data-slot> حقيقي (المرحلة 2، تكبير/
    // تحريك الصورة، docs/rich-text-and-image-editing-plan.md)، فبقى بيترندر بـsrc= زي أي
    // صورة تانية بدل url() CSS.
    public function test_gallery_layout_renders_hero_image_and_zigzag_sections(): void
    {
        $site = $this->buildSite($this->buildTemplate('gallery'));

        $response = $this->get($this->siteUrl($site));

        $response->assertOk();
        $response->assertSee('أهلاً بيكم في مطعمنا');
        $response->assertSee('data-slot="hero_image" src="/storage/hero.jpg"', false);
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

    // Phase 7 (الدفعة التانية) — 10 تصميمات إضافية (split/magazine/bento/minimal/bold/glass/
    // timeline/stack/diagonal/framed). بدل ما نكرر نفس التست التفصيلي 10 مرات، بنلف على كل
    // قيمة في Template::LAYOUTS ونتأكد إنها بترندر 200 وبتعرض المحتوى الفعلي وبتحترم قاعدة
    // إخفاء الأقسام الفاضية — أي layout جديد يتضاف للمصفوفة مستقبلاً بياخد نفس التغطية تلقائي.
    public function test_every_registered_layout_renders_content_and_hides_empty_sections(): void
    {
        foreach (Template::LAYOUTS as $layout) {
            $site = $this->buildSite($this->buildTemplate($layout));

            $response = $this->get($this->siteUrl($site));

            $response->assertOk();
            $response->assertSee('أهلاً بيكم في مطعمنا');
            $response->assertSee('فطار');
            $response->assertSee('id="hero"', false);
            $response->assertSee('id="services"', false);
            $response->assertSee('id="gallery"', false);
            $response->assertSee('id="contact"', false);

            $emptyTemplate = $this->buildTemplate($layout);
            $emptyProject = Project::factory()->for($emptyTemplate)->create();
            $emptySite = GeneratedSite::factory()->for($emptyProject)->create([
                'content_json' => ['hero_title' => 'أهلاً بيكم'],
            ]);

            $emptyResponse = $this->get($this->siteUrl($emptySite));

            $emptyResponse->assertOk();
            $emptyResponse->assertSee('id="hero"', false);
            $emptyResponse->assertDontSee('id="services"', false);
            $emptyResponse->assertDontSee('id="gallery"', false);
        }
    }

    // Phase 8 (الدفعة التالتة) — خط عام للموقع كله + تخصيص لون/خط خانة واحدة بس.
    public function test_the_variants_font_is_applied_as_a_css_custom_property_on_the_body(): void
    {
        $template = Template::factory()->create(['kind' => 'landing', 'layout' => 'modern']);
        $template->slots()->create([
            'section_key' => 'hero', 'key' => 'hero_title', 'label_ar' => 'العنوان',
            'slot_type' => 'text', 'sort_order' => 1,
        ]);
        $variant = \App\Models\TemplateVariant::factory()->for($template)->create(['font' => 'tajawal']);
        $project = Project::factory()->for($template)->create(['template_variant_id' => $variant->id]);
        $site = GeneratedSite::factory()->for($project)->create(['content_json' => ['hero_title' => 'أهلاً']]);

        $response = $this->get($this->siteUrl($site));

        $response->assertOk();
        $response->assertSee('font-family: var(--font-tajawal);', false);
    }

    public function test_a_slot_level_style_override_renders_as_scoped_css_on_the_matching_data_slot(): void
    {
        $template = $this->buildTemplate('modern');
        $project = Project::factory()->for($template)->create();
        $site = GeneratedSite::factory()->for($project)->create([
            'content_json' => ['hero_title' => 'أهلاً بيكم في مطعمنا'],
            'style_overrides_json' => ['hero_title' => ['color' => '#ff0000', 'font' => 'poppins']],
        ]);

        $response = $this->get($this->siteUrl($site));

        $response->assertOk();
        $response->assertSee('data-slot="hero_title"', false);
        $response->assertSee('[data-slot="hero_title"]', false);
        $response->assertSee('color: #ff0000 !important;', false);
        $response->assertSee('font-family: var(--font-poppins) !important;', false);
    }

    public function test_saving_site_content_persists_and_clears_slot_style_overrides(): void
    {
        $user = \App\Models\User::factory()->create();
        $template = Template::factory()->create(['kind' => 'landing']);
        $template->slots()->create([
            'section_key' => 'hero', 'key' => 'hero_title', 'label_ar' => 'العنوان',
            'slot_type' => 'text', 'sort_order' => 1,
        ]);
        $project = Project::factory()->for($template)->create();
        $site = GeneratedSite::factory()->for($project)->create(['content_json' => ['hero_title' => 'أهلاً']]);

        $this->actingAs($user)->put(route('projects.site.update', $project), [
            'content' => ['hero_title' => 'أهلاً'],
            'style' => ['hero_title' => ['color' => '#00ff00', 'font' => 'inter']],
        ]);

        $site->refresh();
        $this->assertSame(['color' => '#00ff00', 'font' => 'inter', 'zoom' => null, 'position' => null], $site->styleFor('hero_title'));

        $this->actingAs($user)->put(route('projects.site.update', $project), [
            'content' => ['hero_title' => 'أهلاً'],
            'style' => ['hero_title' => ['color' => '', 'font' => 'default']],
        ]);

        $site->refresh();
        $this->assertSame(['color' => null, 'font' => null, 'zoom' => null, 'position' => null], $site->styleFor('hero_title'));
    }

    public function test_a_malformed_style_override_color_or_font_is_silently_ignored(): void
    {
        $user = \App\Models\User::factory()->create();
        $template = Template::factory()->create(['kind' => 'landing']);
        $template->slots()->create([
            'section_key' => 'hero', 'key' => 'hero_title', 'label_ar' => 'العنوان',
            'slot_type' => 'text', 'sort_order' => 1,
        ]);
        $project = Project::factory()->for($template)->create();
        $site = GeneratedSite::factory()->for($project)->create(['content_json' => ['hero_title' => 'أهلاً']]);

        $this->actingAs($user)->put(route('projects.site.update', $project), [
            'content' => ['hero_title' => 'أهلاً'],
            // "لون" ده مش hex صالح (زي محاولة كسر الـ CSS block)، و"font" مش من القايمة المعروفة.
            'style' => ['hero_title' => ['color' => 'red; } * { display:none', 'font' => 'evil-font']],
        ]);

        $site->refresh();
        $this->assertSame(['color' => null, 'font' => null, 'zoom' => null, 'position' => null], $site->styleFor('hero_title'));
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
