<?php

namespace Tests\Feature;

use App\Models\GeneratedSite;
use App\Models\Project;
use App\Models\Template;
use App\Models\User;
use App\Support\FollowUpIntent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// (2026-10-11) تصميمات الـ3D (تصميم لكل فئة، site/partials/depth): كل واحد بيفتح كموقع عام وفي المحرر
// المباشر، وكل خانة ليها data-slot (التعديل الحر بيعتمد عليها)، والشات بيطبّقه بالاسم.
class Depth3dTemplatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_3d_template_renders_publicly_and_in_the_live_editor_with_every_slot(): void
    {
        $this->artisan('barq:seed-template-library')->assertSuccessful();
        $templates = Template::with(['slots', 'variants'])->where('slug', 'like', '%-3d-%')->get();

        $scenes = count(\App\Support\SceneLibrary::all());
        $this->assertCount($scenes, $templates, 'every drawing is its own template');
        $this->assertCount(20, $templates->pluck('category')->unique(), 'every category has 3D designs');
        $this->assertCount($scenes, $templates->pluck('layout')->unique());

        $member = User::factory()->member()->create();

        foreach ($templates as $template) {
            $project = Project::factory()->for($template)->create([
                'template_variant_id' => $template->defaultVariant()->id,
                'created_by' => $member->id,
            ]);
            $site = GeneratedSite::factory()->for($project)->create(['content_json' => [], 'status' => 'published']);

            $public = $this->get(route('site.show', ['siteSlug' => $site->slug]));
            $public->assertOk();
            $html = $public->getContent();
            $this->assertSame(2, substr_count($html, 'class="bq3d-scene"'), "{$template->layout}: scene in hero + contact");
            $this->assertStringContainsString('bq3d-still', $html, "{$template->layout}: second scene is static");
            $this->assertStringNotContainsString('live-editor.js', $html);

            foreach ($template->slots->filter(fn ($slot) => filled($slot->default_value)) as $slot) {
                $this->assertStringContainsString('data-slot="'.$slot->key.'"', $html, "{$template->layout}: slot {$slot->key}");
            }

            $editor = $this->actingAs($member)->get(route('projects.site.live-edit', $project));
            $editor->assertOk();
            $editor->assertSee('bq-live-editable', false);
            $editor->assertSee('live-editor.js', false);
            auth()->logout();
        }
    }

    public function test_the_chat_applies_a_3d_template_by_name(): void
    {
        $this->artisan('barq:seed-template-library')->assertSuccessful();
        $brew = Template::where('layout', 'brew')->firstOrFail();
        $plain = Template::with('slots')->where('category', $brew->category)->where('layout', 'classic')->firstOrFail();
        $project = Project::factory()->for($plain)->create();
        GeneratedSite::factory()->for($project)->create(['content_json' => []]);
        $project = $project->fresh(['template.slots', 'site']);

        foreach (['غير القالب لقالب كافيه 3D', 'عايز قالب الكافيه الثري دي', 'حط قالب الكافيه ثلاثي الأبعاد'] as $message) {
            $decision = FollowUpIntent::detect($message, false, $project, $project->site);
            $this->assertSame('change_template', $decision['action'], $message);
            $this->assertSame($brew->id, $decision['template_id'] ?? null, $message);
        }

        // كل الـ20 بالاسم، من أول قالب عادي في نفس الفئة («شركة مقاولات 3D» من «شركة مقاولات» كانت بتتعادل
        // مع «استوديو تصميم ثلاثي الأبعاد» — 2026-10-11)
        foreach (Template::where('slug', 'like', '%-3d-%')->get() as $target) {
            $from = Template::with('slots')->where('category', $target->category)->where('slug', 'not like', '%-3d-%')->orderBy('id')->firstOrFail();
            $p = Project::factory()->for($from)->create();
            GeneratedSite::factory()->for($p)->create(['content_json' => []]);
            $p = $p->fresh(['template.slots', 'site']);
            $message = 'غير القالب لقالب '.trim(explode('—', $target->name)[0]);
            $this->assertSame($target->id, FollowUpIntent::detect($message, false, $p, $p->site)['template_id'] ?? null, $message);
        }
    }
}
