<?php

namespace Tests\Feature;

use App\Models\GeneratedSite;
use App\Models\Project;
use App\Models\Template;
use App\Models\TemplateVariant;
use App\Services\AiProjectAssistantService;
use App\Support\FollowUpIntent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// (2026-10-11) شكوى فؤاد بالصورة: "عاجبني القالب بتاع عيادة جلدية، طبّقه على عيادة الأسنان" كان بيختار
// التخسيس، و"قالب عيادة الجلدية مش التخسيس" كان بيرجّع الأسنان. القالب المتسمّي بالاسم يتطبّق بالظبط.
class NamedTemplateTest extends TestCase
{
    use RefreshDatabase;

    private function template(string $name, string $layout): Template
    {
        $template = Template::factory()->create(['kind' => 'landing', 'category' => 'عيادات وخدمات طبية', 'name' => $name, 'layout' => $layout, 'is_active' => true]);
        $template->slots()->create(['section_key' => 'hero', 'key' => 'hero_title', 'label_ar' => 'hero_title', 'slot_type' => 'text', 'sort_order' => 1]);
        TemplateVariant::factory()->for($template)->create(['is_default' => true]);

        return $template;
    }

    public function test_named_template_wins_and_negation_excludes(): void
    {
        $dental = $this->template('عيادة أسنان — ابتسامة بتقنية حديثة', 'modern');
        $derma = $this->template('عيادة جلدية — عناية راقية', 'glass');
        $this->template('مركز تخسيس وتغذية — تحدي ونتائج حقيقية', 'bold');
        $this->template('عيادة عيون — تقنية متطورة', 'duotone');

        $project = Project::factory()->for($dental)->create(['name' => 'عيادة دكتور احمد']);
        GeneratedSite::factory()->for($project)->create(['content_json' => ['hero_title' => 'ابتسامتك']]);
        $project = $project->fresh(['template.slots', 'site']);

        foreach ([
            'القالب ده مش حلو انا عاجبني القالب بتاع عيادة جلديه فا محتاج تطبقه علي عيادة الاسنان',
            'قالب عيادة الجلديه مش التخسيس',
            'عايز قالب الجلدية',
        ] as $message) {
            $decision = FollowUpIntent::detect($message, false, $project, $project->site);
            $this->assertSame('change_template', $decision['action'], $message);
            $this->assertSame($derma->id, $decision['template_id'] ?? null, $message);
        }

        // من غير اسم قالب: الاختيار العادي (مفيش template_id)
        $plain = FollowUpIntent::detect('غيّر القالب لحاجة أفخم', false, $project, $project->site);
        $this->assertArrayNotHasKey('template_id', $plain);

        // التطبيق الفعلي: القالب اتغيّر والمحتوى زي ما هو
        $reply = app(AiProjectAssistantService::class)->handleFollowUp($project, 'قالب عيادة الجلديه مش التخسيس');
        $this->assertStringContainsString('عيادة جلدية', $reply);
        $this->assertSame($derma->id, $project->fresh()->template_id);
        $this->assertSame('ابتسامتك', $project->fresh('site')->site->content_json['hero_title']);
    }
}
