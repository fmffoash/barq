<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\Ai\AiStats;
use App\Services\Ai\AiStatus;
use App\Services\OllamaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

// صفحة إعدادات الذكاء الاصطناعي (2026-10-10): حالة Ollama، اختيار نموذج متسطّب، حجم السياق،
// السرعة الحقيقية واختبارها، ونماذج تناسب رام الجهاز.
class AiSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.ollama.model' => 'qwen3:8b', 'services.ollama.base_url' => 'http://127.0.0.1:11434', 'services.ollama.num_ctx' => 8192]);
    }

    private function fakeOllamaUp(array $generate = []): void
    {
        Http::fake([
            '*/api/tags' => Http::response(['models' => [
                ['name' => 'qwen3:8b', 'size' => 5_200_000_000, 'details' => ['parameter_size' => '8.2B', 'quantization_level' => 'Q4_K_M']],
                ['name' => 'qwen3:4b', 'size' => 2_600_000_000, 'details' => ['parameter_size' => '4.0B', 'quantization_level' => 'Q4_K_M']],
            ]]),
            '*/api/ps' => Http::response(['models' => [['name' => 'qwen3:8b']]]),
            '*/api/version' => Http::response(['version' => '0.31.2']),
            '*/api/generate' => Http::response($generate ?: ['response' => '{"text":"أهلاً بيكم"}', 'done' => true]),
        ]);
    }

    public function test_guests_cannot_see_or_change_ai_settings(): void
    {
        $this->get(route('ai-settings.show'))->assertRedirect(route('login'));
        $this->put(route('ai-settings.update'), ['model' => 'qwen3:4b'])->assertRedirect(route('login'));
    }

    public function test_the_page_explains_when_ollama_is_off(): void
    {
        Http::fake(['*' => fn () => throw new ConnectionException('cURL error 7')]);

        $this->actingAs(User::factory()->create())->get(route('ai-settings.show'))
            ->assertOk()
            ->assertSee('مش شغال')
            ->assertSee('qwen3:8b')
            ->assertSee('اختبار السرعة');
    }

    public function test_the_page_shows_installed_models_and_the_measured_speed(): void
    {
        $this->fakeOllamaUp();
        AiStats::record('qwen3:8b', 'create', ['eval_count' => 600, 'eval_duration' => 100_000_000_000, 'prompt_eval_count' => 3000, 'prompt_eval_duration' => 60_000_000_000]);

        $page = $this->actingAs(User::factory()->create())->get(route('ai-settings.show'))->assertOk();

        $page->assertSee('✓ شغال', false)
            ->assertSee('v0.31.2')
            ->assertSee('qwen3:4b — 2.6 GB')
            ->assertSee('محمّل في الذاكرة دلوقتي')
            ->assertSee('6 توكن/ثانية')
            ->assertSee('ollama pull qwen3:14b');
    }

    public function test_only_an_installed_model_can_be_chosen_and_empty_goes_back_to_the_default(): void
    {
        $this->fakeOllamaUp();
        $this->actingAs(User::factory()->create());

        $this->put(route('ai-settings.update'), ['model' => 'qwen3:4b', 'num_ctx' => 12288])->assertRedirect(route('ai-settings.show'));
        $this->assertSame('qwen3:4b', app(OllamaService::class)->model());
        $this->assertSame(12288, app(OllamaService::class)->numCtx());

        $this->put(route('ai-settings.update'), ['model' => 'llama-evil:70b'])->assertSessionHasErrors('model');
        $this->put(route('ai-settings.update'), ['num_ctx' => 999])->assertSessionHasErrors('num_ctx');
        $this->assertSame('qwen3:4b', app(OllamaService::class)->model());

        $this->put(route('ai-settings.update'), ['model' => '', 'num_ctx' => ''])->assertRedirect();
        $this->assertNull(Setting::get('ai.model'));
        $this->assertSame('qwen3:8b', app(OllamaService::class)->model());
        $this->assertSame(8192, app(OllamaService::class)->numCtx());
    }

    public function test_the_speed_test_runs_on_the_timer_and_records_the_real_speed(): void
    {
        $this->fakeOllamaUp([
            'response' => '{"text":"أهلاً بيكم في مطعمنا"}',
            'done' => true,
            'eval_count' => 100,
            'eval_duration' => 20_000_000_000,
        ]);
        $this->actingAs(User::factory()->create());

        $run = $this->postJson(route('ai-runs.start'), ['kind' => 'benchmark'])->assertOk()->assertJsonPath('done', false)->json('run');
        $reply = $this->postJson(route('ai-runs.server', $run))->assertJsonPath('ok', true)->json('reply');

        $this->assertStringContainsString('5 توكن/ثانية', $reply);
        $this->assertEqualsWithDelta(5.0, AiStats::estimate('qwen3:8b', 'benchmark')['eval_tps'], 0.01);
    }

    public function test_the_speed_test_works_without_javascript(): void
    {
        $this->fakeOllamaUp(['response' => '{"text":"أهلاً"}', 'done' => true, 'eval_count' => 50, 'eval_duration' => 10_000_000_000]);

        $this->actingAs(User::factory()->create())->post(route('ai-settings.benchmark'))
            ->assertRedirect(route('ai-settings.show'))
            ->assertSessionHas('status', fn ($status) => str_contains($status, '5 توكن/ثانية'));
    }

    public function test_model_suggestions_follow_the_machines_ram(): void
    {
        $status = app(AiStatus::class);
        $byName = fn (?float $ram) => collect($status->catalog($ram, [['name' => 'qwen3:8b']]))->keyBy('name');

        // السيرفر (7.6 جيجا مشتركة): 8b على الحد، 14b لأ.
        $server = $byName(7.6);
        $this->assertSame('tight', $server['qwen3:8b']['fit']);
        $this->assertTrue($server['qwen3:8b']['installed']);
        $this->assertSame('good', $server['qwen3:4b']['fit']);
        $this->assertSame('no', $server['qwen3:14b']['fit']);

        $big = $byName(32.0);
        $this->assertSame('good', $big['qwen3:14b']['fit']);
        $this->assertSame('good', $big['qwen3:30b']['fit']);
        $this->assertSame('tight', $byName(22.0)['qwen3:30b']['fit']);

        $this->assertSame('unknown', $byName(null)['qwen3:8b']['fit']);
    }
}
