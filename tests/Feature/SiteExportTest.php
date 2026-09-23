<?php

namespace Tests\Feature;

use App\Models\GeneratedSite;
use App\Models\Project;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class SiteExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_button_shows_for_landing_projects_and_hides_for_wordpress_projects(): void
    {
        $user = User::factory()->create();

        $landingTemplate = Template::factory()->create(['kind' => 'landing']);
        $landingProject = Project::factory()->for($landingTemplate)->create();
        GeneratedSite::factory()->for($landingProject)->create();

        $wordpressTemplate = Template::factory()->create(['kind' => 'wordpress']);
        $wordpressProject = Project::factory()->for($wordpressTemplate)->create();
        GeneratedSite::factory()->for($wordpressProject)->create();

        $landingResponse = $this->actingAs($user)->get(route('projects.show', $landingProject));
        $landingResponse->assertOk();
        $landingResponse->assertSee('صدّر الموقع');
        $landingResponse->assertSee(route('projects.site.export', $landingProject), false);

        $wordpressResponse = $this->actingAs($user)->get(route('projects.show', $wordpressProject));
        $wordpressResponse->assertOk();
        $wordpressResponse->assertDontSee('صدّر الموقع');
    }

    public function test_exporting_a_landing_site_downloads_a_zip_with_rendered_html_and_bundled_assets(): void
    {
        $user = User::factory()->create();

        $template = Template::factory()->create(['kind' => 'landing']);
        $template->slots()->create([
            'section_key' => 'hero',
            'key' => 'hero_title',
            'label_ar' => 'العنوان',
            'slot_type' => 'text',
            'sort_order' => 1,
        ]);
        $project = Project::factory()->for($template)->create();
        $site = GeneratedSite::factory()->for($project)->create([
            'content_json' => ['hero_title' => 'أهلاً بيكم في المطعم'],
        ]);

        $response = $this->actingAs($user)->get(route('projects.site.export', $project));

        $response->assertOk();

        $zipPath = $response->baseResponse->getFile()->getPathname();
        $this->assertFileExists($zipPath);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath) === true);

        $html = $zip->getFromName('index.html');
        $this->assertNotFalse($html);
        $this->assertStringContainsString('أهلاً بيكم في المطعم', $html);
        $this->assertStringContainsString('href="assets/app.css"', $html);

        $this->assertNotFalse($zip->getFromName('assets/app.css'));

        $zip->close();

        $site->refresh();
        $this->assertNotNull($site->exported_at);
    }

    public function test_exporting_rewrites_an_uploaded_image_slot_path_and_bundles_the_file(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $template = Template::factory()->create(['kind' => 'landing']);
        $template->slots()->create([
            'section_key' => 'hero',
            'key' => 'hero_image',
            'label_ar' => 'صورة',
            'slot_type' => 'image',
            'sort_order' => 1,
        ]);
        $project = Project::factory()->for($template)->create();
        $site = GeneratedSite::factory()->for($project)->create();

        $file = UploadedFile::fake()->image('hero.jpg');
        $this->actingAs($user)->put(route('projects.site.update', $project), [
            'content_files' => ['hero_image' => $file],
        ]);

        $site->refresh();
        $storedPath = $site->content('hero_image');
        $this->assertNotNull($storedPath);

        $response = $this->actingAs($user)->get(route('projects.site.export', $project));
        $response->assertOk();

        $zipPath = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive();
        $zip->open($zipPath);

        $html = $zip->getFromName('index.html');
        $this->assertStringNotContainsString('/storage/', $html);
        $this->assertMatchesRegularExpression('#src="assets/images/[a-zA-Z0-9._-]+\.jpg"#', $html);

        $filename = basename($storedPath);
        $this->assertNotFalse($zip->getFromName('assets/images/'.$filename));

        $zip->close();
    }

    // Phase 7 — التصميمات البصرية الجديدة (modern/gallery) بتستخدم نفس نظام تصدير الصور بالظبط،
    // حتى لما الصورة بتترندر جوّه CSS (background-image: url(...)) بدل <img> عادي زي هيرو الـ
    // gallery layout — لازم المسار يتحول لنسبي جوّه الـ url() برضه، مش بس جوّه src=.
    // ملحوظة (المرحلة 2، 2026-09-22): كان الاسم القديم "...inside_css_url_functions_too"
    // لأن هيرو تصميم gallery كان بيترندر بـ background-image: url(...) على الـsection —
    // اتحول لـ<img data-slot> حقيقي (تكبير/تحريك الصورة، docs/rich-text-and-image-editing-
    // plan.md) فمفيش أي url() لصور خالص في أي layout دلوقتي، بس rewriteImagePaths() نفسها
    // (SiteExportService) بتشتغل على $item['value'] بغض النظر عن مكان استخدامه في القالب،
    // فالتست لسه بيتحقق من نفس الحاجة (تصدير layout غير classic بيعيد كتابة مسار صورة
    // الهيرو صح)، بس في مكانها الجديد (src=، مش url()).
    public function test_exporting_a_non_classic_layout_rewrites_hero_image_paths_too(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $template = Template::factory()->create(['kind' => 'landing', 'layout' => 'gallery']);
        $template->slots()->create([
            'section_key' => 'hero', 'key' => 'hero_title', 'label_ar' => 'العنوان',
            'slot_type' => 'text', 'sort_order' => 1,
        ]);
        $template->slots()->create([
            'section_key' => 'hero', 'key' => 'hero_image', 'label_ar' => 'صورة الغلاف',
            'slot_type' => 'image', 'sort_order' => 2,
        ]);
        $project = Project::factory()->for($template)->create();
        $site = GeneratedSite::factory()->for($project)->create(['content_json' => ['hero_title' => 'أهلاً بيكم']]);

        $file = UploadedFile::fake()->image('cover.jpg');
        $this->actingAs($user)->put(route('projects.site.update', $project), [
            'content_files' => ['hero_image' => $file],
        ]);

        $response = $this->actingAs($user)->get(route('projects.site.export', $project));
        $response->assertOk();

        $zipPath = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive();
        $zip->open($zipPath);

        $html = $zip->getFromName('index.html');
        $this->assertStringNotContainsString('/storage/', $html);
        $this->assertMatchesRegularExpression('~data-slot="hero_image" src="assets/images/[a-zA-Z0-9._-]+\.jpg"~', $html);

        $zip->close();
    }

    public function test_exporting_a_wordpress_project_redirects_with_an_error_and_does_not_mark_it_as_exported(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['kind' => 'wordpress']);
        $project = Project::factory()->for($template)->create();
        $site = GeneratedSite::factory()->for($project)->create();

        $response = $this->actingAs($user)->get(route('projects.site.export', $project));

        $response->assertRedirect(route('projects.show', $project));
        $response->assertSessionHas('status');

        $site->refresh();
        $this->assertNull($site->exported_at);
    }
}
