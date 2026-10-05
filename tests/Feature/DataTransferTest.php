<?php

namespace Tests\Feature;

use App\Models\AiChatMessage;
use App\Models\GeneratedSite;
use App\Models\Project;
use App\Models\Template;
use App\Models\TemplateVariant;
use App\Models\UnsupportedRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

// نقل البيانات من السيرفر للجهاز المحلي (barq:export-data / barq:import-data، 2026-10-05).
// الرحلة الكاملة MySQL → SQLite اتجرّبت يدوي على MariaDB حقيقي (كل الجداول طلعت مطابقة)؛
// التستات دي بتثبّت نفس السلوك على SQLite بس، ومعاه حمايات الاستيراد.
class DataTransferTest extends TestCase
{
    use RefreshDatabase;

    private string $workDir;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->workDir = storage_path('framework/testing/data-transfer-'.uniqid());
        File::ensureDirectoryExists($this->workDir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->workDir);

        parent::tearDown();
    }

    /**
     * @return array{user: User, project: Project, site: GeneratedSite}
     */
    private function seedRealisticData(): array
    {
        $user = User::factory()->create(['email' => 'owner@example.com', 'remember_token' => 'stale-token']);
        $template = Template::factory()->create(['kind' => 'landing', 'layout' => 'gallery']);
        $template->slots()->create([
            'section_key' => 'hero',
            'key' => 'hero_title',
            'label_ar' => 'العنوان',
            'slot_type' => 'text',
            'default_value' => 'عنوان افتراضي',
            'sort_order' => 1,
        ]);
        $variant = TemplateVariant::factory()->for($template)->create(['colors_json' => ['primary' => '#112233']]);

        $project = Project::factory()->for($template)->create([
            'template_variant_id' => $variant->id,
            'name' => 'مطعم الشيف',
            'created_by' => $user->id,
        ]);

        Storage::disk('public')->put('site-images/hero.jpg', 'fake-jpeg-bytes');

        $site = GeneratedSite::factory()->for($project)->create([
            'slug' => 'chef-site',
            'content_json' => [
                'hero_title' => "أهلاً <b>بيكم</b> في \"مطعم\" الشيف\nسطر تاني",
                'hero_image' => '/storage/site-images/hero.jpg',
            ],
            'style_overrides_json' => ['hero_title' => ['color' => '#ff0000', 'posX' => 12.5, 'width' => 55.25]],
            'font_size_scale_override' => 1.1,
            'custom_blocks_json' => [['key' => 'custom_1_abc', 'type' => 'text', 'content' => 'مربع جديد']],
        ]);

        AiChatMessage::create(['project_id' => $project->id, 'role' => 'user', 'content' => 'عايز موقع لمطعم']);
        UnsupportedRequest::create(['generated_site_id' => $site->id, 'prompt_text' => 'عايز متجر كامل']);

        return compact('user', 'project', 'site');
    }

    public function test_export_then_import_restores_every_table_and_uploaded_image_exactly(): void
    {
        ['user' => $user, 'project' => $project, 'site' => $site] = $this->seedRealisticData();
        $zipPath = $this->workDir.'/backup.zip';

        $this->artisan('barq:export-data', ['--output' => $zipPath])
            ->expectsOutputToContain('Uploaded images: 1')
            ->assertSuccessful();

        // التشغيل المحلي قبل الاستيراد: بيانات مختلفة تماماً (أدمن تاني + مشروع تاني) وصورة ناقصة.
        AiChatMessage::query()->delete();
        UnsupportedRequest::query()->delete();
        GeneratedSite::query()->delete();
        Project::query()->delete();
        User::query()->delete();
        User::factory()->create(['email' => 'local-only@example.com']);
        Project::factory()->create(['name' => 'مشروع محلي هيتمسح']);
        Storage::disk('public')->delete('site-images/hero.jpg');

        $this->artisan('barq:import-data', ['path' => $zipPath, '--force' => true])
            ->expectsOutputToContain('Uploaded images restored: 1')
            ->assertSuccessful();

        $this->assertSame(['owner@example.com'], User::pluck('email')->all());
        $this->assertNull(User::first()->remember_token);
        $this->assertSame([$project->id], Project::pluck('id')->all());
        $this->assertSame($user->id, Project::first()->created_by);

        $restored = GeneratedSite::firstOrFail();
        $this->assertSame($site->id, $restored->id);
        $this->assertSame($site->content_json, $restored->content_json);
        $this->assertSame($site->style_overrides_json, $restored->style_overrides_json);
        $this->assertSame($site->custom_blocks_json, $restored->custom_blocks_json);
        $this->assertSame('1.10', $restored->font_size_scale_override);
        $this->assertSame(['#112233'], TemplateVariant::pluck('colors_json')->pluck('primary')->all());
        $this->assertSame('عايز موقع لمطعم', AiChatMessage::firstOrFail()->content);
        $this->assertSame($site->id, UnsupportedRequest::firstOrFail()->generated_site_id);
        Storage::disk('public')->assertExists('site-images/hero.jpg');
        $this->assertSame('fake-jpeg-bytes', Storage::disk('public')->get('site-images/hero.jpg'));

        // الإضافة بعد الاستيراد بتكمّل الترقيم من غير تصادم مع الـIDs المستوردة.
        $this->assertGreaterThan($project->id, Project::factory()->create()->id);
    }

    public function test_import_asks_first_and_changes_nothing_when_declined(): void
    {
        $this->seedRealisticData();
        $zipPath = $this->workDir.'/backup.zip';
        $this->artisan('barq:export-data', ['--output' => $zipPath])->assertSuccessful();

        Project::factory()->create(['name' => 'لازم يفضل موجود']);

        $this->artisan('barq:import-data', ['path' => $zipPath])
            ->expectsConfirmation(
                'This DELETES all current templates, projects, sites, chat history and admin accounts here, and replaces them with the backup. Continue?',
                'no',
            )
            ->assertFailed();

        $this->assertTrue(Project::where('name', 'لازم يفضل موجود')->exists());
    }

    public function test_import_rejects_a_zip_that_is_not_a_backup_without_touching_data(): void
    {
        $user = User::factory()->create();
        $zipPath = $this->workDir.'/random.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE);
        $zip->addFromString('readme.txt', 'not a backup');
        $zip->close();

        $this->artisan('barq:import-data', ['path' => $zipPath, '--force' => true])
            ->expectsOutputToContain('not a data backup')
            ->assertFailed();

        $this->assertModelExists($user);
    }

    public function test_import_refuses_backups_with_paths_that_escape_the_storage_folder(): void
    {
        $this->seedRealisticData();
        $zipPath = $this->workDir.'/backup.zip';
        $this->artisan('barq:export-data', ['--output' => $zipPath])->assertSuccessful();

        $zip = new ZipArchive;
        $zip->open($zipPath);
        $zip->addFromString('files/../../evil.jpg', 'x');
        $zip->close();

        $marker = Project::factory()->create(['name' => 'مشروع محلي']);

        $this->artisan('barq:import-data', ['path' => $zipPath, '--force' => true])
            ->expectsOutputToContain('Unsafe file path')
            ->assertFailed();

        $this->assertModelExists($marker);
    }

    public function test_import_skips_files_that_are_not_images(): void
    {
        $this->seedRealisticData();
        Storage::disk('public')->put('site-images/shell.php', '<?php echo 1;');
        $zipPath = $this->workDir.'/backup.zip';
        $this->artisan('barq:export-data', ['--output' => $zipPath])->assertSuccessful();

        Storage::disk('public')->delete(['site-images/shell.php', 'site-images/hero.jpg']);

        $this->artisan('barq:import-data', ['path' => $zipPath, '--force' => true])
            ->expectsOutputToContain('Skipped (not an image): site-images/shell.php')
            ->assertSuccessful();

        Storage::disk('public')->assertExists('site-images/hero.jpg');
        Storage::disk('public')->assertMissing('site-images/shell.php');
    }
}
