<?php

namespace Tests\Feature;

use App\Console\Commands\SeedTemplateLibrary;
use App\Models\GeneratedSite;
use App\Models\Project;
use App\Models\Template;
use App\Models\User;
use App\Services\AiProjectAssistantService;
use App\Support\FollowUpIntent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// (2026-10-11) فؤاد: مفضلة/أرشيف/حذف للقوالب، والرسومات الـ3D تتحط على أي قالب (ميكس).
class TemplateManagementAndScenesTest extends TestCase
{
    use RefreshDatabase;

    public function test_favorites_archive_and_delete(): void
    {
        $this->artisan('barq:seed-template-library')->assertSuccessful();
        $admin = User::factory()->create();
        $member = User::factory()->member()->create();
        [$a, $b] = Template::where('layout', 'classic')->take(2)->get()->all();

        // مفضلة لكل يوزر لوحده
        $this->actingAs($member)->post(route('templates.favorite', $a))->assertRedirect();
        $this->actingAs($member)->get(route('templates.index', ['view' => 'favorites']))->assertOk()->assertSee($a->name)->assertDontSee($b->name);
        $this->assertSame(0, $admin->favoriteTemplates()->count());
        $this->actingAs($member)->post(route('templates.favorite', $a));
        $this->assertSame(0, $member->favoriteTemplates()->count(), 'second click removes it');

        // الأرشيف: أدمن بس، بيختفي من الاختيار، والمكتبة مابترجّعوش
        $this->actingAs($member)->post(route('templates.archive', $a))->assertForbidden();
        $this->actingAs($admin)->post(route('templates.archive', $a))->assertRedirect();
        $a->refresh();
        $this->assertNotNull($a->archived_at);
        $this->assertFalse($a->is_active);
        $this->actingAs($admin)->get(route('templates.index'))->assertDontSee('>'.$a->name.'<', false);
        $this->actingAs($admin)->get(route('templates.index', ['view' => 'archived']))->assertSee($a->name);
        $this->artisan('barq:seed-template-library')->assertSuccessful();
        $this->assertFalse($a->fresh()->is_active, 'library update keeps it archived');
        $this->actingAs($admin)->post(route('templates.archive', $a));
        $this->assertTrue($a->fresh()->is_active);

        // الحذف: مرفوض لو عليه مشاريع، ونهائي لو مالوش (والمكتبة مابترجّعوش)
        Project::factory()->for($b)->create();
        $this->actingAs($admin)->delete(route('templates.destroy', $b));
        $this->assertNotNull($b->fresh());
        $this->actingAs($member)->delete(route('templates.destroy', $a))->assertForbidden();
        $this->actingAs($admin)->delete(route('templates.destroy', $a))->assertRedirect();
        $this->assertNull(Template::find($a->id));
        $this->assertContains($a->slug, SeedTemplateLibrary::deletedSlugs());
        $this->artisan('barq:seed-template-library')->assertSuccessful();
        $this->assertFalse(Template::where('slug', $a->slug)->exists(), 'deleted library template never comes back');
    }

    public function test_a_3d_drawing_can_be_mixed_into_any_template(): void
    {
        $this->artisan('barq:seed-template-library')->assertSuccessful();
        $member = User::factory()->member()->create();
        $classic = Template::with('slots')->where('layout', 'classic')->where('category', 'عيادات وخدمات طبية')->firstOrFail();
        $project = Project::factory()->for($classic)->create(['created_by' => $member->id, 'template_variant_id' => $classic->defaultVariant()->id]);
        $site = GeneratedSite::factory()->for($project)->create(['content_json' => [], 'status' => 'published']);

        // من درج "تصميم الموقع" في التعديل الحر
        $this->actingAs($member)->put(route('projects.site.update', $project), ['design_form' => 1, 'scene_override' => 'tooth']);
        $this->assertSame('tooth', $site->fresh()->scene_override);
        $html = $this->get(route('site.show', ['siteSlug' => $site->slug]))->assertOk()->getContent();
        $this->assertStringContainsString('bq3d-overlay', $html);
        $this->assertStringContainsString('bq3d-scene', $html);

        // مفتاح مش موجود = من غير رسمة
        $this->actingAs($member)->put(route('projects.site.update', $project), ['design_form' => 1, 'scene_override' => '<script>']);
        $this->assertNull($site->fresh()->scene_override);

        // من الشات
        $project = $project->fresh(['template.slots', 'site']);
        $this->assertSame(['action' => 'set_scene', 'scene' => 'tooth'], FollowUpIntent::detect('حط رسمة السنة', false, $project, $project->site));
        $reply = app(AiProjectAssistantService::class)->handleFollowUp($project, 'حط رسمة الكاميرا على الموقع');
        $this->assertStringContainsString('كاميرا', $reply);
        $this->assertSame('shutter', $site->fresh()->scene_override);
        app(AiProjectAssistantService::class)->handleFollowUp($project->fresh(['template.slots', 'site']), 'شيل الرسمة');
        $this->assertNull($site->fresh()->scene_override);

        // على تصميم 3D: الرسمة المختارة بتبدّل رسمة القالب
        $brew = Template::where('layout', 'brew')->firstOrFail();
        $p2 = Project::factory()->for($brew)->create(['template_variant_id' => $brew->defaultVariant()->id]);
        $s2 = GeneratedSite::factory()->for($p2)->create(['content_json' => [], 'status' => 'published', 'scene_override' => 'pizza']);
        $html = $this->get(route('site.show', ['siteSlug' => $s2->slug]))->getContent();
        $this->assertStringNotContainsString('bq3d-cup', $html);
        $this->assertStringNotContainsString('bq3d-overlay', $html);
    }
}
