<?php

namespace Tests\Feature;

use App\Models\GeneratedSite;
use App\Models\Project;
use App\Models\Template;
use App\Services\AiProjectAssistantService;
use App\Services\OllamaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

// المرحلة 4 (2026-09-24) — فيدباك فؤاد الحي: "رجّع" لازم يعني مسح كل تعديل فعلاً (مش رد
// "مش فاهم")، إضافة عنصر جديد ورفع صورة جاهزة لازم يبقوا عن طريق المساعد الذكي بالشات.
// اختبارات المسار السعيد لكل فعل جديد في AiProjectAssistantService، بتعمل mock لـ
// OllamaService::generateJson() عشان تختبر منطق التطبيق نفسه من غير الاعتماد على نموذج حقيقي.
class AiAssistantExtendedActionsTest extends TestCase
{
    use RefreshDatabase;

    private function mockOllamaDecision(array $decision): void
    {
        $mock = Mockery::mock(OllamaService::class);
        $mock->shouldReceive('generateJson')->once()->andReturn($decision);
        $this->app->instance(OllamaService::class, $mock);
    }

    private function buildProject(): Project
    {
        $template = Template::factory()->create(['kind' => 'landing']);
        $template->slots()->create(['section_key' => 'hero', 'key' => 'hero_title', 'label_ar' => 'عنوان', 'slot_type' => 'text']);
        $template->slots()->create(['section_key' => 'hero', 'key' => 'hero_image', 'label_ar' => 'الصورة الرئيسية', 'slot_type' => 'image']);
        $project = Project::factory()->for($template)->create();
        GeneratedSite::factory()->for($project)->create([
            'content_json' => ['hero_title' => 'أهلاً بيكم في مطعمنا'],
            'colors_override_json' => ['primary' => '#ff0000'],
            'font_override' => 'tajawal',
        ]);

        return $project->fresh(['template.slots', 'site']);
    }

    public function test_reset_to_default_clears_every_override(): void
    {
        $project = $this->buildProject();
        $this->mockOllamaDecision(['action' => 'reset_to_default', 'reply' => 'تمام']);

        $reply = app(AiProjectAssistantService::class)->handleFollowUp($project, 'رجّع كل حاجة زي ما كانت');

        $this->assertStringContainsString('رجّع', $reply);
        $site = $project->site->fresh();
        $this->assertNull($site->content_json);
        $this->assertNull($site->colors_override_json);
        $this->assertNull($site->font_override);
    }

    public function test_update_image_stores_attached_file_into_the_decided_slot(): void
    {
        Storage::fake('public');
        $project = $this->buildProject();
        $image = UploadedFile::fake()->image('menu.jpg');
        $this->mockOllamaDecision(['action' => 'update_image', 'slot_key' => 'hero_image', 'reply' => 'تمام']);

        app(AiProjectAssistantService::class)->handleFollowUp($project, 'خليها الصورة الرئيسية', $image);

        $stored = $project->site->fresh()->content('hero_image');
        $this->assertNotNull($stored);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $stored));
    }

    public function test_update_image_without_an_attached_file_asks_for_it_instead_of_failing_silently(): void
    {
        $project = $this->buildProject();
        $this->mockOllamaDecision(['action' => 'update_image', 'slot_key' => 'hero_image', 'reply' => 'تمام']);

        $reply = app(AiProjectAssistantService::class)->handleFollowUp($project, 'خليها الصورة الرئيسية');

        $this->assertStringContainsString('ترفق', $reply);
        $this->assertNull($project->site->fresh()->content('hero_image'));
    }

    public function test_add_custom_block_text_appends_a_new_section(): void
    {
        $project = $this->buildProject();
        $this->mockOllamaDecision([
            'action' => 'add_custom_block',
            'block_type' => 'text',
            'content' => 'عرض خاص على الطلبات أونلاين هذا الأسبوع',
            'label' => 'عرض خاص',
            'reply' => 'تمام',
        ]);

        app(AiProjectAssistantService::class)->handleFollowUp($project, 'ضيف مربع جديد فيه عرض خاص على الطلبات أونلاين');

        $blocks = $project->site->fresh()->custom_blocks_json;
        $this->assertCount(1, $blocks);
        $this->assertSame('text', $blocks[0]['type']);
        $this->assertSame('عرض خاص على الطلبات أونلاين هذا الأسبوع', $blocks[0]['content']);
    }

    public function test_add_custom_block_image_stores_the_attached_file(): void
    {
        Storage::fake('public');
        $project = $this->buildProject();
        $image = UploadedFile::fake()->image('branch.jpg');
        $this->mockOllamaDecision([
            'action' => 'add_custom_block',
            'block_type' => 'image',
            'label' => 'صورة الفرع الجديد',
            'reply' => 'تمام',
        ]);

        app(AiProjectAssistantService::class)->handleFollowUp($project, 'ضيف الصورة دي كمان', $image);

        $blocks = $project->site->fresh()->custom_blocks_json;
        $this->assertCount(1, $blocks);
        $this->assertSame('image', $blocks[0]['type']);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $blocks[0]['content']));
    }

    public function test_remove_custom_block_deletes_the_matching_block_by_label(): void
    {
        $project = $this->buildProject();
        $project->site->update(['custom_blocks_json' => [
            ['key' => 'custom_1_abc', 'type' => 'text', 'label' => 'عرض خاص', 'content' => 'نص'],
        ]]);
        $this->mockOllamaDecision(['action' => 'remove_custom_block', 'label' => 'عرض خاص', 'reply' => 'تمام']);

        app(AiProjectAssistantService::class)->handleFollowUp($project, 'امسح العرض الخاص اللي ضفته');

        $this->assertNull($project->site->fresh()->custom_blocks_json);
    }

    public function test_reset_to_default_is_recognized_even_if_the_model_returns_an_unknown_action(): void
    {
        // شبكة الأمان في handleFollowUp() — نموذج صغير أحياناً بيرجّع action مش معروف رغم
        // إن الرسالة واضحة (نفس فكرة شبكة أمان change_template الموجودة من قبل).
        $project = $this->buildProject();
        $this->mockOllamaDecision(['action' => 'none', 'reply' => 'مش فاهم']);

        $reply = app(AiProjectAssistantService::class)->handleFollowUp($project, 'ارجع بقالك رجّع الموقع زي ما كان الأول');

        $this->assertStringContainsString('رجّع', $reply);
        $this->assertNull($project->site->fresh()->content_json);
    }
}
