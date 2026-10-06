<?php

namespace Tests\Feature;

use App\Models\GeneratedSite;
use App\Models\Project;
use App\Models\Template;
use App\Models\TemplateVariant;
use App\Models\User;
use App\Services\TemplatePreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

// معاينة شكل القالب الحقيقي قبل اختياره (2026-10-06): صفحة معاينة كاملة + صورة حقيقية على
// الكارت بتتعرض بس لو بصمة القالب مطابقة للقطة (TemplatePreviewService).
class TemplatePreviewTest extends TestCase
{
    use RefreshDatabase;

    private string $previewsDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previewsDir = storage_path('framework/testing/template-previews-'.uniqid());
        File::ensureDirectoryExists($this->previewsDir);
        config(['barq.template_previews_dir' => $this->previewsDir]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->previewsDir);

        parent::tearDown();
    }

    private function landingTemplate(array $attributes = []): Template
    {
        $template = Template::factory()->create(['kind' => 'landing', 'layout' => 'modern', 'name' => 'مطعم الشيف', 'slug' => 'chef-modern'] + $attributes);
        $template->slots()->create(['section_key' => 'hero', 'key' => 'hero_title', 'label_ar' => 'العنوان', 'slot_type' => 'text', 'default_value' => 'طعم أصيل يجمعكم', 'sort_order' => 1]);
        $template->slots()->create(['section_key' => 'about', 'key' => 'about_text', 'label_ar' => 'من نحن', 'slot_type' => 'textarea', 'default_value' => 'مطبخ عائلي من زمان', 'sort_order' => 2]);
        TemplateVariant::factory()->for($template)->create(['is_default' => true, 'colors_json' => ['primary' => '#123456']]);

        return $template->fresh();
    }

    public function test_preview_requires_login(): void
    {
        $this->get(route('templates.preview', $this->landingTemplate()))->assertRedirect(route('login'));
    }

    public function test_preview_renders_the_real_template_with_its_default_content_without_saving_anything(): void
    {
        $template = $this->landingTemplate();

        $response = $this->actingAs(User::factory()->create())->get(route('templates.preview', $template));

        $response->assertOk();
        $response->assertSee('طعم أصيل يجمعكم');
        $response->assertSee('مطبخ عائلي من زمان');
        $response->assertSee('--site-primary: #123456', false);
        $response->assertSee('استخدم القالب ده');
        $response->assertSee(route('projects.create', ['template' => $template->id]), false);
        $this->assertSame(0, Project::count());
        $this->assertSame(0, GeneratedSite::count());
    }

    public function test_preview_of_a_wordpress_template_shows_the_coming_soon_page(): void
    {
        $template = Template::factory()->create(['kind' => 'wordpress', 'name' => 'موقع ووردبريس']);

        $this->actingAs(User::factory()->create())
            ->get(route('templates.preview', $template))
            ->assertOk()
            ->assertSee('لسه بيتجهّز');
    }

    public function test_the_card_picture_is_used_only_while_it_still_matches_the_template(): void
    {
        $template = $this->landingTemplate();
        $previews = app(TemplatePreviewService::class);

        $this->assertNull($previews->thumbnailUrl($template), 'no picture yet');

        File::put($this->previewsDir.'/chef-modern.jpg', 'jpeg-bytes');
        $previews->writeManifest(['chef-modern' => $previews->fingerprint($template)]);
        $this->assertStringContainsString('images/template-previews/chef-modern.jpg', (string) $previews->thumbnailUrl($template));

        // نفس الألوان بترتيب مفاتيح مختلف (عمود JSON في MySQL بيعيد ترتيبها) = نفس الشكل.
        $template->variants()->first()->update(['colors_json' => ['primary' => '#123456', 'accent' => '#000000']]);
        $previews->writeManifest(['chef-modern' => $previews->fingerprint($template->fresh())]);
        $template->variants()->first()->update(['colors_json' => ['accent' => '#000000', 'primary' => '#123456']]);
        $this->assertNotNull($previews->thumbnailUrl($template->fresh()));

        // فؤاد غيّر ألوان القالب بعد اللقطة: الصورة مبقتش تمثّله.
        $template->variants()->first()->update(['colors_json' => ['primary' => '#ff0000']]);
        $this->assertNull($previews->thumbnailUrl($template->fresh()));
    }

    public function test_templates_page_and_new_project_page_show_the_picture_and_a_preview_link(): void
    {
        $template = $this->landingTemplate();
        $previews = app(TemplatePreviewService::class);
        File::put($this->previewsDir.'/chef-modern.jpg', 'jpeg-bytes');
        $previews->writeManifest(['chef-modern' => $previews->fingerprint($template)]);
        $user = User::factory()->create();

        foreach ([route('templates.index'), route('projects.create'), route('templates.show', $template)] as $url) {
            $this->actingAs($user)->get($url)
                ->assertOk()
                ->assertSee('images/template-previews/chef-modern.jpg', false)
                ->assertSee(route('templates.preview', $template), false);
        }
    }

    public function test_a_slug_can_never_point_outside_the_pictures_folder(): void
    {
        $template = Template::factory()->make(['slug' => '../../evil/../x', 'kind' => 'landing']);

        $this->assertSame('evilx', app(TemplatePreviewService::class)->safeSlug($template));
    }
}
