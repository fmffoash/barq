<?php

namespace Tests\Feature;

use App\Models\GeneratedSite;
use App\Models\Project;
use App\Models\Template;
use App\Models\TemplateVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Phase 3 — حفظ مشروع منجز كقالب جديد + بحث/فلترة مكتبة القوالب (في صفحة القوالب وفي
// خطوة اختيار القالب وقت عمل مشروع جديد).
class TemplateLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_a_project_as_a_new_template_without_content(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['category' => 'مطاعم', 'kind' => 'landing']);
        $template->slots()->create([
            'section_key' => 'hero',
            'key' => 'hero_title',
            'label_ar' => 'عنوان',
            'slot_type' => 'text',
            'default_value' => 'عنوان افتراضي قديم',
        ]);
        $project = Project::factory()->for($template)->create();

        $response = $this->actingAs($user)->post(route('templates.store-from-project', $project), [
            'name' => 'قالب جديد من المشروع',
        ]);

        $newTemplate = Template::where('name', 'قالب جديد من المشروع')->first();

        $response->assertRedirect(route('templates.show', $newTemplate));
        $this->assertNotNull($newTemplate);
        $this->assertNotSame($template->id, $newTemplate->id);
        $this->assertSame('مطاعم', $newTemplate->category);
        $this->assertSame('landing', $newTemplate->kind);
        $this->assertTrue($newTemplate->is_active);

        // من غير "احفظ بالمحتوى الحالي"، القيمة الافتراضية للخانة بتورّث من القالب الأصلي.
        $newSlot = $newTemplate->slots()->where('key', 'hero_title')->first();
        $this->assertSame('عنوان افتراضي قديم', $newSlot->default_value);
    }

    public function test_admin_can_save_a_project_as_a_new_template_with_its_current_content(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();
        $template->slots()->create([
            'section_key' => 'hero',
            'key' => 'hero_title',
            'label_ar' => 'عنوان',
            'slot_type' => 'text',
            'default_value' => 'عنوان افتراضي قديم',
        ]);
        $template->slots()->create([
            'section_key' => 'hero',
            'key' => 'hero_desc',
            'label_ar' => 'وصف',
            'slot_type' => 'textarea',
        ]);
        $project = Project::factory()->for($template)->create();
        GeneratedSite::factory()->for($project)->create([
            'content_json' => ['hero_title' => 'عنوان المشروع الفعلي'],
        ]);

        $response = $this->actingAs($user)->post(route('templates.store-from-project', $project), [
            'name' => 'قالب بمحتوى المشروع',
            'with_content' => '1',
        ]);

        $newTemplate = Template::where('name', 'قالب بمحتوى المشروع')->first();
        $response->assertRedirect(route('templates.show', $newTemplate));

        $titleSlot = $newTemplate->slots()->where('key', 'hero_title')->first();
        $descSlot = $newTemplate->slots()->where('key', 'hero_desc')->first();

        $this->assertSame('عنوان المشروع الفعلي', $titleSlot->default_value);
        // hero_desc مالوش قيمة في محتوى الموقع أصلاً، فمفيش قيمة افتراضية جديدة ليه.
        $this->assertNull($descSlot->default_value);
    }

    public function test_saving_a_project_as_a_template_requires_a_name(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();
        $project = Project::factory()->for($template)->create();

        $countBefore = Template::count();

        $response = $this->actingAs($user)->post(route('templates.store-from-project', $project), []);

        $response->assertSessionHasErrors('name');
        $this->assertSame($countBefore, Template::count());
    }

    public function test_saving_a_project_as_a_template_falls_back_to_the_source_templates_category_when_none_given(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['category' => 'مطاعم']);
        $project = Project::factory()->for($template)->create();

        $this->actingAs($user)->post(route('templates.store-from-project', $project), [
            'name' => 'قالب بدون تصنيف مُدخَل',
        ]);

        $newTemplate = Template::where('name', 'قالب بدون تصنيف مُدخَل')->first();
        $this->assertSame('مطاعم', $newTemplate->category);
    }

    public function test_saving_a_project_with_a_variant_copies_it_as_the_new_templates_default_variant(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();
        $variant = TemplateVariant::factory()->for($template)->create([
            'name' => 'الأساسية',
            'colors_json' => ['primary' => '#f59e0b'],
            'sections_json' => ['hero', 'services'],
        ]);
        $project = Project::factory()->for($template)->create(['template_variant_id' => $variant->id]);

        $response = $this->actingAs($user)->post(route('templates.store-from-project', $project), [
            'name' => 'قالب بنسخة منسوخة',
        ]);

        $newTemplate = Template::where('name', 'قالب بنسخة منسوخة')->first();
        $response->assertRedirect(route('templates.show', $newTemplate));

        $newVariant = $newTemplate->variants()->first();
        $this->assertNotNull($newVariant);
        $this->assertSame('الأساسية', $newVariant->name);
        $this->assertSame(['primary' => '#f59e0b'], $newVariant->colors_json);
        $this->assertSame(['hero', 'services'], $newVariant->sections_json);
        $this->assertTrue($newVariant->is_default);
    }

    public function test_new_project_content_is_prefilled_from_template_slot_defaults(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();
        $template->slots()->create([
            'section_key' => 'hero',
            'key' => 'hero_title',
            'label_ar' => 'عنوان',
            'slot_type' => 'text',
            'default_value' => 'عنوان جاهز',
        ]);
        $template->slots()->create([
            'section_key' => 'services',
            'key' => 'services_list',
            'label_ar' => 'الخدمات',
            'slot_type' => 'list',
            'default_value' => ['خدمة أولى', 'خدمة تانية'],
        ]);
        // خانة من غير default_value خالص — المفروض متظهرش في المحتوى الابتدائي.
        $template->slots()->create([
            'section_key' => 'hero',
            'key' => 'hero_desc',
            'label_ar' => 'وصف',
            'slot_type' => 'textarea',
        ]);

        $response = $this->actingAs($user)->post('/projects', [
            'template_id' => $template->id,
            'name' => 'مشروع بمحتوى جاهز',
        ]);

        $project = Project::first();
        $response->assertRedirect(route('projects.show', $project));

        $site = GeneratedSite::where('project_id', $project->id)->first();
        $this->assertSame('عنوان جاهز', $site->content('hero_title'));
        $this->assertSame(['خدمة أولى', 'خدمة تانية'], $site->content('services_list'));
        $this->assertNull($site->content('hero_desc'));
    }

    public function test_a_template_with_no_default_values_still_creates_a_project_with_empty_content(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();
        $template->slots()->create([
            'section_key' => 'hero',
            'key' => 'hero_title',
            'label_ar' => 'عنوان',
            'slot_type' => 'text',
        ]);

        $this->actingAs($user)->post('/projects', [
            'template_id' => $template->id,
            'name' => 'مشروع بلا محتوى جاهز',
        ]);

        $site = GeneratedSite::where('project_id', Project::first()->id)->first();
        $this->assertSame([], $site->content_json);
    }

    public function test_template_library_can_be_searched_by_name(): void
    {
        $user = User::factory()->create();
        Template::factory()->create(['name' => 'قالب المطاعم']);
        Template::factory()->create(['name' => 'قالب العيادات']);

        $response = $this->actingAs($user)->get(route('templates.index', ['q' => 'مطاعم']));

        $response->assertOk();
        $response->assertSee('قالب المطاعم');
        $response->assertDontSee('قالب العيادات');
    }

    public function test_template_library_can_be_filtered_by_category(): void
    {
        $user = User::factory()->create();
        Template::factory()->create(['name' => 'قالب أ', 'category' => 'مطاعم']);
        Template::factory()->create(['name' => 'قالب ب', 'category' => 'عيادات']);

        $response = $this->actingAs($user)->get(route('templates.index', ['category' => 'مطاعم']));

        $response->assertOk();
        $response->assertSee('قالب أ');
        $response->assertDontSee('قالب ب');
    }

    public function test_project_template_picker_can_be_searched_and_filtered(): void
    {
        $user = User::factory()->create();
        Template::factory()->create(['name' => 'قالب المطاعم', 'category' => 'مطاعم', 'is_active' => true]);
        Template::factory()->create(['name' => 'قالب العيادات', 'category' => 'عيادات', 'is_active' => true]);

        $bySearch = $this->actingAs($user)->get(route('projects.create', ['q' => 'مطاعم']));
        $bySearch->assertOk();
        $bySearch->assertSee('قالب المطاعم');
        $bySearch->assertDontSee('قالب العيادات');

        $byCategory = $this->actingAs($user)->get(route('projects.create', ['category' => 'عيادات']));
        $byCategory->assertOk();
        $byCategory->assertSee('قالب العيادات');
        $byCategory->assertDontSee('قالب المطاعم');
    }
}
