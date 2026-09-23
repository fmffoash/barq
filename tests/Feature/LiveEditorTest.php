<?php

namespace Tests\Feature;

use App\Models\GeneratedSite;
use App\Models\Project;
use App\Models\Template;
use App\Models\User;
use App\Services\RichTextSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

// المحرر البصري المباشر (WYSIWYG click-to-edit، docs/wysiwyg-editor-plan.md) — بيرندر نفس
// الموقع الحقيقي جوّه route محمي بـ auth، وبيحقن سكريبت/CSS التعديل. المسار العام
// (site.show) لازم يفضل زي ما هو تماماً، صفر أثر لوضع التعديل عليه.
class LiveEditorTest extends TestCase
{
    use RefreshDatabase;

    private function buildProjectWithSite(): Project
    {
        $template = Template::factory()->create(['kind' => 'landing']);
        $template->slots()->create(['section_key' => 'hero', 'key' => 'hero_title', 'label_ar' => 'عنوان', 'slot_type' => 'text']);
        $template->slots()->create(['section_key' => 'hero', 'key' => 'hero_desc', 'label_ar' => 'وصف', 'slot_type' => 'textarea']);
        $project = Project::factory()->for($template)->create();
        GeneratedSite::factory()->for($project)->create([
            'content_json' => ['hero_title' => 'أهلاً بيكم', 'hero_desc' => 'وصف مبدئي'],
        ]);

        return $project;
    }

    private function buildProjectWithImageSlot(): Project
    {
        $template = Template::factory()->create(['kind' => 'landing']);
        $template->slots()->create(['section_key' => 'hero', 'key' => 'hero_title', 'label_ar' => 'عنوان', 'slot_type' => 'text']);
        $template->slots()->create(['section_key' => 'hero', 'key' => 'hero_image', 'label_ar' => 'صورة', 'slot_type' => 'image']);
        $project = Project::factory()->for($template)->create();
        GeneratedSite::factory()->for($project)->create([
            'content_json' => ['hero_title' => 'أهلاً بيكم', 'hero_image' => '/storage/site-images/test.jpg'],
            'status' => 'published',
        ]);

        return $project;
    }

    public function test_guests_cannot_access_the_live_editor(): void
    {
        $project = $this->buildProjectWithSite();

        $response = $this->get(route('projects.site.live-edit', $project));

        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_open_the_live_editor_and_it_renders_the_real_site_with_editor_assets(): void
    {
        $user = User::factory()->create();
        $project = $this->buildProjectWithSite();

        $response = $this->actingAs($user)->get(route('projects.site.live-edit', $project));

        $response->assertOk();
        $response->assertSee('أهلاً بيكم');
        $response->assertSee('data-slot="hero_title"', false);
        $response->assertSee('live-editor-config', false);
        $response->assertSee('live-editor.js', false);
        $response->assertSee('live-editor.css', false);
    }

    // القيد الأمني الأهم في الخطة: المسار العام مش بيبان عليه أي أثر لوضع التعديل، حتى لو
    // اللي بيفتحه أدمن مسجّل دخول فعلاً (صفر query parameter بيفعّل التعديل على الرابط العام).
    public function test_the_public_site_route_never_loads_editor_assets(): void
    {
        $user = User::factory()->create();
        $project = $this->buildProjectWithSite();
        $site = $project->site;
        $site->update(['status' => 'published']);

        $response = $this->actingAs($user)->get(route('site.show', ['siteSlug' => $site->slug]));

        $response->assertOk();
        $response->assertDontSee('live-editor-config', false);
        $response->assertDontSee('live-editor.js', false);
        $response->assertDontSee('live-editor.css', false);
    }

    // ده بالظبط الباج اللي اتصلح قبل ما نبني الحفظ التدريجي: نداء AJAX بيبعت تخصيص لون/خط
    // خانة واحدة بس لازم يسيب تخصيص أي خانة تانية زي ما هو، مش يمسحه.
    public function test_a_partial_style_save_for_one_slot_preserves_another_slots_style_override(): void
    {
        $user = User::factory()->create();
        $project = $this->buildProjectWithSite();
        $site = $project->site;
        $site->update([
            'style_overrides_json' => [
                'hero_desc' => ['color' => '#123456', 'font' => 'tajawal'],
            ],
        ]);

        // نداء زي اللي المحرر البصري هيبعته: مفيش غير style بتاع hero_title، صفر ذكر لـ
        // hero_desc خالص في الـ request.
        $response = $this->actingAs($user)->put(route('projects.site.update', $project), [
            'style' => [
                'hero_title' => ['color' => '#ff0000', 'font' => ''],
            ],
        ]);

        $response->assertRedirect(route('projects.show', $project));
        $site->refresh();

        $this->assertSame('#ff0000', $site->styleFor('hero_title')['color']);
        $this->assertSame('#123456', $site->styleFor('hero_desc')['color']);
        $this->assertSame('tajawal', $site->styleFor('hero_desc')['font']);
    }

    public function test_a_content_only_save_does_not_touch_any_style_overrides(): void
    {
        $user = User::factory()->create();
        $project = $this->buildProjectWithSite();
        $site = $project->site;
        $site->update([
            'style_overrides_json' => [
                'hero_title' => ['color' => '#123456', 'font' => 'tajawal'],
            ],
        ]);

        $response = $this->actingAs($user)->put(route('projects.site.update', $project), [
            'content' => ['hero_title' => 'عنوان جديد'],
        ]);

        $response->assertRedirect(route('projects.show', $project));
        $site->refresh();

        $this->assertSame('عنوان جديد', $site->content('hero_title'));
        $this->assertSame('#123456', $site->styleFor('hero_title')['color']);
        $this->assertSame('tajawal', $site->styleFor('hero_title')['font']);
    }

    // ---------------------------------------------------------------------
    // المرحلة 1: تنسيق نص جزئي (Bold/Italic/Underline/لون/تظليل/خط/حجم) —
    // docs/rich-text-and-image-editing-plan.md. RichTextSanitizer::clean() لوحده (يونيت)،
    // وبعدها feature tests تتأكد إن الحفظ/الرندر الفعلي بيستخدمه صح.
    // ---------------------------------------------------------------------

    public function test_rich_text_sanitizer_keeps_allowed_formatting_tags_and_span_styles(): void
    {
        $html = '<b>تخين</b> نص عادي <span style="color:#ff0000">أحمر</span> و<i>مايل</i> و<u>تحته خط</u>';

        $clean = RichTextSanitizer::clean($html);

        $this->assertStringContainsString('<b>تخين</b>', $clean);
        $this->assertStringContainsString('<span style="color: #ff0000">أحمر</span>', $clean);
        $this->assertStringContainsString('<i>مايل</i>', $clean);
        $this->assertStringContainsString('<u>تحته خط</u>', $clean);
        $this->assertStringContainsString('نص عادي', $clean);
    }

    // متصفحات حقيقية (اتأكدنا فعلياً في Chromium وقت التطوير) بتولّد أشكال تانية من نفس
    // التنسيقات لازم المطهّر يقبلها: لون rgb(r, g, b) (مش hex بس، لما execCommand يعيد
    // تطبيق لون على Selection ملوّنة بالفعل)، وfont-weight/font-style/text-decoration
    // (لما المستخدم يعمل toggle-off لتنسيق موروث من كلاس CSS في التصميم، زي عنوان
    // hero_title اللي عنده font-bold ثابت في أغلب الـ16 layout).
    public function test_rich_text_sanitizer_accepts_browser_generated_toggle_and_rgb_styles(): void
    {
        $cases = [
            '<span style="color: rgb(255, 0, 0); background-color: rgb(0, 255, 0)">rgb</span>' => ['color: rgb(255, 0, 0)', 'background-color: rgb(0, 255, 0)'],
            '<span style="font-weight: normal;">un-bold</span>' => ['font-weight: normal'],
            '<span style="font-weight: bold">bold</span>' => ['font-weight: bold'],
            '<span style="font-style: italic">italic</span>' => ['font-style: italic'],
            '<span style="text-decoration: underline">underline</span>' => ['text-decoration: underline'],
        ];

        foreach ($cases as $html => $expectedFragments) {
            $clean = RichTextSanitizer::clean($html);
            foreach ($expectedFragments as $fragment) {
                $this->assertStringContainsString($fragment, $clean, "expected \"$fragment\" in cleaned output of: $html");
            }
        }
    }

    public function test_rich_text_sanitizer_rejects_out_of_range_rgb_and_font_weight(): void
    {
        $this->assertSame('<span>x</span>', RichTextSanitizer::clean('<span style="color: rgb(999, 0, 0)">x</span>'));
        $this->assertSame('<span>x</span>', RichTextSanitizer::clean('<span style="font-weight: 999">x</span>'));
    }

    public static function maliciousRichTextPayloads(): array
    {
        return [
            // نص جوّه <script> مش خطر لوحده — بيترندر كنص عادي escaped، مش بينفّذ. الخطر
            // الوحيد المفروض يتشال هو الـtag نفسه.
            'script tag' => ['<script>alert(1)</script>', ['<script']],
            'img onerror' => ['<img src=x onerror=alert(1)>', ['<img', 'onerror']],
            'span style javascript url' => ['<span style="background:url(javascript:alert(1))">text</span>', ['javascript:', 'url(']],
            'anchor javascript href' => ['<a href="javascript:alert(1)">click</a>', ['<a ', 'javascript:']],
            'svg onload' => ['<svg onload=alert(1)>bad</svg>', ['<svg', 'onload']],
            'unclosed nested tags' => ['<b><i>text</b></i>', []],
            'on-attribute on span' => ['<span onclick="alert(1)" style="color:#123456">x</span>', ['onclick']],
            'invalid font key' => ['<span style="font-family: var(--font-does-not-exist)">x</span>', ['does-not-exist']],
            'out of range font size' => ['<span style="font-size: 999px">x</span>', ['999px']],
        ];
    }

    #[DataProvider('maliciousRichTextPayloads')]
    public function test_rich_text_sanitizer_strips_dangerous_payloads(string $payload, array $mustNotContain): void
    {
        $clean = RichTextSanitizer::clean($payload);

        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('onerror=', $clean);
        $this->assertStringNotContainsString('onclick=', $clean);
        $this->assertStringNotContainsString('onload=', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringNotContainsString('<img', $clean);
        $this->assertStringNotContainsString('<a ', $clean);
        $this->assertStringNotContainsString('<svg', $clean);

        foreach ($mustNotContain as $needle) {
            $this->assertStringNotContainsString($needle, $clean, "unexpected \"$needle\" survived sanitization of: $payload");
        }
    }

    public function test_rich_text_sanitizer_is_idempotent(): void
    {
        $payloads = [
            '<b>تخين</b> <span style="color:#ff0000; font-size: 20px">لون وحجم</span>',
            '<script>alert(1)</script><img src=x onerror=alert(1)>',
            'نص عادي من غير أي تنسيق',
        ];

        foreach ($payloads as $payload) {
            $once = RichTextSanitizer::clean($payload);
            $twice = RichTextSanitizer::clean($once);
            $this->assertSame($once, $twice, "sanitizer is not idempotent for: $payload");
        }
    }

    public function test_saving_legit_rich_text_content_renders_it_on_the_published_site(): void
    {
        $user = User::factory()->create();
        $project = $this->buildProjectWithSite();
        $project->site->update(['status' => 'published']);

        $response = $this->actingAs($user)->put(route('projects.site.update', $project), [
            'content' => [
                'hero_title' => '<b>تخين</b> نص عادي <span style="color:#ff0000">أحمر</span>',
            ],
        ]);

        $response->assertRedirect(route('projects.show', $project));

        $site = $project->site->fresh();
        $this->assertStringContainsString('<b>تخين</b>', $site->content('hero_title'));

        $page = $this->get(route('site.show', ['siteSlug' => $site->slug]));
        $page->assertOk();
        $page->assertSee('<b>تخين</b>', false);
        $page->assertSee('<span style="color: #ff0000">أحمر</span>', false);
    }

    public function test_saving_malicious_rich_text_content_never_renders_executable_markup(): void
    {
        $user = User::factory()->create();
        $project = $this->buildProjectWithSite();
        $project->site->update(['status' => 'published']);

        $response = $this->actingAs($user)->put(route('projects.site.update', $project), [
            'content' => [
                'hero_title' => '<script>alert(1)</script><img src=x onerror=alert(document.cookie)>واضح',
            ],
        ]);

        $response->assertRedirect(route('projects.show', $project));

        $site = $project->site->fresh();

        $page = $this->get(route('site.show', ['siteSlug' => $site->slug]));
        $page->assertOk();
        $page->assertSee('واضح');
        $page->assertDontSee('<script', false);
        $page->assertDontSee('onerror=', false);
        $page->assertDontSee('<img', false);
    }

    // ---------------------------------------------------------------------
    // المرحلة 2: تكبير/تصغير/تحريك الصورة جوّه إطارها الثابت — كل خانة صورة من غير استثناء
    // (docs/rich-text-and-image-editing-plan.md، توضيح 2026-09-22).
    // ---------------------------------------------------------------------

    public function test_saving_valid_zoom_and_position_for_an_image_slot_renders_in_the_generated_css(): void
    {
        $user = User::factory()->create();
        $project = $this->buildProjectWithImageSlot();

        $response = $this->actingAs($user)->put(route('projects.site.update', $project), [
            'style' => [
                'hero_image' => ['zoom' => '1.8', 'position' => '30% 70%'],
            ],
        ]);

        $response->assertRedirect(route('projects.show', $project));

        $site = $project->site->fresh();
        $this->assertEquals(1.8, $site->styleFor('hero_image')['zoom']);
        $this->assertSame('30% 70%', $site->styleFor('hero_image')['position']);

        $page = $this->get(route('site.show', ['siteSlug' => $site->slug]));
        $page->assertOk();
        $page->assertSee('data-slot="hero_image"', false);
        $page->assertSee('object-position: 30% 70%', false);
        $page->assertSee('transform: scale(1.8)', false);
    }

    public static function invalidZoomPayloads(): array
    {
        return [
            'too small' => ['0.5'],
            'too large' => ['99'],
            'not numeric' => ['abc'],
        ];
    }

    #[DataProvider('invalidZoomPayloads')]
    public function test_out_of_range_zoom_is_rejected_while_leaving_position_untouched(string $zoom): void
    {
        $user = User::factory()->create();
        $project = $this->buildProjectWithImageSlot();

        $response = $this->actingAs($user)->put(route('projects.site.update', $project), [
            'style' => [
                'hero_image' => ['zoom' => $zoom, 'position' => ''],
            ],
        ]);

        $response->assertRedirect(route('projects.show', $project));

        $site = $project->site->fresh();
        $this->assertNull($site->styleFor('hero_image')['zoom']);
    }

    public static function invalidPositionPayloads(): array
    {
        return [
            'over 100' => ['200% 200%'],
            'script tag' => ['<script>alert(1)</script>'],
            'wrong separator' => ['50%,50%'],
            'missing percent sign' => ['50 50'],
        ];
    }

    #[DataProvider('invalidPositionPayloads')]
    public function test_malformed_position_is_rejected_while_leaving_zoom_untouched(string $position): void
    {
        $user = User::factory()->create();
        $project = $this->buildProjectWithImageSlot();

        $response = $this->actingAs($user)->put(route('projects.site.update', $project), [
            'style' => [
                'hero_image' => ['zoom' => '', 'position' => $position],
            ],
        ]);

        $response->assertRedirect(route('projects.show', $project));

        $site = $project->site->fresh();
        $this->assertNull($site->styleFor('hero_image')['position']);

        // اتأكد صريح إن القيمة الخبيثة معملتش reflect في الصفحة المُرندرة خالص.
        $page = $this->get(route('site.show', ['siteSlug' => $site->slug]));
        $page->assertOk();
        $page->assertDontSee('<script', false);
    }

    public function test_a_partial_image_style_save_does_not_touch_another_slots_text_style_override(): void
    {
        $user = User::factory()->create();
        $project = $this->buildProjectWithImageSlot();
        $site = $project->site;
        $site->update([
            'style_overrides_json' => [
                'hero_title' => ['color' => '#123456'],
            ],
        ]);

        $response = $this->actingAs($user)->put(route('projects.site.update', $project), [
            'style' => [
                'hero_image' => ['zoom' => '2', 'position' => '10% 20%'],
            ],
        ]);

        $response->assertRedirect(route('projects.show', $project));
        $site->refresh();

        $this->assertSame('#123456', $site->styleFor('hero_title')['color']);
        $this->assertEquals(2.0, $site->styleFor('hero_image')['zoom']);
    }

    public function test_resetting_image_style_clears_the_override(): void
    {
        $user = User::factory()->create();
        $project = $this->buildProjectWithImageSlot();
        $site = $project->site;
        $site->update([
            'style_overrides_json' => [
                'hero_image' => ['zoom' => 2.0, 'position' => '10% 20%'],
            ],
        ]);

        $response = $this->actingAs($user)->put(route('projects.site.update', $project), [
            'style' => [
                'hero_image' => ['zoom' => '', 'position' => ''],
            ],
        ]);

        $response->assertRedirect(route('projects.show', $project));
        $site->refresh();

        $this->assertNull($site->styleFor('hero_image')['zoom']);
        $this->assertNull($site->styleFor('hero_image')['position']);
    }

    public function test_every_layout_renders_a_data_slot_attribute_on_its_image_tag(): void
    {
        $user = User::factory()->create();

        foreach (Template::LAYOUTS as $layout) {
            $template = Template::factory()->create(['kind' => 'landing', 'layout' => $layout]);
            $template->slots()->create(['section_key' => 'hero', 'key' => 'hero_title', 'label_ar' => 'عنوان', 'slot_type' => 'text', 'sort_order' => 1]);
            $template->slots()->create(['section_key' => 'hero', 'key' => 'hero_image', 'label_ar' => 'صورة', 'slot_type' => 'image', 'sort_order' => 2]);
            $template->slots()->create(['section_key' => 'gallery', 'key' => 'gallery_image_1', 'label_ar' => 'صورة معرض', 'slot_type' => 'image', 'sort_order' => 3]);

            $project = Project::factory()->for($template)->create();
            $site = GeneratedSite::factory()->for($project)->create([
                'content_json' => [
                    'hero_title' => 'عنوان',
                    'hero_image' => '/storage/site-images/a.jpg',
                    'gallery_image_1' => '/storage/site-images/b.jpg',
                ],
                'status' => 'published',
            ]);

            // hero_image مش كل الـ16 تصميم بيعرضها في قسم الهيرو (بعض التصاميم، زي bold،
            // هيرو نص بحت بالتصميم عمداً) — الثابت المضمون في كل التصاميم هو قسم الجاليري.
            $page = $this->actingAs($user)->get(route('site.show', ['siteSlug' => $site->slug]));
            $page->assertOk("layout [{$layout}] failed to render");
            $page->assertSee('data-slot="gallery_image_1"', false);
        }
    }
}
