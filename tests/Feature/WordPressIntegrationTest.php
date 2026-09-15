<?php

namespace Tests\Feature;

use App\Models\GeneratedSite;
use App\Models\Project;
use App\Models\Template;
use App\Models\User;
use App\Services\WordPressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

// اختبارات Phase 5 (WordPress Multisite integration) — نفس أسلوب OllamaContentSuggestionTest
// بالحرف (Http::fake بدل ما نكلّم شبكة ووردبريس حقيقية)، بالإضافة لاختبارات على مستوى
// الكونترولر (الأزرار + الـ redirects) زي SiteExportTest.
class WordPressIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function configureWordPressNetwork(): void
    {
        config([
            'services.wordpress.network_url' => 'https://network.example.test',
            'services.wordpress.shared_secret' => 'test-shared-secret',
        ]);
    }

    private function wordpressSite(array $siteAttributes = []): GeneratedSite
    {
        $template = Template::factory()->create(['kind' => 'wordpress']);
        $project = Project::factory()->for($template)->create();

        return GeneratedSite::factory()->for($project)->create($siteAttributes);
    }

    // — provisionSite() ————————————————————————————————————————————————————

    public function test_provisioning_throws_when_the_project_template_is_not_wordpress(): void
    {
        $template = Template::factory()->create(['kind' => 'landing']);
        $project = Project::factory()->for($template)->create();
        $site = GeneratedSite::factory()->for($project)->create();

        $this->expectException(RuntimeException::class);

        (new WordPressService)->provisionSite($project, $site);
    }

    public function test_provisioning_is_idempotent_and_skips_the_http_call_when_already_provisioned(): void
    {
        $this->configureWordPressNetwork();

        $site = $this->wordpressSite([
            'wp_site_id' => 42,
            'wp_site_url' => 'https://already-there.example-network.test',
            'wp_admin_url' => 'https://already-there.example-network.test/wp-admin',
            'wp_provisioned_at' => now(),
        ]);

        Http::fake();

        $result = (new WordPressService)->provisionSite($site->project, $site);

        $this->assertTrue($result);
        Http::assertNothingSent();
    }

    public function test_provisioning_fails_gracefully_when_the_network_is_not_configured(): void
    {
        // صفر config — WORDPRESS_NETWORK_URL/WORDPRESS_SHARED_SECRET فاضيين بالافتراضي.
        $site = $this->wordpressSite();

        Http::fake();

        $result = (new WordPressService)->provisionSite($site->project, $site);

        $this->assertFalse($result);
        Http::assertNothingSent();
        $this->assertFalse($site->fresh()->isWordPressProvisioned());
    }

    public function test_provisioning_succeeds_and_stores_the_returned_site_data(): void
    {
        $this->configureWordPressNetwork();
        $site = $this->wordpressSite();

        Http::fake([
            'network.example.test/wp-json/barq/v1/sites' => Http::response([
                'site_id' => 17,
                'site_url' => 'https://new-site.example-network.test',
                'admin_url' => 'https://new-site.example-network.test/wp-admin',
            ], 201),
        ]);

        $result = (new WordPressService)->provisionSite($site->project, $site);

        $this->assertTrue($result);

        $site->refresh();
        $this->assertTrue($site->isWordPressProvisioned());
        $this->assertSame(17, $site->wp_site_id);
        $this->assertSame('https://new-site.example-network.test', $site->wp_site_url);
        $this->assertSame('https://new-site.example-network.test/wp-admin', $site->wp_admin_url);
        $this->assertNotNull($site->wp_provisioned_at);

        Http::assertSent(function ($request) use ($site) {
            return $request->hasHeader('Authorization', 'Bearer test-shared-secret')
                && $request['slug'] === $site->slug;
        });
    }

    public function test_provisioning_fails_gracefully_when_the_network_returns_a_non_success_status(): void
    {
        $this->configureWordPressNetwork();
        $site = $this->wordpressSite();

        Http::fake([
            'network.example.test/wp-json/barq/v1/sites' => Http::response('', 500),
        ]);

        $result = (new WordPressService)->provisionSite($site->project, $site);

        $this->assertFalse($result);
        $this->assertFalse($site->fresh()->isWordPressProvisioned());
    }

    public function test_provisioning_fails_gracefully_when_the_network_returns_an_unexpected_response_shape(): void
    {
        $this->configureWordPressNetwork();
        $site = $this->wordpressSite();

        Http::fake([
            'network.example.test/wp-json/barq/v1/sites' => Http::response([
                'site_id' => null,
            ], 201),
        ]);

        $result = (new WordPressService)->provisionSite($site->project, $site);

        $this->assertFalse($result);
        $this->assertFalse($site->fresh()->isWordPressProvisioned());
    }

    public function test_provisioning_fails_gracefully_when_the_network_is_unreachable(): void
    {
        $this->configureWordPressNetwork();
        $site = $this->wordpressSite();

        Http::fake(function () {
            throw new ConnectionException('Connection refused');
        });

        $result = (new WordPressService)->provisionSite($site->project, $site);

        $this->assertFalse($result);
        $this->assertFalse($site->fresh()->isWordPressProvisioned());
    }

    // — pushContent() ————————————————————————————————————————————————————

    public function test_pushing_content_fails_gracefully_when_the_site_is_not_provisioned_yet(): void
    {
        $this->configureWordPressNetwork();
        $site = $this->wordpressSite();

        Http::fake();

        $result = (new WordPressService)->pushContent($site);

        $this->assertFalse($result);
        Http::assertNothingSent();
    }

    public function test_pushing_content_fails_gracefully_when_the_network_is_not_configured(): void
    {
        $site = $this->wordpressSite([
            'wp_site_id' => 17,
            'wp_site_url' => 'https://x.example-network.test',
            'wp_admin_url' => 'https://x.example-network.test/wp-admin',
            'wp_provisioned_at' => now(),
        ]);

        Http::fake();

        $result = (new WordPressService)->pushContent($site);

        $this->assertFalse($result);
        Http::assertNothingSent();
    }

    public function test_pushing_content_sends_the_sites_current_content_json_and_returns_true_on_success(): void
    {
        $this->configureWordPressNetwork();
        $site = $this->wordpressSite([
            'wp_site_id' => 17,
            'wp_site_url' => 'https://x.example-network.test',
            'wp_admin_url' => 'https://x.example-network.test/wp-admin',
            'wp_provisioned_at' => now(),
            'content_json' => ['site_title' => 'مطعم فطاير', 'homepage_body' => '<p>أهلاً بيكم</p>'],
        ]);

        Http::fake([
            'network.example.test/wp-json/barq/v1/sites/17/content' => Http::response(['ok' => true], 200),
        ]);

        $result = (new WordPressService)->pushContent($site);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer test-shared-secret')
                && $request['content']['site_title'] === 'مطعم فطاير'
                && $request['content']['homepage_body'] === '<p>أهلاً بيكم</p>';
        });
    }

    public function test_pushing_content_fails_gracefully_when_the_network_returns_a_non_success_status(): void
    {
        $this->configureWordPressNetwork();
        $site = $this->wordpressSite([
            'wp_site_id' => 17,
            'wp_site_url' => 'https://x.example-network.test',
            'wp_admin_url' => 'https://x.example-network.test/wp-admin',
            'wp_provisioned_at' => now(),
        ]);

        Http::fake([
            'network.example.test/wp-json/barq/v1/sites/17/content' => Http::response('', 500),
        ]);

        $result = (new WordPressService)->pushContent($site);

        $this->assertFalse($result);
    }

    public function test_pushing_content_fails_gracefully_when_the_network_is_unreachable(): void
    {
        $this->configureWordPressNetwork();
        $site = $this->wordpressSite([
            'wp_site_id' => 17,
            'wp_site_url' => 'https://x.example-network.test',
            'wp_admin_url' => 'https://x.example-network.test/wp-admin',
            'wp_provisioned_at' => now(),
        ]);

        Http::fake(function () {
            throw new ConnectionException('Connection refused');
        });

        $result = (new WordPressService)->pushContent($site);

        $this->assertFalse($result);
    }

    // — الكونترولر (الأزرار + الـ routes) ————————————————————————————————————

    public function test_the_provisioning_button_shows_for_unprovisioned_wordpress_projects_and_the_update_button_shows_after(): void
    {
        $user = User::factory()->create();

        $unprovisionedSite = $this->wordpressSite();
        $provisionedSite = $this->wordpressSite([
            'wp_site_id' => 17,
            'wp_site_url' => 'https://x.example-network.test',
            'wp_admin_url' => 'https://x.example-network.test/wp-admin',
            'wp_provisioned_at' => now(),
        ]);

        $unprovisionedResponse = $this->actingAs($user)->get(route('projects.show', $unprovisionedSite->project));
        $unprovisionedResponse->assertOk();
        $unprovisionedResponse->assertSee('اعمل site على ووردبريس');
        $unprovisionedResponse->assertDontSee('حدّث المحتوى على ووردبريس');

        $provisionedResponse = $this->actingAs($user)->get(route('projects.show', $provisionedSite->project));
        $provisionedResponse->assertOk();
        $provisionedResponse->assertDontSee('اعمل site على ووردبريس');
        $provisionedResponse->assertSee('حدّث المحتوى على ووردبريس');
        $provisionedResponse->assertSee($provisionedSite->wp_admin_url, false);
    }

    public function test_provisioning_through_the_controller_action_persists_the_returned_site_data_and_redirects_back(): void
    {
        $this->configureWordPressNetwork();
        $user = User::factory()->create();
        $site = $this->wordpressSite();

        Http::fake([
            'network.example.test/wp-json/barq/v1/sites' => Http::response([
                'site_id' => 99,
                'site_url' => 'https://from-controller.example-network.test',
                'admin_url' => 'https://from-controller.example-network.test/wp-admin',
            ], 201),
        ]);

        $response = $this->actingAs($user)->post(route('projects.site.provision-wordpress', $site->project));

        $response->assertRedirect(route('projects.show', $site->project));
        $response->assertSessionHas('status');

        $site->refresh();
        $this->assertSame(99, $site->wp_site_id);
        $this->assertSame('https://from-controller.example-network.test', $site->wp_site_url);
    }

    public function test_pushing_content_through_the_controller_action_redirects_back_with_a_status_message(): void
    {
        $this->configureWordPressNetwork();
        $user = User::factory()->create();
        $site = $this->wordpressSite([
            'wp_site_id' => 17,
            'wp_site_url' => 'https://x.example-network.test',
            'wp_admin_url' => 'https://x.example-network.test/wp-admin',
            'wp_provisioned_at' => now(),
            'content_json' => ['site_title' => 'مطعم فطاير'],
        ]);

        Http::fake([
            'network.example.test/wp-json/barq/v1/sites/17/content' => Http::response(['ok' => true], 200),
        ]);

        $response = $this->actingAs($user)->post(route('projects.site.push-wordpress-content', $site->project));

        $response->assertRedirect(route('projects.show', $site->project));
        $response->assertSessionHas('status');

        Http::assertSent(fn ($request) => $request['content']['site_title'] === 'مطعم فطاير');
    }
}
