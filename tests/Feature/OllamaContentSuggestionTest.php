<?php

namespace Tests\Feature;

use App\Models\Template;
use App\Services\OllamaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OllamaContentSuggestionTest extends TestCase
{
    use RefreshDatabase;

    private function templateWithSlots(): Template
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
            'section_key' => 'hero',
            'key' => 'hero_logo',
            'label_ar' => 'اللوجو',
            'slot_type' => 'image',
            'sort_order' => 2,
        ]);

        $template->slots()->create([
            'section_key' => 'services',
            'key' => 'services_list',
            'label_ar' => 'الخدمات',
            'slot_type' => 'list',
            'sort_order' => 3,
        ]);

        return $template;
    }

    public function test_it_returns_suggested_content_for_text_and_list_slots(): void
    {
        Http::fake([
            '*/api/generate' => Http::response([
                'response' => json_encode([
                    'hero_title' => 'أهلاً بيكم في مطعمنا',
                    'services_list' => ['فطاير', 'مشروبات ساخنة', 'حلويات'],
                    'hero_logo' => 'http://example.com/logo.png', // خانة صورة — لازم تتشال
                    'some_unknown_key' => 'قيمة زيادة', // مفتاح مش موجود في القالب — لازم يتشال
                ]),
            ], 200),
        ]);

        $suggestions = (new OllamaService)->suggestContent($this->templateWithSlots(), 'مطعم فطاير في المهندسين');

        $this->assertSame('أهلاً بيكم في مطعمنا', $suggestions['hero_title']);
        $this->assertSame(['فطاير', 'مشروبات ساخنة', 'حلويات'], $suggestions['services_list']);
        $this->assertArrayNotHasKey('hero_logo', $suggestions);
        $this->assertArrayNotHasKey('some_unknown_key', $suggestions);
    }

    public function test_it_never_sends_image_slots_to_ollama(): void
    {
        Http::fake([
            '*/api/generate' => Http::response(['response' => json_encode(['hero_title' => 'عنوان'])], 200),
        ]);

        (new OllamaService)->suggestContent($this->templateWithSlots(), 'مطعم فطاير في المهندسين');

        Http::assertSent(function ($request) {
            return ! str_contains($request['prompt'] ?? '', 'hero_logo');
        });
    }

    public function test_it_returns_empty_array_when_ollama_is_unreachable(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection refused');
        });

        $suggestions = (new OllamaService)->suggestContent($this->templateWithSlots(), 'مطعم فطاير في المهندسين');

        $this->assertSame([], $suggestions);
    }

    public function test_it_returns_empty_array_when_ollama_responds_with_invalid_json(): void
    {
        Http::fake([
            '*/api/generate' => Http::response(['response' => 'مش JSON خالص'], 200),
        ]);

        $suggestions = (new OllamaService)->suggestContent($this->templateWithSlots(), 'مطعم فطاير في المهندسين');

        $this->assertSame([], $suggestions);
    }

    public function test_it_returns_empty_array_when_ollama_returns_a_non_success_status(): void
    {
        Http::fake([
            '*/api/generate' => Http::response('', 500),
        ]);

        $suggestions = (new OllamaService)->suggestContent($this->templateWithSlots(), 'مطعم فطاير في المهندسين');

        $this->assertSame([], $suggestions);
    }

    public function test_it_skips_the_http_call_entirely_when_the_template_has_no_suggestable_slots(): void
    {
        $template = Template::factory()->create(['kind' => 'landing']);
        $template->slots()->create([
            'section_key' => 'hero',
            'key' => 'hero_logo',
            'label_ar' => 'اللوجو',
            'slot_type' => 'image',
            'sort_order' => 1,
        ]);

        Http::fake();

        $suggestions = (new OllamaService)->suggestContent($template, 'مطعم فطاير في المهندسين');

        $this->assertSame([], $suggestions);
        Http::assertNothingSent();
    }
}
