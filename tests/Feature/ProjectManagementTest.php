<?php

namespace Tests\Feature;

use App\Models\GeneratedSite;
use App\Models\Project;
use App\Models\Template;
use App\Models\TemplateVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_projects(): void
    {
        $response = $this->get('/projects');

        $response->assertRedirect(route('login'));
    }

    public function test_create_page_shows_template_picker_without_a_chosen_template(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['name' => 'قالب المطاعم']);

        $response = $this->actingAs($user)->get(route('projects.create'));

        $response->assertOk();
        $response->assertSee('قالب المطاعم');
        $response->assertSee(route('projects.create', ['template' => $template->id]), false);
    }

    public function test_create_page_shows_the_full_form_once_a_template_is_chosen(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['name' => 'قالب المطاعم']);
        $variant = TemplateVariant::factory()->for($template)->create(['name' => 'الأساسية']);

        $response = $this->actingAs($user)->get(route('projects.create', ['template' => $template->id]));

        $response->assertOk();
        $response->assertSee('قالب المطاعم');
        $response->assertSee('الأساسية');
    }

    public function test_admin_can_create_a_project_and_its_site_is_generated_atomically(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();
        $variant = TemplateVariant::factory()->for($template)->create();

        $response = $this->actingAs($user)->post('/projects', [
            'template_id' => $template->id,
            'template_variant_id' => $variant->id,
            'name' => 'مطعم بيت الطعمية',
            'contact_name' => 'محمد أحمد',
            'contact_phone' => '01000000000',
            'contact_email' => 'test@example.com',
        ]);

        $project = Project::first();

        $response->assertRedirect(route('projects.show', $project));
        $this->assertSame('مطعم بيت الطعمية', $project->name);
        $this->assertSame($template->id, $project->template_id);
        $this->assertSame($variant->id, $project->template_variant_id);
        $this->assertSame('draft', $project->status);
        $this->assertNotEmpty($project->slug);

        $site = GeneratedSite::where('project_id', $project->id)->first();
        $this->assertNotNull($site);
        $this->assertSame('draft', $site->status);
        $this->assertSame([], $site->content_json);
        $this->assertNotEmpty($site->slug);
    }

    public function test_project_and_site_slugs_are_unique_when_names_collide(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();

        $this->actingAs($user)->post('/projects', [
            'template_id' => $template->id,
            'name' => 'مشروع تجريبي',
        ]);

        $this->actingAs($user)->post('/projects', [
            'template_id' => $template->id,
            'name' => 'مشروع تجريبي',
        ]);

        $projectSlugs = Project::orderBy('id')->pluck('slug')->all();
        $siteSlugs = GeneratedSite::query()->orderBy('id')->pluck('slug')->all();

        $this->assertCount(2, array_unique($projectSlugs));
        $this->assertCount(2, array_unique($siteSlugs));
    }

    public function test_a_variant_belonging_to_a_different_template_is_rejected_on_create(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();
        $otherTemplate = Template::factory()->create();
        $foreignVariant = TemplateVariant::factory()->for($otherTemplate)->create();

        $response = $this->actingAs($user)->post('/projects', [
            'template_id' => $template->id,
            'template_variant_id' => $foreignVariant->id,
            'name' => 'مشروع تجريبي',
        ]);

        $response->assertSessionHasErrors('template_variant_id');
        $this->assertDatabaseCount('projects', 0);
        $this->assertDatabaseCount('generated_sites', 0);
    }

    public function test_admin_can_view_the_projects_index_and_show_pages(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();
        $project = Project::factory()->for($template)->create(['name' => 'مشروع العرض']);
        GeneratedSite::factory()->for($project)->create();

        $index = $this->actingAs($user)->get('/projects');
        $index->assertOk();
        $index->assertSee('مشروع العرض');

        $show = $this->actingAs($user)->get(route('projects.show', $project));
        $show->assertOk();
        $show->assertSee('مشروع العرض');
    }

    public function test_admin_can_update_a_project(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();
        $project = Project::factory()->for($template)->create(['name' => 'اسم قديم']);

        $response = $this->actingAs($user)->put(route('projects.update', $project), [
            'name' => 'اسم جديد',
            'status' => 'generated',
        ]);

        $response->assertRedirect(route('projects.show', $project));
        $project->refresh();
        $this->assertSame('اسم جديد', $project->name);
        $this->assertSame('generated', $project->status);
    }

    public function test_a_project_cannot_be_updated_with_a_variant_from_a_different_template(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();
        $otherTemplate = Template::factory()->create();
        $foreignVariant = TemplateVariant::factory()->for($otherTemplate)->create();
        $project = Project::factory()->for($template)->create();

        $response = $this->actingAs($user)->put(route('projects.update', $project), [
            'name' => $project->name,
            'template_variant_id' => $foreignVariant->id,
            'status' => 'draft',
        ]);

        $response->assertSessionHasErrors('template_variant_id');
        $this->assertNull($project->fresh()->template_variant_id);
    }

    public function test_admin_can_deliver_a_project(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();
        $project = Project::factory()->for($template)->create(['status' => 'generated']);

        $response = $this->actingAs($user)->post(route('projects.deliver', $project));

        $response->assertRedirect(route('projects.show', $project));
        $this->assertSame('delivered', $project->fresh()->status);
    }

    public function test_deleting_a_project_cascades_to_its_generated_site(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();
        $project = Project::factory()->for($template)->create();
        $site = GeneratedSite::factory()->for($project)->create();

        $response = $this->actingAs($user)->delete(route('projects.destroy', $project));

        $response->assertRedirect(route('projects.index'));
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        $this->assertDatabaseMissing('generated_sites', ['id' => $site->id]);
    }

    public function test_admin_can_view_the_site_content_editor(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();
        $template->slots()->create([
            'section_key' => 'hero',
            'key' => 'hero_title',
            'label_ar' => 'عنوان الصفحة',
            'slot_type' => 'text',
        ]);
        $project = Project::factory()->for($template)->create();
        GeneratedSite::factory()->for($project)->create();

        $response = $this->actingAs($user)->get(route('projects.site.edit', $project));

        $response->assertOk();
        $response->assertSee('عنوان الصفحة');
    }

    public function test_admin_can_save_text_textarea_list_and_link_content(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();
        $template->slots()->create(['section_key' => 'hero', 'key' => 'hero_title', 'label_ar' => 'عنوان', 'slot_type' => 'text']);
        $template->slots()->create(['section_key' => 'hero', 'key' => 'hero_desc', 'label_ar' => 'وصف', 'slot_type' => 'textarea']);
        $template->slots()->create(['section_key' => 'services', 'key' => 'services_list', 'label_ar' => 'الخدمات', 'slot_type' => 'list']);
        $template->slots()->create(['section_key' => 'contact', 'key' => 'contact_link', 'label_ar' => 'رابط التواصل', 'slot_type' => 'link']);
        $project = Project::factory()->for($template)->create();
        $site = GeneratedSite::factory()->for($project)->create();

        $response = $this->actingAs($user)->put(route('projects.site.update', $project), [
            'content' => [
                'hero_title' => 'أهلاً بيكم',
                'hero_desc' => "أحسن مطعم في المدينة\nمفتوح 24 ساعة",
                'services_list' => "توصيل\nحجز طاولات\n\nمناسبات",
                'contact_link' => 'https://wa.me/201000000000',
            ],
        ]);

        $response->assertRedirect(route('projects.show', $project));
        $site->refresh();

        $this->assertSame('أهلاً بيكم', $site->content('hero_title'));
        $this->assertSame("أحسن مطعم في المدينة\nمفتوح 24 ساعة", $site->content('hero_desc'));
        $this->assertSame(['توصيل', 'حجز طاولات', 'مناسبات'], $site->content('services_list'));
        $this->assertSame('https://wa.me/201000000000', $site->content('contact_link'));
    }

    public function test_admin_can_upload_an_image_for_an_image_slot(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $template = Template::factory()->create();
        $template->slots()->create(['section_key' => 'hero', 'key' => 'hero_image', 'label_ar' => 'صورة', 'slot_type' => 'image']);
        $project = Project::factory()->for($template)->create();
        $site = GeneratedSite::factory()->for($project)->create();

        $file = UploadedFile::fake()->image('hero.jpg');

        $response = $this->actingAs($user)->put(route('projects.site.update', $project), [
            'content_files' => ['hero_image' => $file],
        ]);

        $response->assertRedirect(route('projects.show', $project));
        $site->refresh();

        $this->assertNotNull($site->content('hero_image'));
        $storedPath = str_replace('/storage/', '', $site->content('hero_image'));
        Storage::disk('public')->assertExists($storedPath);
    }

    // بدون التحقق ده أي ملف (حتى .php) كان هيتخزن زي ما هو في storage العامة — رفض أي ملف
    // مش صورة حقيقية بامتداد معروف قبل ما يوصل للتخزين خالص.
    public function test_uploading_a_non_image_file_for_an_image_slot_is_rejected(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $template = Template::factory()->create();
        $template->slots()->create(['section_key' => 'hero', 'key' => 'hero_image', 'label_ar' => 'صورة', 'slot_type' => 'image']);
        $project = Project::factory()->for($template)->create();
        GeneratedSite::factory()->for($project)->create();

        $file = UploadedFile::fake()->create('shell.php', 10, 'application/x-php');

        $response = $this->actingAs($user)->put(route('projects.site.update', $project), [
            'content_files' => ['hero_image' => $file],
        ]);

        $response->assertSessionHasErrors('content_files.hero_image');
        Storage::disk('public')->assertDirectoryEmpty('site-images');
    }

    public function test_updating_site_content_preserves_fields_that_were_not_submitted(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();
        $template->slots()->create(['section_key' => 'hero', 'key' => 'hero_title', 'label_ar' => 'عنوان', 'slot_type' => 'text']);
        $template->slots()->create(['section_key' => 'hero', 'key' => 'hero_desc', 'label_ar' => 'وصف', 'slot_type' => 'textarea']);
        $project = Project::factory()->for($template)->create();
        $site = GeneratedSite::factory()->for($project)->create(['content_json' => ['hero_title' => 'قديم', 'hero_desc' => 'وصف قديم']]);

        $this->actingAs($user)->put(route('projects.site.update', $project), [
            'content' => ['hero_title' => 'جديد'],
        ]);

        $site->refresh();
        $this->assertSame('جديد', $site->content('hero_title'));
        $this->assertSame('وصف قديم', $site->content('hero_desc'));
    }

    public function test_suggesting_content_only_fills_empty_slots_and_never_overwrites_manual_content(): void
    {
        Http::fake([
            '*/api/generate' => Http::response([
                'response' => json_encode([
                    'hero_title' => 'عنوان مقترح بالذكاء الاصطناعي',
                    'hero_desc' => 'وصف مقترح',
                    'services_list' => ['توصيل', 'حجز طاولات'],
                ]),
            ], 200),
        ]);

        $user = User::factory()->create();
        $template = Template::factory()->create();
        $template->slots()->create(['section_key' => 'hero', 'key' => 'hero_title', 'label_ar' => 'عنوان', 'slot_type' => 'text']);
        $template->slots()->create(['section_key' => 'hero', 'key' => 'hero_desc', 'label_ar' => 'وصف', 'slot_type' => 'textarea']);
        $template->slots()->create(['section_key' => 'services', 'key' => 'services_list', 'label_ar' => 'الخدمات', 'slot_type' => 'list']);
        $project = Project::factory()->for($template)->create();
        $site = GeneratedSite::factory()->for($project)->create(['content_json' => ['hero_title' => 'عنوان كتبه الأدمن يدوي']]);

        $response = $this->actingAs($user)->post(route('projects.site.suggest', $project), [
            'business_description' => 'مطعم فطاير في المهندسين',
        ]);

        $response->assertRedirect(route('projects.site.edit', $project));
        $site->refresh();

        // الخانة اللي كانت مكتوبة يدوي فضلت زي ما هي — الذكاء الاصطناعي مادعّهاش.
        $this->assertSame('عنوان كتبه الأدمن يدوي', $site->content('hero_title'));
        // الخانات الفاضية اتعبّت بالمقترح.
        $this->assertSame('وصف مقترح', $site->content('hero_desc'));
        $this->assertSame(['توصيل', 'حجز طاولات'], $site->content('services_list'));
    }

    public function test_suggesting_content_shows_a_helpful_message_when_ollama_is_unreachable(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection refused');
        });

        $user = User::factory()->create();
        $template = Template::factory()->create();
        $template->slots()->create(['section_key' => 'hero', 'key' => 'hero_title', 'label_ar' => 'عنوان', 'slot_type' => 'text']);
        $project = Project::factory()->for($template)->create();
        $site = GeneratedSite::factory()->for($project)->create();

        $response = $this->actingAs($user)->post(route('projects.site.suggest', $project), [
            'business_description' => 'مطعم فطاير في المهندسين',
        ]);

        $response->assertRedirect(route('projects.site.edit', $project));
        $response->assertSessionHas('status');
        $this->assertNull($site->fresh()->content('hero_title'));
    }

    public function test_suggest_content_requires_a_business_description(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create();
        $project = Project::factory()->for($template)->create();
        GeneratedSite::factory()->for($project)->create();

        $response = $this->actingAs($user)->post(route('projects.site.suggest', $project), []);

        $response->assertSessionHasErrors('business_description');
        Http::assertNothingSent();
    }

    public function test_admin_can_publish_a_site_which_bumps_a_draft_project_to_generated(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();
        $project = Project::factory()->for($template)->create(['status' => 'draft']);
        $site = GeneratedSite::factory()->for($project)->create(['status' => 'draft']);

        $response = $this->actingAs($user)->post(route('projects.site.publish', $project));

        $response->assertRedirect(route('projects.show', $project));
        $this->assertSame('published', $site->fresh()->status);
        $this->assertNotNull($site->fresh()->last_generated_at);
        $this->assertSame('generated', $project->fresh()->status);
    }

    public function test_admin_can_unpublish_a_site(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();
        $project = Project::factory()->for($template)->create();
        $site = GeneratedSite::factory()->for($project)->create(['status' => 'published']);

        $response = $this->actingAs($user)->post(route('projects.site.unpublish', $project));

        $response->assertRedirect(route('projects.show', $project));
        $this->assertSame('archived', $site->fresh()->status);
    }
}
