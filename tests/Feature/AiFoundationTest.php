<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Setting;
use App\Models\Template;
use App\Models\TemplateVariant;
use App\Services\Ai\AiResult;
use App\Services\Ai\AiStats;
use App\Services\AiProjectAssistantService;
use App\Services\OllamaService;
use App\Support\CategoryGuesser;
use App\Support\PastedText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

// أساس الذكاء الاصطناعي على جهاز فؤاد (2026-10-06): مساحة قراية صريحة، شكل رد مُلزِم، أسباب
// فشل واضحة، ومفيش "أول فئة أبجدياً" لما الذكاء الاصطناعي ميردّش (عيادة أسنان طلعت متجر أزياء).
class AiFoundationTest extends TestCase
{
    use RefreshDatabase;

    private function libraryTemplate(string $category, string $name): Template
    {
        $template = Template::factory()->create(['kind' => 'landing', 'category' => $category, 'name' => $name, 'is_active' => true]);
        foreach ([['hero', 'hero_title', 'text'], ['hero', 'hero_subtitle', 'textarea'], ['services', 'services_list', 'list'], ['hero', 'hero_image', 'image'], ['contact', 'contact_link', 'link']] as $i => [$section, $key, $type]) {
            $template->slots()->create(['section_key' => $section, 'key' => $key, 'label_ar' => $key, 'slot_type' => $type, 'sort_order' => $i]);
        }
        TemplateVariant::factory()->for($template)->create(['is_default' => true]);

        return $template;
    }

    public function test_requests_carry_context_size_keep_alive_options_and_a_schema(): void
    {
        config(['services.ollama.num_ctx' => 8192, 'services.ollama.keep_alive' => '30m']);
        Http::fake(['*/api/generate' => Http::response(['response' => '{"a":"b"}', 'eval_count' => 20, 'eval_duration' => 2_000_000_000], 200)]);

        $schema = ['type' => 'object', 'properties' => ['a' => ['type' => 'string']], 'required' => ['a']];
        $result = (new OllamaService)->run('طلب', $schema, 'classify');

        $this->assertTrue($result->ok);
        $this->assertSame(['a' => 'b'], $result->data);
        Http::assertSent(fn (Request $request) => $request['options']['num_ctx'] === 8192
            && $request['options']['temperature'] === 0.1
            && $request['keep_alive'] === '30m'
            && $request['format'] === $schema
            && $request['think'] === false
            && $request['stream'] === false);
    }

    public function test_the_model_chosen_in_settings_wins_over_env(): void
    {
        config(['services.ollama.model' => 'qwen3:8b']);
        Setting::put('ai.model', 'qwen3.5:9b');

        $this->assertSame('qwen3.5:9b', (new OllamaService)->model());
    }

    public function test_failures_are_classified_with_a_specific_reason(): void
    {
        // Http::fake مرة واحدة بس — رد مختلف لكل نداء بالترتيب.
        $replies = [
            fn () => throw new ConnectionException('cURL error 7: Failed to connect to 127.0.0.1 port 11434'),
            fn () => throw new ConnectionException('cURL error 28: Operation timed out after 600001 milliseconds'),
            fn () => Http::response(['error' => "model 'qwen3:8b' not found"], 404),
            fn () => Http::response(['response' => 'مش JSON خالص', 'done_reason' => 'stop'], 200),
            fn () => Http::response(['response' => '{"a": "نص مقطوع', 'done_reason' => 'length'], 200),
        ];
        Http::fake(['*/api/generate' => function () use (&$replies) {
            return array_shift($replies)();
        }]);

        $ollama = new OllamaService;
        $this->assertSame(AiResult::DOWN, $ollama->run('x')->error);
        $this->assertSame(AiResult::TIMEOUT, $ollama->run('x')->error);
        $missing = $ollama->run('x');
        $this->assertSame(AiResult::MODEL_MISSING, $missing->error);
        $this->assertStringContainsString('مش متسطّب', $missing->message('qwen3:8b'));
        $this->assertSame(AiResult::BAD_JSON, $ollama->run('x')->error);
        $this->assertSame(AiResult::TOO_LONG, $ollama->run('x')->error);
    }

    public function test_json_wrapped_in_a_code_fence_is_still_understood(): void
    {
        $this->assertSame(['a' => 1], OllamaService::decodeJson("```json\n{\"a\": 1}\n```"));
        $this->assertSame(['a' => 1], OllamaService::decodeJson('أكيد! ده الرد: {"a": 1} بالتوفيق'));
    }

    public function test_preflight_reports_a_missing_model_before_any_long_wait(): void
    {
        config(['services.ollama.model' => 'qwen3:8b']);
        $installed = [['name' => 'llama3:8b']];
        Http::fake(['*/api/tags' => function () use (&$installed) {
            return Http::response(['models' => $installed], 200);
        }]);

        $this->assertSame(AiResult::MODEL_MISSING, (new OllamaService)->preflight()?->error);

        $installed[] = ['name' => 'qwen3:8b'];
        $this->assertNull((new OllamaService)->preflight());
    }

    public function test_speed_is_measured_from_real_replies(): void
    {
        AiStats::record('qwen3:8b', 'create', [
            'prompt_eval_count' => 1000, 'prompt_eval_duration' => 20_000_000_000,
            'eval_count' => 600, 'eval_duration' => 100_000_000_000, 'load_duration' => 15_000_000_000,
        ]);

        $estimate = AiStats::estimate('qwen3:8b', 'create');

        $this->assertTrue($estimate['measured']);
        $this->assertEqualsWithDelta(50.0, $estimate['prompt_tps'], 0.01);
        $this->assertEqualsWithDelta(6.0, $estimate['eval_tps'], 0.01);
        $this->assertSame(600, $estimate['eval_tokens']);
        $this->assertSame(15000.0, (float) $estimate['load_ms']);
        $this->assertFalse(AiStats::estimate('other:model', 'create')['measured']);
    }

    public function test_google_maps_icon_glyphs_and_hidden_marks_are_removed_from_pasted_text(): void
    {
        $pasted = "عيادة دكتور احمد\u{E0C8}\n\u{200E}010 1234 5678\u{200F}\n\n\n\nمفتوح ⋅ يغلق في ١٠\u{202F}م";

        $this->assertSame("عيادة دكتور احمد\n010 1234 5678\n\nمفتوح ⋅ يغلق في 10 م", PastedText::clean($pasted));
    }

    public function test_the_business_type_is_guessed_from_whole_words(): void
    {
        $categories = ['أزياء وملابس', 'عيادات وخدمات طبية', 'مقاولات وديكور', 'صالونات وتجميل', 'سفر وسياحة'];

        $this->assertSame('عيادات وخدمات طبية', CategoryGuesser::guess('عيادة دكتور احمد انور الهلالي طبيب أسنان نظرة عامة الاتجاهات حفظ', $categories)['category']);
        // "سباكة" مش "سبا" (صالون)، و"احجز" مش "حج" (سياحة).
        $this->assertSame('مقاولات وديكور', CategoryGuesser::guess('سباكة وتشطيبات — احجز معاينة', $categories)['category']);
        $this->assertNull(CategoryGuesser::guess('كلام عام مفيهوش أي نشاط', $categories));
    }

    public function test_when_the_ai_is_off_a_dental_clinic_still_gets_a_medical_template_and_the_real_reason(): void
    {
        $this->libraryTemplate('أزياء وملابس', 'متجر أزياء — إطلالة عصرية');
        $clinic = $this->libraryTemplate('عيادات وخدمات طبية', 'عيادة أسنان — ابتسامة واثقة');
        Http::fake(['*' => fn () => throw new ConnectionException('cURL error 7: Failed to connect')]);

        $result = app(AiProjectAssistantService::class)->createFromMessage("عيادة دكتور احمد انور الهلالي\u{E0C8}\nطبيب أسنان\nالمنصورة");

        $this->assertTrue($result['ok']);
        $this->assertSame($clinic->id, $result['project']->template_id);
        $this->assertSame('عيادة دكتور احمد انور الهلالي', $result['project']->name);
        $this->assertStringContainsString('Ollama', $result['reply']);
        $this->assertStringNotContainsString("\u{E0C8}", $result['project']->aiChatMessages()->first()->content);
    }

    public function test_when_the_ai_is_off_and_the_text_says_nothing_no_junk_project_is_created(): void
    {
        $this->libraryTemplate('أزياء وملابس', 'متجر أزياء — إطلالة عصرية');
        Http::fake(['*' => fn () => throw new ConnectionException('cURL error 7: Failed to connect')]);

        $result = app(AiProjectAssistantService::class)->createFromMessage('مرحبا');

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('Ollama', $result['reply']);
        $this->assertSame(0, Project::count());
    }

    public function test_the_create_request_restricts_the_category_to_the_real_list_and_uses_the_answer(): void
    {
        $this->libraryTemplate('أزياء وملابس', 'متجر أزياء — إطلالة عصرية');
        $clinic = $this->libraryTemplate('عيادات وخدمات طبية', 'عيادة أسنان — ابتسامة واثقة');

        Http::fake([
            '*/api/tags' => Http::response(['models' => [['name' => 'qwen3:8b']]], 200),
            '*/api/generate' => Http::response(['response' => json_encode([
                'category' => 'عيادات وخدمات طبية',
                'project_name' => 'عيادة د. أحمد الهلالي',
                'style_hint' => '',
                'content' => ['hero_title' => 'ابتسامتك في أمان', 'services_list' => ['تبييض', 'تقويم', 'زراعة'], 'contact_link' => 'example.com'],
            ], JSON_UNESCAPED_UNICODE), 'eval_count' => 300, 'eval_duration' => 50_000_000_000], 200),
        ]);
        config(['services.ollama.model' => 'qwen3:8b']);

        $result = app(AiProjectAssistantService::class)->createFromMessage('عيادة أسنان في المنصورة');

        $this->assertSame($clinic->id, $result['project']->template_id);
        $this->assertSame('عيادة د. أحمد الهلالي', $result['project']->name);
        $content = $result['project']->site->content_json;
        $this->assertSame(['تبييض', 'تقويم', 'زراعة'], $content['services_list']);
        $this->assertArrayNotHasKey('contact_link', $content, 'invented links never get saved');

        Http::assertSent(function (Request $request) {
            if (! str_ends_with($request->url(), '/api/generate')) {
                return false;
            }
            $enum = $request['format']['properties']['category']['enum'] ?? [];

            return in_array('عيادات وخدمات طبية', $enum, true) && str_contains($request['prompt'], 'الأرجح إن الفئة "عيادات وخدمات طبية"');
        });
    }
}
