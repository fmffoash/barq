<?php

namespace Tests\Feature;

use App\Models\GeneratedSite;
use App\Models\Project;
use App\Models\Template;
use App\Models\TemplateVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteRenderingTest extends TestCase
{
    use RefreshDatabase;

    private function siteUrl(GeneratedSite $site, string $path = '/'): string
    {
        return 'http://'.$site->slug.'.'.config('barq.base_domain').$path;
    }

    public function test_a_generated_site_renders_its_filled_content_at_its_subdomain(): void
    {
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

        $response = $this->get($this->siteUrl($site));

        $response->assertOk();
        $response->assertSee('أهلاً بيكم في المطعم');
    }

    public function test_sections_with_no_filled_content_are_not_rendered(): void
    {
        $template = Template::factory()->create(['kind' => 'landing']);
        $template->slots()->create([
            'section_key' => 'hero',
            'key' => 'hero_title',
            'label_ar' => 'العنوان',
            'slot_type' => 'text',
            'sort_order' => 1,
        ]);
        $template->slots()->create([
            'section_key' => 'services',
            'key' => 'services_list',
            'label_ar' => 'الخدمات',
            'slot_type' => 'list',
            'sort_order' => 2,
        ]);
        $project = Project::factory()->for($template)->create();
        $site = GeneratedSite::factory()->for($project)->create([
            'content_json' => ['hero_title' => 'أهلاً بيكم'],
        ]);

        $response = $this->get($this->siteUrl($site));

        $response->assertOk();
        $response->assertSee('id="hero"', false);
        $response->assertDontSee('id="services"', false);
    }

    public function test_a_site_with_no_filled_content_shows_a_being_prepared_placeholder(): void
    {
        $template = Template::factory()->create(['kind' => 'landing']);
        $template->slots()->create([
            'section_key' => 'hero',
            'key' => 'hero_title',
            'label_ar' => 'العنوان',
            'slot_type' => 'text',
            'sort_order' => 1,
        ]);
        $project = Project::factory()->for($template)->create();
        $site = GeneratedSite::factory()->for($project)->create(['content_json' => []]);

        $response = $this->get($this->siteUrl($site));

        $response->assertOk();
        $response->assertSee('الموقع لسه بيتجهّز');
    }

    public function test_visiting_an_unknown_subdomain_returns_a_404(): void
    {
        $response = $this->get('http://ghost-slug-that-does-not-exist.'.config('barq.base_domain').'/');

        $response->assertNotFound();
    }

    public function test_an_archived_site_returns_a_404_like_a_nonexistent_one(): void
    {
        $template = Template::factory()->create(['kind' => 'landing']);
        $project = Project::factory()->for($template)->create();
        $site = GeneratedSite::factory()->for($project)->create(['status' => 'archived']);

        $response = $this->get($this->siteUrl($site));

        $response->assertNotFound();
    }

    public function test_wordpress_kind_templates_show_a_coming_soon_placeholder(): void
    {
        $template = Template::factory()->create(['kind' => 'wordpress']);
        $project = Project::factory()->for($template)->create(['name' => 'ووردبريس تجريبي']);
        $site = GeneratedSite::factory()->for($project)->create();

        $response = $this->get($this->siteUrl($site));

        $response->assertOk();
        $response->assertSee('ووردبريس تجريبي');
        $response->assertSee('لسه بيتجهّز');
    }

    // Phase 5 — لما الموقع يتعمله site فعلي على شبكة ووردبريس، زوّار الرابط دلوقتي بيتحوّلوا
    // مباشرة لموقعه الحقيقي هناك بدل ما يشوفوا صفحة "قريباً" (اللي عمرها ما هتعرض ووردبريس فعلي).
    public function test_a_provisioned_wordpress_site_redirects_visitors_to_its_real_wordpress_url(): void
    {
        $template = Template::factory()->create(['kind' => 'wordpress']);
        $project = Project::factory()->for($template)->create();
        $site = GeneratedSite::factory()->for($project)->wordpressProvisioned()->create();

        $response = $this->get($this->siteUrl($site));

        $response->assertRedirect($site->wp_site_url);
    }

    public function test_variant_colors_are_applied_as_css_custom_properties(): void
    {
        $template = Template::factory()->create(['kind' => 'landing']);
        $template->slots()->create([
            'section_key' => 'hero',
            'key' => 'hero_title',
            'label_ar' => 'العنوان',
            'slot_type' => 'text',
            'sort_order' => 1,
        ]);
        $variant = TemplateVariant::factory()->for($template)->create([
            'colors_json' => ['primary' => '#123456'],
        ]);
        $project = Project::factory()->for($template)->create(['template_variant_id' => $variant->id]);
        $site = GeneratedSite::factory()->for($project)->create([
            'content_json' => ['hero_title' => 'أهلاً بيكم'],
        ]);

        $response = $this->get($this->siteUrl($site));

        $response->assertOk();
        $response->assertSee('--site-primary: #123456;', false);
    }

    public function test_admin_routes_remain_unaffected_on_the_plain_host(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
    }
}
