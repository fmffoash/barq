<?php

namespace Tests\Feature;

use App\Models\AiRun;
use App\Models\GeneratedSite;
use App\Models\Project;
use App\Models\Template;
use App\Models\TemplateVariant;
use App\Models\User;
use App\Services\Ai\AiStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// العدّاد والبث (2026-10-08): المتصفح بيبدأ الطلب، يستقبل الرد كلمة بكلمة، ويرجّعه يتطبّق —
// بنفس منطق المسار القديم بالظبط، ومن غير ما نفس الرد يتطبّق مرتين.
class AiRunTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        config(['services.ollama.model' => 'qwen3:8b', 'services.ollama.base_url' => 'http://127.0.0.1:11434']);
    }

    private function libraryTemplate(string $category, string $name): Template
    {
        $template = Template::factory()->create(['kind' => 'landing', 'category' => $category, 'name' => $name, 'is_active' => true]);
        foreach ([['hero', 'hero_title', 'text'], ['hero', 'hero_subtitle', 'textarea'], ['services', 'services_list', 'list'], ['hero', 'hero_image', 'image']] as $i => [$section, $key, $type]) {
            $template->slots()->create(['section_key' => $section, 'key' => $key, 'label_ar' => $key, 'slot_type' => $type, 'sort_order' => $i]);
        }
        TemplateVariant::factory()->for($template)->create(['is_default' => true]);

        return $template;
    }

    private function projectWithSite(array $content = []): Project
    {
        $template = $this->libraryTemplate('عيادات وخدمات طبية', 'عيادة أسنان — ابتسامة واثقة');
        $project = Project::factory()->for($template)->create();
        GeneratedSite::factory()->for($project)->create(['content_json' => $content]);

        return $project;
    }

    // رد /api/generate لازم يتحدد هنا من الأول — Http::fake تاني بعد كده مش بيغلب الأول.
    private function fakeOllamaUp(bool $loaded = true, array|string|\Closure $generate = []): void
    {
        Http::fake([
            '*/api/tags' => Http::response(['models' => [['name' => 'qwen3:8b']]]),
            '*/api/ps' => Http::response(['models' => $loaded ? [['name' => 'qwen3:8b']] : []]),
            '*/api/generate' => $generate instanceof \Closure ? $generate : Http::response($generate ?: ['response' => '{}', 'done' => true]),
        ]);
    }

    public function test_start_prepares_the_request_and_gives_the_browser_the_ollama_body_and_an_estimate(): void
    {
        $this->libraryTemplate('عيادات وخدمات طبية', 'عيادة أسنان — ابتسامة واثقة');
        $this->fakeOllamaUp(loaded: false);
        config(['services.ollama.browser_direct' => 'always']);
        AiStats::record('qwen3:8b', 'create', ['eval_count' => 500, 'eval_duration' => 50_000_000_000, 'load_duration' => 12_000_000_000]);

        $response = $this->postJson(route('ai-runs.start'), ['kind' => 'create', 'message' => 'عيادة أسنان في المنصورة'])->assertOk();

        $response->assertJsonPath('done', false)
            ->assertJsonPath('direct.url', 'http://127.0.0.1:11434/api/generate')
            ->assertJsonPath('direct.body.stream', true)
            ->assertJsonPath('direct.body.model', 'qwen3:8b')
            ->assertJsonPath('estimate.loaded', false)
            ->assertJsonPath('estimate.eval_tokens', 500)
            ->assertJsonPath('estimate.measured', true);
        $this->assertGreaterThan(0, $response->json('estimate.prompt_tokens'));
        $this->assertSame(AiRun::PENDING, AiRun::findOrFail($response->json('run'))->status);
        Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/api/generate'));
    }

    public function test_the_browser_only_talks_to_ollama_directly_when_both_are_on_this_machine(): void
    {
        $this->libraryTemplate('عيادات وخدمات طبية', 'عيادة أسنان — ابتسامة واثقة');
        $this->fakeOllamaUp();

        config(['services.ollama.browser_direct' => 'auto']);
        $this->postJson('http://127.0.0.1:8010/ai/runs', ['kind' => 'create', 'message' => 'عيادة أسنان'])->assertJsonPath('direct.url', 'http://127.0.0.1:11434/api/generate');
        $this->postJson('https://panel.example.com/ai/runs', ['kind' => 'create', 'message' => 'عيادة أسنان'])->assertJsonPath('direct', null);

        config(['services.ollama.browser_direct' => false]);
        $this->postJson('http://127.0.0.1:8010/ai/runs', ['kind' => 'create', 'message' => 'عيادة أسنان'])->assertJsonPath('direct', null);
    }

    public function test_the_streamed_reply_is_applied_once_and_the_speed_is_recorded(): void
    {
        $clinic = $this->libraryTemplate('عيادات وخدمات طبية', 'عيادة أسنان — ابتسامة واثقة');
        $this->fakeOllamaUp();

        $run = $this->postJson(route('ai-runs.start'), ['kind' => 'create', 'message' => 'عيادة أسنان في المنصورة'])->json('run');
        $text = json_encode([
            'category' => 'عيادات وخدمات طبية',
            'project_name' => 'عيادة د. أحمد',
            'style_hint' => '',
            'content' => ['hero_title' => 'ابتسامتك في أمان', 'services_list' => ['تبييض', 'تقويم', 'زراعة']],
        ], JSON_UNESCAPED_UNICODE);
        $metrics = ['eval_count' => 420, 'eval_duration' => 60_000_000_000, 'prompt_eval_count' => 900, 'prompt_eval_duration' => 18_000_000_000, 'done_reason' => 'stop', 'context' => [1, 2, 3], 'evil' => 'x'];

        $first = $this->postJson(route('ai-runs.complete', $run), ['text' => $text, 'metrics' => $metrics])->assertOk();

        $project = Project::sole();
        $first->assertJsonPath('ok', true)->assertJsonPath('redirect', route('ai-chat.show', $project));
        $this->assertSame($clinic->id, $project->template_id);
        $this->assertSame('عيادة د. أحمد', $project->name);
        $this->assertSame(['تبييض', 'تقويم', 'زراعة'], $project->site->content_json['services_list']);
        $this->assertEqualsWithDelta(7.0, AiStats::estimate('qwen3:8b', 'create')['eval_tps'], 0.01);
        $this->assertSame(AiRun::DONE, AiRun::findOrFail($run)->status);

        // المتصفح عاد المحاولة (أو دوستين) — نفس النتيجة، ومفيش مشروع تاني.
        $this->postJson(route('ai-runs.complete', $run), ['text' => $text, 'metrics' => $metrics])
            ->assertJsonPath('redirect', route('ai-chat.show', $project));
        $this->assertSame(1, Project::count());
    }

    public function test_when_ollama_is_off_the_run_finishes_right_away_with_the_fallback_and_the_reason(): void
    {
        $clinic = $this->libraryTemplate('عيادات وخدمات طبية', 'عيادة أسنان — ابتسامة واثقة');
        Http::fake(['*' => fn () => throw new ConnectionException('cURL error 7: Failed to connect')]);

        $response = $this->postJson(route('ai-runs.start'), ['kind' => 'create', 'message' => "عيادة دكتور احمد\nطبيب أسنان"])->assertOk();

        $response->assertJsonPath('done', true)->assertJsonPath('ok', true);
        $this->assertStringContainsString('Ollama', $response->json('reply'));
        $this->assertSame($clinic->id, Project::sole()->template_id);
    }

    public function test_bad_input_comes_back_as_validation_errors_without_touching_the_ai(): void
    {
        Http::fake();

        $this->postJson(route('ai-runs.start'), ['kind' => 'create', 'message' => ''])->assertStatus(422)->assertJsonValidationErrors('message');
        $this->postJson(route('ai-runs.start'), ['kind' => 'hack'])->assertStatus(422)->assertJsonValidationErrors('kind');
        $this->postJson(route('ai-runs.start'), ['kind' => 'follow_up', 'message' => 'x', 'project_id' => 999])->assertStatus(422)->assertJsonValidationErrors('project_id');

        Http::assertNothingSent();
    }

    public function test_a_follow_up_message_is_applied_from_the_streamed_reply(): void
    {
        $project = $this->projectWithSite(['hero_title' => 'قديم']);
        $this->fakeOllamaUp();

        $run = $this->postJson(route('ai-runs.start'), ['kind' => 'follow_up', 'project_id' => $project->id, 'message' => 'اكتب عنوان رئيسي أحلى عن الابتسامة'])
            ->assertJsonPath('done', false)->json('run');

        $text = json_encode(['action' => 'update_content', 'changes' => ['hero_title' => 'ابتسامة تليق بيك'], 'reply' => 'تمام'], JSON_UNESCAPED_UNICODE);
        $this->postJson(route('ai-runs.complete', $run), ['text' => $text, 'metrics' => ['eval_count' => 40, 'eval_duration' => 8_000_000_000]])
            ->assertJsonPath('ok', true)
            ->assertJsonPath('redirect', null);

        $this->assertSame('ابتسامة تليق بيك', $project->site->fresh()->content_json['hero_title']);
        $this->assertSame(2, $project->aiChatMessages()->count());
    }

    public function test_a_failure_seen_by_the_browser_is_explained_to_fouad(): void
    {
        $project = $this->projectWithSite();
        $this->fakeOllamaUp();

        $run = $this->postJson(route('ai-runs.start'), ['kind' => 'follow_up', 'project_id' => $project->id, 'message' => 'اكتب فقرة عن العيادة'])->json('run');

        $reply = $this->postJson(route('ai-runs.complete', $run), ['error' => 'model_missing', 'detail' => "model 'qwen3:8b' not found"])->json('reply');

        $this->assertStringContainsString('مش متسطّب', $reply);
    }

    public function test_suggest_only_asks_the_ai_about_empty_slots_and_never_overwrites_written_ones(): void
    {
        $project = $this->projectWithSite(['hero_title' => 'كتبته بإيدي']);
        $this->fakeOllamaUp();

        $start = $this->postJson(route('ai-runs.start'), ['kind' => 'suggest', 'project_id' => $project->id, 'business_description' => 'عيادة أسنان'])->assertJsonPath('done', false);
        $properties = array_keys($start->json('direct.body.format.properties') ?? AiRun::findOrFail($start->json('run'))->body_json['format']['properties']);
        $this->assertSame(['hero_subtitle', 'services_list'], $properties);

        $text = json_encode(['hero_title' => 'مكتوب على اللي كتبته', 'hero_subtitle' => 'رعاية كاملة لأسنانك', 'services_list' => ['تبييض', 'حشو']], JSON_UNESCAPED_UNICODE);
        $this->postJson(route('ai-runs.complete', $start->json('run')), ['text' => $text, 'metrics' => []])
            ->assertJsonPath('redirect', route('projects.site.edit', $project));

        $content = $project->site->fresh()->content_json;
        $this->assertSame('كتبته بإيدي', $content['hero_title']);
        $this->assertSame('رعاية كاملة لأسنانك', $content['hero_subtitle']);
        $this->assertSame(['تبييض', 'حشو'], $content['services_list']);
    }

    public function test_suggest_with_nothing_empty_answers_instantly(): void
    {
        $project = $this->projectWithSite(['hero_title' => 'أ', 'hero_subtitle' => 'ب', 'services_list' => ['ج']]);
        Http::fake();

        $this->postJson(route('ai-runs.start'), ['kind' => 'suggest', 'project_id' => $project->id, 'business_description' => 'عيادة'])
            ->assertJsonPath('done', true)
            ->assertJsonPath('ok', true);

        Http::assertNothingSent();
    }

    public function test_the_server_relays_the_reply_line_by_line_when_the_browser_cannot_reach_ollama(): void
    {
        $this->libraryTemplate('عيادات وخدمات طبية', 'عيادة أسنان — ابتسامة واثقة');
        $ndjson = "{\"response\":\"{\\\"a\\\"\",\"done\":false}\n{\"response\":\":1}\",\"done\":false}\n{\"response\":\"\",\"done\":true,\"eval_count\":2}\n";
        $this->fakeOllamaUp(generate: $ndjson);
        $run = $this->postJson(route('ai-runs.start'), ['kind' => 'create', 'message' => 'عيادة أسنان'])->json('run');

        $content = $this->post(route('ai-runs.stream', $run))->assertOk()->streamedContent();

        $lines = array_values(array_filter(explode("\n", $content)));
        // أول سطر نبضة "لسه شغال" فورية (Cloudflare على السيرفر بيقطع أي رد ساكت 100 ثانية).
        $this->assertTrue(json_decode($lines[0], true)['heartbeat']);
        $lines = array_values(array_filter($lines, fn ($line) => ! (json_decode($line, true)['heartbeat'] ?? false)));
        $this->assertCount(3, $lines);
        $this->assertTrue(json_decode($lines[2], true)['done']);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/api/generate') && $request['stream'] === true);
    }

    public function test_a_relay_failure_becomes_an_error_line_instead_of_silence(): void
    {
        $this->libraryTemplate('عيادات وخدمات طبية', 'عيادة أسنان — ابتسامة واثقة');
        $this->fakeOllamaUp(generate: fn () => throw new ConnectionException('cURL error 7: Failed to connect'));
        $run = $this->postJson(route('ai-runs.start'), ['kind' => 'create', 'message' => 'عيادة أسنان'])->json('run');

        $lines = array_filter(explode("\n", $this->post(route('ai-runs.stream', $run))->streamedContent()));
        $line = json_decode(end($lines), true);

        $this->assertSame('down', $line['kind']);
    }

    public function test_the_last_resort_waits_for_the_whole_reply_on_the_server(): void
    {
        $project = $this->projectWithSite();
        $this->fakeOllamaUp(generate: [
            'response' => json_encode(['action' => 'update_content', 'changes' => ['hero_title' => 'عنوان جديد']], JSON_UNESCAPED_UNICODE),
            'done' => true,
            'eval_count' => 30,
            'eval_duration' => 5_000_000_000,
        ]);

        $run = $this->postJson(route('ai-runs.start'), ['kind' => 'follow_up', 'project_id' => $project->id, 'message' => 'اكتب عنوان أحسن'])->json('run');
        $this->postJson(route('ai-runs.server', $run))->assertJsonPath('ok', true);

        $this->assertSame('عنوان جديد', $project->site->fresh()->content_json['hero_title']);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/api/generate') && $request['stream'] === false);
    }

    public function test_cancelling_drops_the_attached_image_and_nothing_is_applied_after(): void
    {
        Storage::fake('public');
        $project = $this->projectWithSite();
        $this->fakeOllamaUp();

        $run = $this->post(route('ai-runs.start'), [
            'kind' => 'follow_up',
            'project_id' => $project->id,
            'message' => 'حط الصورة دي جنب الخدمات',
            'image' => UploadedFile::fake()->image('photo.jpg', 400, 300),
        ], ['Accept' => 'application/json'])->json('run');

        $stored = AiRun::findOrFail($run)->context_json['image_path'];
        Storage::disk('public')->assertExists(substr($stored, strlen('/storage/')));

        $this->postJson(route('ai-runs.cancel', $run))->assertOk();

        Storage::disk('public')->assertMissing(substr($stored, strlen('/storage/')));
        $this->postJson(route('ai-runs.complete', $run), ['text' => '{"action":"update_image","slot_key":"hero_image"}'])
            ->assertJsonPath('ok', false);
        $this->assertNull($project->site->fresh()->content('hero_image'));
    }

    public function test_guests_cannot_start_runs(): void
    {
        auth()->logout();

        $this->postJson(route('ai-runs.start'), ['kind' => 'create', 'message' => 'x'])->assertUnauthorized();
    }
}
