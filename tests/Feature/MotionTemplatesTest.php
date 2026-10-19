<?php

namespace Tests\Feature;

use App\Models\GeneratedSite;
use App\Models\Project;
use App\Models\Template;
use App\Models\User;
use App\Services\SiteExportService;
use App\Services\SiteRenderer;
use App\Services\TemplatePreviewService;
use App\Support\MotionCopy;
use App\Support\SceneLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class MotionTemplatesTest extends TestCase
{
    use RefreshDatabase;

    private function seedMotion(): Template
    {
        $this->artisan('barq:seed-motion-templates')->assertSuccessful();

        return Template::where('layout', 'motion')->firstOrFail();
    }

    public function test_import_adds_98_templates_without_changing_existing_projects_or_repeating_rows(): void
    {
        $existing = Template::factory()->create(['layout' => 'modern']);
        $project = Project::factory()->for($existing)->create();
        $site = GeneratedSite::factory()->for($project)->create(['content_json' => ['hero_title' => 'محتوى محفوظ']]);
        $before = $site->fresh()->toArray();

        $this->seedMotion();
        $this->artisan('barq:seed-motion-templates')->assertSuccessful();

        $this->assertDatabaseCount('templates', 99);
        $this->assertSame('modern', $existing->fresh()->layout);
        $this->assertSame($before, $site->fresh()->toArray());
        $this->assertSame(20, Template::where('layout', 'motion')->distinct()->count('category'));
    }

    public function test_reimport_preserves_user_edits_and_archived_motion_templates(): void
    {
        $template = $this->seedMotion();
        $template->update(['name' => 'قالب معدّل', 'is_active' => false]);
        $template->forceFill(['archived_at' => now()])->save();
        $template->slots()->where('key', 'hero_title')->firstOrFail()->update(['default_value' => 'عنوان خاص']);

        $this->artisan('barq:seed-motion-templates')->assertSuccessful();

        $this->assertSame('قالب معدّل', $template->fresh()->name);
        $this->assertNotNull($template->fresh()->archived_at);
        $this->assertFalse($template->fresh()->is_active);
        $this->assertSame('عنوان خاص', $template->slots()->where('key', 'hero_title')->firstOrFail()->default_value);
        $this->assertDatabaseCount('templates', 98);
    }

    public function test_all_imported_templates_render_editable_content_in_the_native_preview(): void
    {
        $this->seedMotion();
        $this->actingAs(User::factory()->create());

        foreach (Template::where('layout', 'motion')->with('slots')->get() as $template) {
            $response = $this->get(route('templates.preview', $template));
            $response->assertOk();
            $response->assertSee('motion/motion.css', false);
            $response->assertSee('motion/motion.js', false);
            $response->assertSee($template->slots->firstWhere('key', 'hero_title')->default_value);
            foreach ($template->slots as $slot) {
                if (filled($slot->default_value)) {
                    $response->assertSee('data-slot="'.$slot->key.'"', false);
                }
            }
        }

        $this->assertDatabaseCount('projects', 0);
        $this->assertDatabaseCount('generated_sites', 0);
    }

    public function test_motion_content_and_uploaded_images_can_be_saved_and_live_editing_does_not_run_motion(): void
    {
        Storage::fake('public');
        $template = $this->seedMotion();
        $user = User::factory()->create();
        $project = Project::factory()->for($template)->create(['created_by' => $user->id, 'template_variant_id' => $template->defaultVariant()->id]);
        $site = GeneratedSite::factory()->for($project)->create();

        $this->actingAs($user)->put(route('projects.site.update', $project), [
            'content' => ['hero_title' => 'عنوان العميل الجديد'],
            'content_files' => ['hero_image' => UploadedFile::fake()->image('new-cover.jpg')],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $site->refresh();
        $this->assertSame('عنوان العميل الجديد', $site->content_json['hero_title']);
        Storage::disk('public')->assertExists(ltrim(str_replace('/storage/', '', $site->content_json['hero_image']), '/'));
        $this->get(route('projects.site.live-edit', $project))
            ->assertOk()
            ->assertSee('عنوان العميل الجديد')
            ->assertDontSee('motion/motion.js', false)
            ->assertSee('bq-live-editable', false);
    }

    public function test_static_export_contains_motion_fonts_and_every_local_image_without_server_paths(): void
    {
        $template = $this->seedMotion();
        $project = Project::factory()->for($template)->create(['template_variant_id' => $template->defaultVariant()->id]);
        $site = GeneratedSite::factory()->for($project)->create(['content_json' => ['hero_title' => 'نسخة العميل المستقلة']]);

        $path = app(SiteExportService::class)->export($site);

        $zip = new ZipArchive;
        $zip->open($path);
        try {
            $html = $zip->getFromName('index.html');
            $this->assertStringContainsString('نسخة العميل المستقلة', $html);
            $this->assertStringContainsString('href="assets/motion/motion.css"', $html);
            $this->assertStringContainsString('src="assets/motion/motion.js"', $html);
            foreach (['motion.css', 'motion.js', 'tajawal-400.woff2', 'tajawal-700.woff2', 'tajawal-900.woff2', 'symbols.ttf'] as $asset) {
                $this->assertNotFalse($zip->getFromName('assets/motion/'.$asset));
            }
            preg_match_all('/<img[^>]+src="([^"]+)"/', $html, $images);
            $this->assertNotEmpty($images[1]);
            foreach ($images[1] as $image) {
                $this->assertStringStartsWith('assets/images/', $image);
                $this->assertNotFalse($zip->getFromName($image), $image);
            }
            $this->assertStringNotContainsString('src="/images/', $html);
            $this->assertStringNotContainsString('../assets/', $html);
        } finally {
            $zip->close();
            unlink($path);
        }
    }

    public function test_changing_motion_art_direction_invalidates_the_thumbnail_fingerprint(): void
    {
        $template = $this->seedMotion();
        $previews = app(TemplatePreviewService::class);
        $fingerprint = $previews->fingerprint($template);

        $template->update(['motion_profile' => [...$template->motion_profile, 'composition' => 'studio']]);

        $this->assertNotSame($fingerprint, $previews->fingerprint($template->fresh()));
    }

    // MySQL بيعيد ترتيب مفاتيح عمود JSON — نفس الشكل لازم يطلع نفس البصمة وإلا صور الكروت بتختفي على السيرفر.
    public function test_thumbnail_fingerprint_ignores_motion_profile_key_order(): void
    {
        $template = $this->seedMotion();
        $previews = app(TemplatePreviewService::class);
        $fingerprint = $previews->fingerprint($template);

        $reordered = $template->motion_profile;
        krsort($reordered);
        $template->update(['motion_profile' => $reordered]);

        $this->assertSame($fingerprint, $previews->fingerprint($template->fresh()));
    }

    // (2026-10-19) كل قالب له مجسم عايم من نوع نشاطه (مش ✨ عامة)، مختلف عن صورة الهيرو، وموجود فعلاً.
    public function test_every_template_has_its_own_existing_floating_object_and_tailored_hero_copy(): void
    {
        $definitions = json_decode(file_get_contents(database_path('data/motion-templates.json')), true);

        foreach ($definitions as $definition) {
            $scene = $definition['profile']['scene_image'];
            $hero = collect($definition['slots'])->firstWhere('key', 'hero_image')['default_value'];
            $this->assertFileExists(public_path(ltrim($scene, '/')), $definition['slug']);
            $this->assertNotSame('/images/scenes3d/sparkles_3d.png', $scene, $definition['slug']);
            $this->assertNotSame($hero, $scene, $definition['slug']);
            $this->assertContains($definition['profile']['theme'], MotionCopy::themes(), $definition['slug']);
        }

        $template = $this->seedMotion();
        $this->actingAs(User::factory()->create());
        $copy = MotionCopy::for($template->motion_profile['theme'], $template->name);
        $this->get(route('templates.preview', $template))
            ->assertOk()
            ->assertSee($copy['tagline'])
            ->assertSee($copy['sticker'][0])
            ->assertDontSee('MADE FOR THE MOMENT')
            ->assertDontSee('تفاصيل صغيرة');
    }

    public function test_reimport_refreshes_the_look_profile_but_keeps_slots_and_colors(): void
    {
        $template = $this->seedMotion();
        $original = $template->motion_profile;
        $template->update(['motion_profile' => [...$original, 'scene_image' => '/images/scenes3d/sparkles_3d.png']]);
        $template->slots()->where('key', 'hero_title')->firstOrFail()->update(['default_value' => 'عنوان خاص']);
        $template->defaultVariant()->update(['colors_json' => [...$template->defaultVariant()->colors_json, 'primary' => '#123456']]);

        $this->artisan('barq:seed-motion-templates')->assertSuccessful();

        $this->assertEquals($original, $template->fresh()->motion_profile);
        $this->assertSame('عنوان خاص', $template->slots()->where('key', 'hero_title')->firstOrFail()->default_value);
        $this->assertSame('#123456', $template->fresh()->defaultVariant()->colors_json['primary']);
    }

    public function test_chosen_scene_replaces_the_floating_object_instead_of_stacking_an_overlay(): void
    {
        $template = $this->seedMotion();
        $user = User::factory()->create();
        $project = Project::factory()->for($template)->create(['created_by' => $user->id, 'template_variant_id' => $template->defaultVariant()->id]);
        GeneratedSite::factory()->for($project)->create(['scene_override' => 'helix']);
        $main = SceneLibrary::IMAGE_SCENES['helix']['main'];

        $html = app(SiteRenderer::class)->render($project->fresh()->site);
        $this->assertSame('/images/scenes3d/'.$main, $html['motionProfile']['scene_image']);

        $page = view('site.show', $html)->render();
        $this->assertStringContainsString('<div class="bm-object" aria-hidden="true"><img src="/images/scenes3d/'.$main.'"', $page);
        $this->assertStringNotContainsString('bq3d-overlay', $page);
    }
}
