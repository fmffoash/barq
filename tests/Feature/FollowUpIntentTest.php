<?php

namespace Tests\Feature;

use App\Models\GeneratedSite;
use App\Models\Project;
use App\Models\Template;
use App\Models\TemplateVariant;
use App\Services\AiProjectAssistantService;
use App\Support\FollowUpIntent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

// رسايل التعديل الواضحة بتتنفّذ من غير ذكاء اصطناعي (2026-10-08) — وكمان اللي مش واضحة لازم
// تفضل رايحة للنموذج (أي غلطة هنا = تعديل فؤاد مطلبوش، زي مسح كل حاجة).
class FollowUpIntentTest extends TestCase
{
    use RefreshDatabase;

    private function project(): Project
    {
        $template = Template::factory()->create(['kind' => 'landing', 'category' => 'مطاعم وكافيهات', 'name' => 'مطعم — واجهة دافئة', 'layout' => 'classic', 'is_active' => true]);
        foreach ([['hero', 'hero_title', 'text', 1], ['hero', 'hero_subtitle', 'textarea', 2], ['hero', 'hero_image', 'image', 4], ['services', 'services_list', 'list', 1],
            ['gallery', 'gallery_image_1', 'image', 2], ['gallery', 'gallery_image_2', 'image', 3], ['testimonials', 'testimonials_title', 'text', 1], ['testimonials', 'testimonials_list', 'list', 2]] as [$s, $k, $t, $o]) {
            $template->slots()->create(['section_key' => $s, 'key' => $k, 'label_ar' => $k, 'slot_type' => $t, 'sort_order' => $o]);
        }
        TemplateVariant::factory()->for($template)->create(['is_default' => true]);
        $project = Project::factory()->for($template)->create(['name' => 'مطعم أبو علي']);
        GeneratedSite::factory()->for($project)->create(['content_json' => ['hero_title' => 'قديم', 'testimonials_list' => ['كلام حقيقي — منى']]]);

        return $project->fresh(['template.slots', 'site']);
    }

    public function test_clear_requests_are_understood_and_vague_ones_go_to_the_ai(): void
    {
        $project = $this->project();
        $site = $project->site;

        $cases = [
            // [الرسالة, فيه صورة؟, الفعل المتوقع (null = للنموذج), تفاصيل]
            ['رجّع كل حاجة زي ما كانت', false, 'reset_to_default', []],
            ['الغي كل التعديلات', false, 'reset_to_default', []],
            ['رجّع العنوان زي ما كان', false, null, []],
            ['امسح كل الخدمات', false, null, []],
            ['غيّر القالب', false, 'change_template', []],
            ['عايز شكل تاني أفخم شوية', false, 'change_template', []],
            ['الشكل حلو كده', false, null, []],
            ['خليه أزرق', false, 'update_colors', ['colors' => ['primary' => '#2563eb']]],
            ['خلي اللون أزرق غامق', false, 'update_colors', ['colors' => ['primary' => '#1e3a8a']]],
            ['خلي الخلفية بيضا', false, 'update_colors', ['colors' => ['background' => '#ffffff', 'surface' => '#f3f4f6', 'text' => '#111827', 'muted' => '#4b5563']]],
            ['خليه غامق', false, 'update_colors', ['colors' => ['background' => '#0b0f19', 'surface' => '#111827', 'text' => '#f1f5f9', 'muted' => '#94a3b8']]],
            ['الخط يبقى تجوال', false, 'update_font', ['font' => 'tajawal']],
            ['غير الخط لـ Poppins', false, 'update_font', ['font' => 'poppins']],
            ['عايز خط كلاسيكي', false, 'update_font', ['font' => 'amiri']],
            ['كبّر الخط شوية', false, 'update_font_size', ['scale' => 1.1]],
            ['الكلام أصغر', false, 'update_font_size', ['scale' => 0.9]],
            ['الخط كبير', false, null, []],
            ['اكتب المحتوى من جديد', false, 'rewrite_content', []],
            ['اكتب في المحتوى إننا بنقفل يوم الجمعة', false, null, []],
            ['غيّر العنوان الرئيسي لـ "أحلى فطير في مصر"', false, null, []],
            ['خليها صورة الغلاف', true, 'update_image', ['slot_key' => 'hero_image']],
            ['حطها في المعرض التانية', true, 'update_image', ['slot_key' => 'gallery_image_2']],
            ['ضيف الصورة دي كمان', true, 'add_custom_block', ['block_type' => 'image']],
            ['حط الصورة دي جنب الخدمات', true, null, []],
        ];

        foreach ($cases as [$message, $image, $action, $details]) {
            $decision = FollowUpIntent::detect($message, $image, $project, $site);
            $this->assertSame($action, $decision['action'] ?? null, "«{$message}»");
            foreach ($details as $key => $value) {
                $this->assertSame($value, $decision[$key] ?? null, "«{$message}» → {$key}");
            }
        }
    }

    public function test_a_clear_request_is_applied_at_once_without_calling_the_ai(): void
    {
        $project = $this->project();
        Http::fake();

        $reply = app(AiProjectAssistantService::class)->handleFollowUp($project, 'كبّر الخط شوية');

        $this->assertStringContainsString('110%', $reply);
        $this->assertSame('1.10', (string) $project->site->fresh()->font_size_scale_override);
        Http::assertNothingSent();
        $this->assertSame(2, $project->aiChatMessages()->count());
    }

    public function test_rewriting_the_content_overwrites_texts_but_never_real_reviews(): void
    {
        $project = $this->project();
        Http::fake([
            '*/api/tags' => Http::response(['models' => [['name' => 'qwen3:8b']]]),
            '*/api/generate' => Http::response(['response' => json_encode([
                'hero_title' => 'عنوان جديد خالص', 'hero_subtitle' => 'وصف جديد', 'services_list' => ['فطير', 'حلو'],
            ], JSON_UNESCAPED_UNICODE), 'eval_count' => 50, 'eval_duration' => 10_000_000_000]),
        ]);
        config(['services.ollama.model' => 'qwen3:8b']);

        $reply = app(AiProjectAssistantService::class)->handleFollowUp($project, 'اكتب المحتوى من جديد بأسلوب أشيك');

        $content = $project->site->fresh()->content_json;
        $this->assertStringContainsString('3 خانة', $reply);
        $this->assertSame('عنوان جديد خالص', $content['hero_title']);
        $this->assertSame(['كلام حقيقي — منى'], $content['testimonials_list']);

        Http::assertSent(function (Request $request) {
            if (! str_ends_with($request->url(), '/api/generate')) {
                return false;
            }
            $keys = array_keys($request['format']['properties']);

            return ! in_array('testimonials_list', $keys, true) && in_array('hero_title', $keys, true)
                && str_contains($request['prompt'], 'بأسلوب أشيك');
        });
    }

    public function test_anything_unclear_goes_to_the_ai_with_a_strict_reply_shape(): void
    {
        $project = $this->project();
        Http::fake([
            '*/api/tags' => Http::response(['models' => [['name' => 'qwen3:8b']]]),
            '*/api/generate' => Http::response(['response' => json_encode(['action' => 'update_content', 'changes' => ['hero_title' => 'أحلى فطير في مصر'], 'reply' => 'تمام'], JSON_UNESCAPED_UNICODE)]),
        ]);
        config(['services.ollama.model' => 'qwen3:8b']);

        app(AiProjectAssistantService::class)->handleFollowUp($project, 'غيّر العنوان الرئيسي لـ "أحلى فطير في مصر"');

        $this->assertSame('أحلى فطير في مصر', $project->site->fresh()->content_json['hero_title']);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/api/generate')
            && in_array('update_content', $request['format']['properties']['action']['enum'], true)
            && array_key_exists('hero_title', (array) $request['format']['properties']['changes']['properties'])
            && in_array('tajawal', $request['format']['properties']['font']['enum'], true));
    }
}
