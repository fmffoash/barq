<?php

namespace Tests\Feature;

use App\Models\GeneratedSite;
use App\Models\Project;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
