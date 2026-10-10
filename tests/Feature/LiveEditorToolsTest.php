<?php

namespace Tests\Feature;

use App\Models\AiRun;
use App\Models\GeneratedSite;
use App\Models\Project;
use App\Models\Template;
use App\Models\TemplateVariant;
use App\Models\User;
use App\Support\LinkInput;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

// أدوات المحرر المباشر الجديدة (2026-10-10): صورة تانية (من صور المشروع/القالب أو رفع)، أزرار
// التواصل (رقم ← واتساب، ورفض أي رابط مش رابط)، تعديل القوايم، و"✨ صياغة تانية" بالذكاء الاصطناعي.
class LiveEditorToolsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        config(['services.ollama.model' => 'qwen3:8b', 'services.ollama.base_url' => 'http://127.0.0.1:11434']);
    }

    private function project(array $content = [], array $pool = []): Project
    {
        $template = Template::factory()->create(['kind' => 'landing', 'category' => 'مطاعم وكافيهات', 'is_active' => true, 'layout' => 'classic']);
        $slots = [
            ['hero', 'hero_title', 'text', 'عنوان', null],
            ['hero', 'hero_subtitle', 'textarea', 'وصف', null],
            ['hero', 'hero_image', 'image', 'صورة الغلاف', '/images/template-library/restaurants/hero.jpg'],
            ['hero', 'hero_cta', 'link', 'اطلب دلوقتي', null],
            ['services', 'services_list', 'list', 'الخدمات', null],
            ['testimonials', 'testimonials_list', 'list', 'آراء العملاء', null],
            ['contact', 'contact_link', 'link', 'تواصل معنا', null],
        ];
        foreach ($slots as $i => [$section, $key, $type, $label, $default]) {
            $template->slots()->create(['section_key' => $section, 'key' => $key, 'slot_type' => $type, 'label_ar' => $label, 'default_value' => $default, 'sort_order' => $i]);
        }
        TemplateVariant::factory()->for($template)->create(['is_default' => true]);
        $project = Project::factory()->for($template)->create(['name' => 'مطعم الشيف']);
        GeneratedSite::factory()->for($project)->create([
            'content_json' => $content + ['hero_title' => 'أكل بيتي', 'hero_subtitle' => 'أطباق طازة كل يوم', 'services_list' => ['محاشي', 'مشويات']],
            'photo_pool_json' => $pool ?: null,
        ]);

        return $project;
    }

    // ---------- صورة تانية ----------

    public function test_a_template_photo_or_a_pool_photo_can_be_put_in_an_image_slot_as_json(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('site-images/pool1.jpg', 'x');
        $project = $this->project([], ['/storage/site-images/pool1.jpg']);

        $this->postJson(route('projects.site.photos.use', $project), ['photo' => '/storage/site-images/pool1.jpg', 'slot_key' => 'hero_image'])
            ->assertOk()->assertJsonPath('ok', true);
        $this->assertSame('/storage/site-images/pool1.jpg', $project->site->fresh()->content_json['hero_image']);

        $this->postJson(route('projects.site.photos.use', $project), ['photo' => '/images/template-library/restaurants/hero.jpg', 'slot_key' => 'hero_image'])
            ->assertOk();
        $this->assertSame('/images/template-library/restaurants/hero.jpg', $project->site->fresh()->content_json['hero_image']);
    }

    public function test_an_earlier_upload_can_be_restored_by_undo_but_no_arbitrary_path_is_accepted(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('site-images/old-upload.jpg', 'x');
        $project = $this->project();

        $this->postJson(route('projects.site.photos.use', $project), ['photo' => '/storage/site-images/old-upload.jpg', 'slot_key' => 'hero_image'])->assertOk();

        foreach (['/storage/site-images/missing.jpg', '/storage/../.env', 'https://evil.example/x.jpg', '/images/template-library/other/hero.jpg', 'javascript:alert(1)'] as $bad) {
            $this->postJson(route('projects.site.photos.use', $project), ['photo' => $bad, 'slot_key' => 'hero_image'])
                ->assertStatus(422)->assertJsonValidationErrors('photo');
        }
        $this->postJson(route('projects.site.photos.use', $project), ['photo' => '/storage/site-images/old-upload.jpg', 'slot_key' => 'services_list'])
            ->assertStatus(422)->assertJsonValidationErrors('slot_key');

        $this->assertSame('/storage/site-images/old-upload.jpg', $project->site->fresh()->content_json['hero_image']);
    }

    public function test_uploading_from_the_editor_adds_to_the_pool_and_fills_the_slot(): void
    {
        Storage::fake('public');
        $project = $this->project();

        $response = $this->post(route('projects.site.photos.store', $project), [
            'photos' => [UploadedFile::fake()->image('new.jpg', 800, 600)],
            'slot_key' => 'hero_image',
        ], ['Accept' => 'application/json'])->assertOk();

        $added = $response->json('added.0');
        $site = $project->site->fresh();
        $this->assertSame($added, $site->content_json['hero_image']);
        $this->assertSame([$added], $site->photo_pool_json);
        Storage::disk('public')->assertExists(substr($added, strlen('/storage/')));
    }

    // ---------- أزرار التواصل ----------

    public static function links(): array
    {
        return [
            'egyptian mobile' => ['01012345678', 'https://wa.me/201012345678'],
            'spaced mobile' => ['010 1234 5678', 'https://wa.me/201012345678'],
            'international plus' => ['+966 50 123 4567', 'https://wa.me/966501234567'],
            'international 00' => ['00971501234567', 'https://wa.me/971501234567'],
            'wa.me without scheme' => ['wa.me/201012345678', 'https://wa.me/201012345678'],
            'bare domain' => ['facebook.com/chef', 'https://facebook.com/chef'],
            'https' => ['https://example.com/menu?x=1', 'https://example.com/menu?x=1'],
            'tel' => ['tel:+201012345678', 'tel:+201012345678'],
            'mailto' => ['mailto:a@b.co', 'mailto:a@b.co'],
            'anchor' => ['#contact', '#contact'],
            'empty clears' => ['  ', ''],
            'javascript' => ['javascript:alert(1)', null],
            'data uri' => ['data:text/html,<script>', null],
            'quote breakout' => ['https://x.com/"onmouseover=', null],
            'too short number' => ['12345', null],
        ];
    }

    #[DataProvider('links')]
    public function test_link_input_is_normalized_or_rejected(string $raw, ?string $expected): void
    {
        $this->assertSame($expected, LinkInput::normalize($raw));
    }

    public function test_saving_a_phone_number_on_a_contact_button_turns_it_into_whatsapp_and_bad_links_are_refused(): void
    {
        $project = $this->project();

        $this->put(route('projects.site.update', $project), ['content' => ['contact_link' => '01012345678']], ['Accept' => 'application/json'])
            ->assertRedirect();
        $this->assertSame('https://wa.me/201012345678', $project->site->fresh()->content_json['contact_link']);

        $this->put(route('projects.site.update', $project), ['content' => ['contact_link' => 'javascript:alert(1)', 'hero_title' => 'تغيير']], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors('content.contact_link');
        $site = $project->site->fresh();
        $this->assertSame('https://wa.me/201012345678', $site->content_json['contact_link']);
        $this->assertSame('أكل بيتي', $site->content_json['hero_title'], 'nothing is saved when a link is refused');

        // والزرار بقى واتساب على الموقع العام + الزرار العائم.
        $page = $this->get(route('site.show', ['siteSlug' => $site->slug]));
        $page->assertSee('href="https://wa.me/201012345678"', false);
    }

    // ---------- المحرر نفسه ----------

    public function test_the_live_editor_gets_lists_links_photos_and_the_rewrite_form_safely_encoded(): void
    {
        $project = $this->project(['testimonials_list' => ['حلو جداً </script><script>alert(1)</script> — منى']], ['/storage/site-images/p.jpg']);

        $page = $this->get(route('projects.site.live-edit', $project))->assertOk();

        $page->assertSee('id="bq-rewrite-form"', false)
            ->assertSee('data-ai-run="rewrite"', false)
            ->assertSee('id="ai-run-panel"', false)
            ->assertSee('id="bq-links-open"', false)
            ->assertSee('id="bq-undo"', false);
        $html = $page->getContent();
        $this->assertStringNotContainsString('</script><script>alert(1)', $html);

        preg_match('~<script id="live-editor-config" type="application/json">(.*?)</script>~s', $html, $m);
        $config = json_decode($m[1], true);
        $this->assertSame(['محاشي', 'مشويات'], $config['listValues']['services_list']);
        $this->assertSame(['contact_link', 'hero_cta'], collect($config['linkSlots'])->pluck('key')->sort()->values()->all(), 'empty link slots are editable too');
        $this->assertSame(['/storage/site-images/p.jpg'], $config['photoPool']);
        $this->assertSame(['/images/template-library/restaurants/hero.jpg'], $config['templatePhotos']);
        $this->assertArrayHasKey('shorter', $config['rewriteStyles']);
    }

    // ---------- ✨ صياغة تانية ----------

    private function fakeOllama(string $text): void
    {
        Http::fake([
            '*/api/tags' => Http::response(['models' => [['name' => 'qwen3:8b']]]),
            '*/api/ps' => Http::response(['models' => [['name' => 'qwen3:8b']]]),
            '*/api/generate' => Http::response(['response' => json_encode(['text' => $text], JSON_UNESCAPED_UNICODE), 'done' => true, 'eval_count' => 20, 'eval_duration' => 2_000_000_000]),
        ]);
    }

    public function test_rewrite_asks_about_one_slot_and_saves_the_new_wording_escaped(): void
    {
        $project = $this->project();
        $this->fakeOllama('«أطباق بيتي طازة <b>كل يوم</b> & بحب»');

        $start = $this->postJson(route('ai-runs.start'), ['kind' => 'rewrite', 'project_id' => $project->id, 'slot_key' => 'hero_subtitle', 'style' => 'shorter', 'note' => 'اذكر الحب'])
            ->assertOk()->assertJsonPath('done', false);
        $prompt = $start->json('direct.body.prompt') ?? AiRun::findOrFail($start->json('run'))->body_json['prompt'];
        $this->assertStringContainsString('أطباق طازة كل يوم', $prompt);
        $this->assertStringContainsString('اذكر الحب', $prompt);
        $this->assertStringContainsString('مطعم الشيف', $prompt);

        $this->postJson(route('ai-runs.server', $start->json('run')))->assertJsonPath('ok', true)->assertJsonPath('reload', true);

        $saved = $project->site->fresh()->content_json['hero_subtitle'];
        $this->assertStringNotContainsString('<b>', $saved, 'AI text is plain text, never markup');
        $this->assertStringContainsString('أطباق بيتي طازة', $saved);
        $this->assertStringNotContainsString('«', $saved);
    }

    public function test_rewrite_refuses_non_text_slots_and_empty_text_without_calling_the_ai(): void
    {
        Http::fake();
        $project = $this->project(['hero_title' => '']);

        $this->postJson(route('ai-runs.start'), ['kind' => 'rewrite', 'project_id' => $project->id, 'slot_key' => 'services_list', 'style' => 'better'])
            ->assertOk()->assertJsonPath('done', true)->assertJsonPath('ok', false);
        $this->postJson(route('ai-runs.start'), ['kind' => 'rewrite', 'project_id' => $project->id, 'slot_key' => 'hero_title', 'style' => 'better'])
            ->assertOk()->assertJsonPath('done', true)->assertJsonPath('ok', false);
        $this->postJson(route('ai-runs.start'), ['kind' => 'rewrite', 'project_id' => $project->id, 'slot_key' => 'hero_subtitle', 'style' => 'evil'])
            ->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_rewrite_works_without_javascript_too(): void
    {
        $project = $this->project();
        $this->fakeOllama('عنوان أشيك');

        $this->post(route('projects.site.rewrite', $project), ['slot_key' => 'hero_title', 'style' => 'formal'])
            ->assertRedirect(route('projects.site.live-edit', $project));

        $this->assertSame('عنوان أشيك', $project->site->fresh()->content_json['hero_title']);
    }
}
