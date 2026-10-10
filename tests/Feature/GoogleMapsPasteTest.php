<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Template;
use App\Models\TemplateVariant;
use App\Models\User;
use App\Services\PhotoPoolService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// كوبي جوجل مابس بالصور (2026-10-08): التليفون/المواعيد/العنوان/التقييم بيتحطوا بالحرف، الصور
// بتتوزّع على الغلاف والمعرض والزيادة في مخزن الصور، ومفيش آراء عملاء متألّفة على موقع حقيقي.
class GoogleMapsPasteTest extends TestCase
{
    use RefreshDatabase;

    private const PASTE = "عيادة دكتور احمد انور الهلالي\n4.9\n(127)\nطبيب أسنان\nنظرة عامة\nآراء\nالاتجاهات\nحفظ\nمشاركة\nشارع الجمهورية، المنصورة، الدقهلية 35511\nمفتوح ⋅ يغلق في 10 م\n010 1234 5678";

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->actingAs(User::factory()->create());
        config(['services.ollama.model' => 'qwen3:8b']);
    }

    private function clinicTemplate(): Template
    {
        $template = Template::factory()->create(['kind' => 'landing', 'category' => 'عيادات وخدمات طبية', 'name' => 'عيادة أسنان — ابتسامة واثقة', 'is_active' => true]);
        $slots = [
            ['hero', 'hero_title', 'text', 1], ['hero', 'hero_cta', 'link', 3], ['hero', 'hero_image', 'image', 4],
            ['gallery', 'gallery_title', 'text', 1], ['gallery', 'gallery_image_1', 'image', 2], ['gallery', 'gallery_image_2', 'image', 3], ['gallery', 'gallery_image_3', 'image', 4],
            ['testimonials', 'testimonials_title', 'text', 1], ['testimonials', 'testimonials_list', 'list', 2],
            ['contact', 'contact_title', 'text', 1], ['contact', 'contact_note', 'text', 2], ['contact', 'contact_link', 'link', 3],
        ];
        foreach ($slots as [$section, $key, $type, $order]) {
            $template->slots()->create(['section_key' => $section, 'key' => $key, 'label_ar' => $key, 'slot_type' => $type, 'sort_order' => $order,
                'default_value' => $key === 'testimonials_list' ? json_encode(['آراء متألّفة — سارة م.']) : null]);
        }
        TemplateVariant::factory()->for($template)->create(['is_default' => true, 'sections_json' => ['hero', 'gallery', 'testimonials', 'contact']]);

        return $template;
    }

    private function jpeg(int $w = 800, int $h = 600): string
    {
        $image = imagecreatetruecolor($w, $h);
        ob_start();
        imagejpeg($image);

        return (string) ob_get_clean();
    }

    public function test_a_google_maps_paste_with_photos_builds_a_site_with_its_real_data(): void
    {
        $this->clinicTemplate();
        $photo = 'https://lh5.googleusercontent.com/p/AF1QipNabc=w408-h306-k-no';

        Http::fake([
            '*/api/tags' => Http::response(['models' => [['name' => 'qwen3:8b']]]),
            '*/api/ps' => Http::response(['models' => [['name' => 'qwen3:8b']]]),
            'lh5.googleusercontent.com/*' => Http::response($this->jpeg(), 200, ['Content-Type' => 'image/jpeg']),
            'evil.example.com/*' => Http::response($this->jpeg()),
        ]);

        $start = $this->post(route('ai-runs.start'), [
            'kind' => 'create',
            'message' => self::PASTE,
            'photos' => [UploadedFile::fake()->image('front.jpg', 900, 600), UploadedFile::fake()->image('chair.png', 900, 600)],
            'photo_urls' => [$photo, 'https://evil.example.com/x.jpg', 'http://lh5.googleusercontent.com/p/plain-http=w400', 'https://lh3.googleusercontent.com/a/icon=s32-c'],
        ], ['Accept' => 'application/json'])->assertOk();

        // الصورة اتنزّلت بمقاس كبير، والروابط التانية اترفضت من غير ما تتطلب أصلاً.
        Http::assertSent(fn (Request $r) => $r->url() === 'https://lh5.googleusercontent.com/p/AF1QipNabc=w1600-h1200-k-no');
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'evil.example.com') || str_starts_with($r->url(), 'http://') && str_contains($r->url(), 'googleusercontent') || str_contains($r->url(), 'icon='));

        // بيانات المكان راحت للنموذج كبيانات مؤكدة.
        $prompt = \App\Models\AiRun::findOrFail($start->json('run'))->body_json['prompt'];
        $this->assertStringContainsString('- التليفون: 01012345678', $prompt);
        $this->assertStringContainsString('تقييمنا 4.9 ★ على جوجل من 127 تقييم', $prompt);

        $reply = json_encode([
            'category' => 'عيادات وخدمات طبية',
            'project_name' => 'Dr Ahmed Clinic',
            'style_hint' => '',
            'content' => ['hero_title' => 'ابتسامتك في أمان', 'contact_note' => 'مفتوح دايماً (غلط)', 'testimonials_list' => []],
        ], JSON_UNESCAPED_UNICODE);

        $this->postJson(route('ai-runs.complete', $start->json('run')), ['text' => $reply, 'metrics' => []])->assertJsonPath('ok', true);

        $project = Project::sole();
        $content = $project->site->content_json;

        $this->assertSame('عيادة دكتور احمد انور الهلالي', $project->name, 'the exact Google name wins over the model\'s version');
        $this->assertSame('01012345678', $project->contact_phone);
        $this->assertSame(4.9, $project->place_json['rating']);
        $this->assertSame('https://wa.me/201012345678', $content['hero_cta']);
        $this->assertSame('https://wa.me/201012345678', $content['contact_link']);
        $this->assertSame('مفتوح — يغلق في 10 م — شارع الجمهورية، المنصورة، الدقهلية 35511', $content['contact_note']);

        // الصور: المرفوعة الأول بالترتيب (الأولى غلاف)، وبعدين صورة جوجل.
        $pool = $project->site->photo_pool_json;
        $this->assertCount(3, $pool);
        $this->assertSame($pool[0], $content['hero_image']);
        $this->assertSame($pool[1], $content['gallery_image_1']);
        $this->assertSame($pool[2], $content['gallery_image_2']);
        $this->assertArrayNotHasKey('gallery_image_3', $content);
        foreach ($pool as $path) {
            Storage::disk('public')->assertExists(substr($path, strlen('/storage/')));
        }

        // مفيش آراء حقيقية → بدل الآراء المتألّفة: سطر تقييم جوجل الحقيقي بس.
        $this->assertSame([], $content['testimonials_list']);
        $this->assertSame('تقييمنا 4.9 ★ على جوجل من 127 تقييم', $content['testimonials_title']);

        $chat = $project->aiChatMessages()->where('role', 'assistant')->value('content');
        $this->assertStringContainsString('زرار واتساب برقمه', $chat);
        $this->assertStringContainsString('3 صور في الموقع', $chat);
    }

    public function test_without_real_reviews_or_rating_the_testimonials_section_disappears(): void
    {
        $this->clinicTemplate();
        Http::fake(['*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('cURL error 7')]);

        $this->postJson(route('ai-runs.start'), ['kind' => 'create', 'message' => 'عيادة أسنان في المنصورة'])->assertJsonPath('ok', true);

        $content = Project::sole()->site->content_json;
        $this->assertSame([], $content['testimonials_list']);
        $this->assertSame('', $content['testimonials_title']);

        $html = $this->get(Project::sole()->site->previewUrl())->assertOk()->getContent();
        $this->assertStringNotContainsString('آراء متألّفة', $html);
    }

    public function test_real_reviews_from_the_paste_are_kept(): void
    {
        $this->clinicTemplate();
        Http::fake(['*/api/tags' => Http::response(['models' => [['name' => 'qwen3:8b']]]), '*/api/ps' => Http::response(['models' => []])]);

        $run = $this->postJson(route('ai-runs.start'), ['kind' => 'create', 'message' => "عيادة أسنان\nمحمد علي\nمرشد محلي · 23 مراجعة\nدكتور ممتاز وشرح كل حاجة"])->json('run');
        $reply = json_encode(['category' => 'عيادات وخدمات طبية', 'project_name' => 'عيادة', 'style_hint' => '', 'content' => ['testimonials_list' => ['دكتور ممتاز وشرح كل حاجة — محمد']]], JSON_UNESCAPED_UNICODE);
        $this->postJson(route('ai-runs.complete', $run), ['text' => $reply]);

        $this->assertSame(['دكتور ممتاز وشرح كل حاجة — محمد'], Project::sole()->site->content_json['testimonials_list']);
        $this->assertArrayNotHasKey('testimonials_title', Project::sole()->site->content_json);
    }

    public function test_photos_from_a_run_that_never_made_a_project_are_deleted(): void
    {
        $this->clinicTemplate();
        Http::fake(['*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('cURL error 7')]);

        $this->post(route('ai-runs.start'), [
            'kind' => 'create',
            'message' => 'مرحبا',
            'photos' => [UploadedFile::fake()->image('a.jpg', 600, 400)],
        ], ['Accept' => 'application/json'])->assertJsonPath('ok', false);

        $this->assertSame(0, Project::count());
        $this->assertSame([], Storage::disk('public')->allFiles('site-images'));
    }

    public function test_any_pool_photo_goes_into_any_image_slot_and_nothing_outside_the_pool(): void
    {
        $template = $this->clinicTemplate();
        $project = Project::factory()->for($template)->create();
        $site = \App\Models\GeneratedSite::factory()->for($project)->create([
            'content_json' => ['hero_image' => '/storage/site-images/a.jpg'],
            'photo_pool_json' => ['/storage/site-images/a.jpg', '/storage/site-images/b.jpg'],
        ]);
        Storage::disk('public')->put('site-images/a.jpg', 'x');
        Storage::disk('public')->put('site-images/b.jpg', 'x');

        $this->post(route('projects.site.photos.use', $project), ['photo' => '/storage/site-images/b.jpg', 'slot_key' => 'gallery_image_3'])->assertSessionHasNoErrors();
        $this->assertSame('/storage/site-images/b.jpg', $site->fresh()->content_json['gallery_image_3']);

        // مسار مش من المخزن، أو خانة مش صورة — مرفوضين.
        $this->post(route('projects.site.photos.use', $project), ['photo' => '/etc/passwd', 'slot_key' => 'hero_image'])->assertSessionHasErrors('photo');
        $this->post(route('projects.site.photos.use', $project), ['photo' => '/storage/site-images/b.jpg', 'slot_key' => 'hero_title'])->assertSessionHasErrors('slot_key');

        // شيل صورة مستخدمة: بتخرج من المخزن بس الملف بيفضل (الموقع لسه بيعرضها).
        $this->delete(route('projects.site.photos.destroy', $project), ['photo' => '/storage/site-images/a.jpg']);
        $this->assertSame(['/storage/site-images/b.jpg'], $site->fresh()->photo_pool_json);
        Storage::disk('public')->assertExists('site-images/a.jpg');
    }

    public function test_photos_can_be_added_to_the_pool_later(): void
    {
        $template = $this->clinicTemplate();
        $project = Project::factory()->for($template)->create();
        $site = \App\Models\GeneratedSite::factory()->for($project)->create();
        Http::fake();

        $this->post(route('projects.site.photos.store', $project), ['photos' => [UploadedFile::fake()->image('new.jpg', 800, 600)]])->assertSessionHasNoErrors();

        $this->assertCount(1, $site->fresh()->photo_pool_json);
        $this->get(route('projects.show', $project))->assertOk()->assertSee($site->fresh()->photo_pool_json[0]);
    }

    public function test_only_https_google_image_hosts_are_ever_downloaded(): void
    {
        $this->assertTrue(PhotoPoolService::isAllowedUrl('https://lh3.googleusercontent.com/p/x=w800'));
        $this->assertTrue(PhotoPoolService::isAllowedUrl('https://geo0.ggpht.com/cbk?x=1'));
        $this->assertFalse(PhotoPoolService::isAllowedUrl('http://lh3.googleusercontent.com/p/x'));
        $this->assertFalse(PhotoPoolService::isAllowedUrl('https://googleusercontent.com.evil.com/x'));
        $this->assertFalse(PhotoPoolService::isAllowedUrl('https://user:pass@lh3.googleusercontent.com/x'));
        $this->assertFalse(PhotoPoolService::isAllowedUrl('https://lh3.googleusercontent.com:8443/x'));
        $this->assertFalse(PhotoPoolService::isAllowedUrl('https://127.0.0.1/x'));
    }

    public function test_a_non_image_or_tiny_download_is_skipped(): void
    {
        Http::fake([
            'lh1.googleusercontent.com/*' => Http::response('<html>not an image</html>', 200, ['Content-Type' => 'text/html']),
            'lh2.googleusercontent.com/*' => Http::response($this->jpeg(60, 60), 200),
            'lh3.googleusercontent.com/*' => Http::response('', 302, ['Location' => 'https://127.0.0.1/admin']),
        ]);

        $paths = app(PhotoPoolService::class)->importUrls([
            'https://lh1.googleusercontent.com/p/a=w800',
            'https://lh2.googleusercontent.com/p/b=w800',
            'https://lh3.googleusercontent.com/p/c=w800',
        ]);

        $this->assertSame([], $paths);
        $this->assertSame([], Storage::disk('public')->allFiles('site-images'));
    }
}
