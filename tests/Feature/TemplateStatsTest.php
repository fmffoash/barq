<?php

namespace Tests\Feature;

use App\Console\Commands\SeedTemplateLibrary;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Template;
use App\Models\TemplateEvent;
use App\Models\User;
use App\Services\TemplatePicker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// (2026-10-19) أولوية قوالب 3D في الاختيار التلقائي + سجل الاختيار/التبديل + صفحة إحصائيات القوالب.
class TemplateStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_picker_prefers_3d_templates_in_the_category_and_the_admin_can_turn_it_off(): void
    {
        $regular = Template::factory()->create(['kind' => 'landing', 'name' => 'عيادة أسنان — بسيط', 'category' => 'عيادات', 'layout' => 'modern']);
        $motion = Template::factory()->create(['kind' => 'landing', 'name' => 'مركز طبي — موشن 3D', 'category' => 'عيادات', 'layout' => 'motion']);
        $picker = new TemplatePicker(false);

        // من غير الأولوية، تخصص «أسنان» كان هيكسب.
        $this->assertSame($motion->id, $picker->pick('عيادات', 'دكتور أسنان')['template']->id);

        Setting::put(Template::PREFER_3D_SETTING, false);
        $this->assertSame($regular->id, $picker->pick('عيادات', 'دكتور أسنان')['template']->id);
    }

    public function test_category_without_3d_templates_still_picks_from_all(): void
    {
        $regular = Template::factory()->create(['kind' => 'landing', 'category' => 'محاماة', 'layout' => 'classic']);

        $this->assertSame($regular->id, (new TemplatePicker(false))->pick('محاماة', 'مكتب محاماة')['template']->id);
    }

    // الترتيب: موشن ← تصميمات 3D ← العادي، أول درجة فيها قالب في الفئة هي اللي بيتختار منها.
    public function test_motion_comes_first_then_depth_3d_then_regular(): void
    {
        $regular = Template::factory()->create(['kind' => 'landing', 'name' => 'مكتب محاماة — كلاسيك', 'category' => 'محاماة', 'layout' => 'classic']);
        $depth = Template::factory()->create(['kind' => 'landing', 'name' => 'مكتب محاماة 3D — ميزان', 'category' => 'محاماة', 'layout' => 'verdict']);
        $picker = new TemplatePicker(false);
        $this->assertSame($depth->id, $picker->pick('محاماة', 'مكتب محاماة')['template']->id);

        $motion = Template::factory()->create(['kind' => 'landing', 'name' => 'مكتب العدل — موشن 3D', 'category' => 'محاماة', 'layout' => 'motion']);
        $this->assertSame($motion->id, $picker->pick('محاماة', 'مكتب محاماة')['template']->id);
        $this->assertNotSame($regular->id, $picker->pick('محاماة', 'مكتب محاماة')['template']->id);
    }

    // على المكتبة الحقيقية: موشن الأول، والتخصص بيتحسب من motion_profile.specialty (أساميهم أسماء محلات).
    public function test_real_library_picks_the_motion_template_of_the_same_specialty(): void
    {
        $this->artisan('barq:seed-template-library')->assertSuccessful();
        $this->artisan('barq:seed-motion-templates')->assertSuccessful();

        $cases = [
            ['عيادات وخدمات طبية', 'Dr. Samy · Orthopedic Surgeon', 'عيادة عظام'],
            ['عيادات وخدمات طبية', 'دكتور أسنان في المعادي', 'عيادة أسنان'],
            ['مطاعم وكافيهات', 'Pizza place', 'بيتزا'],
            ['مطاعم وكافيهات', 'مطعم بيتزا', 'بيتزا'],
            ['عيادات وخدمات طبية', 'عيادة عيون', 'مركز عيون'],
            ['مقاولات وديكور', 'شركة مطابخ', 'مطابخ'],
            ['جيم ولياقة بدنية', 'أكاديمية سباحة للأطفال', 'أكاديمية سباحة'],
            ['صالونات وتجميل', 'باربر شوب', 'باربر'],
        ];
        foreach ($cases as [$category, $context, $specialty]) {
            $pick = (new TemplatePicker(false))->pick($category, $context);
            $this->assertSame('motion', $pick['template']->layout, $context);
            $this->assertSame($specialty, $pick['template']->motion_profile['specialty'], "{$context} → {$pick['template']->name}");
            $this->assertSame("مخصوص لـ«{$specialty}»", $pick['reason']);
        }
    }

    public function test_choosing_and_switching_templates_is_recorded_once_each(): void
    {
        $first = Template::factory()->create();
        $second = Template::factory()->create();
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        $project = Project::factory()->for($first)->create(['created_by' => $user->id]);
        $project->update(['name' => 'اسم جديد']);
        $project->update(['template_id' => $second->id]);

        $this->assertSame(2, TemplateEvent::count());
        $this->assertDatabaseHas('template_events', ['project_id' => $project->id, 'from_template_id' => null, 'to_template_id' => $first->id, 'user_id' => $user->id]);
        $this->assertDatabaseHas('template_events', ['project_id' => $project->id, 'from_template_id' => $first->id, 'to_template_id' => $second->id]);
    }

    public function test_stats_page_shows_usage_and_switches_for_admins_only(): void
    {
        $popular = Template::factory()->create(['kind' => 'landing', 'name' => 'قالب مشهور']);
        $left = Template::factory()->create(['kind' => 'landing', 'name' => 'قالب اتساب', 'layout' => 'motion']);
        Project::factory()->for($popular)->count(2)->create();
        Project::factory()->for($left)->create()->update(['template_id' => $popular->id]);

        $this->actingAs(User::factory()->member()->create())->get(route('template-stats.index'))->assertForbidden();

        $response = $this->actingAs(User::factory()->create())->get(route('template-stats.index', ['usage' => 'switched']));
        $response->assertOk()->assertSee('قالب اتساب')->assertSee('100%');
        $rows = $response->viewData('rows')->keyBy('id');
        $this->assertSame(['chosen' => 1, 'current' => 0, 'switched' => 1], array_intersect_key($rows[$left->id], array_flip(['chosen', 'current', 'switched'])));
        $this->assertFalse($rows->has($popular->id), 'the switched filter hides templates nobody left');

        $all = $this->get(route('template-stats.index'))->viewData('rows')->keyBy('id');
        $this->assertSame(3, $all[$popular->id]['chosen']);
        $this->assertSame(3, $all[$popular->id]['current']);
    }

    public function test_bulk_delete_removes_unused_templates_keeps_used_ones_and_library_does_not_bring_them_back(): void
    {
        $this->artisan('barq:seed-motion-templates')->assertSuccessful();
        $unused = Template::where('layout', 'motion')->firstOrFail();
        $used = Template::factory()->create(['name' => 'عليه مشروع']);
        Project::factory()->for($used)->create();

        $this->actingAs(User::factory()->member()->create())
            ->delete(route('template-stats.destroy'), ['ids' => [$unused->id]])->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->delete(route('template-stats.destroy'), ['ids' => [$unused->id, $used->id]])
            ->assertSessionHas('status', fn (string $message) => str_contains($message, 'اتمسح 1 قالب') && str_contains($message, 'عليه مشروع'));

        $this->assertModelMissing($unused);
        $this->assertModelExists($used);
        $this->assertContains($unused->slug, SeedTemplateLibrary::deletedSlugs());

        $this->artisan('barq:seed-motion-templates')->assertSuccessful();
        $this->assertDatabaseMissing('templates', ['slug' => $unused->slug]);
    }

    public function test_admin_toggles_the_3d_priority(): void
    {
        $this->assertTrue(Template::prefers3d());

        $this->actingAs(User::factory()->create())
            ->put(route('template-stats.prefer-3d'), ['prefer_3d' => 0])->assertRedirect();
        $this->assertFalse(Template::prefers3d());

        $this->actingAs(User::factory()->member()->create())
            ->put(route('template-stats.prefer-3d'), ['prefer_3d' => 1])->assertForbidden();
        $this->assertFalse(Template::prefers3d());
    }
}
