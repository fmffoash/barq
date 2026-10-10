<?php

namespace Tests\Feature;

use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use PDO;
use Symfony\Component\Process\Process;
use Tests\TestCase;

// التشغيل المحلي على جهاز فؤاد (2026-10-05): barq:doctor (فحص الصحة) وbarq:local-setup
// (التجهيز اللي بتناديه سكريبتات local/). السكريبتات نفسها اتجرّبت يدوي من الأول للآخر.
class LocalMachineSetupTest extends TestCase
{
    use RefreshDatabase;

    private function fakeOllamaWithModels(array $models): void
    {
        Http::fake([
            '*/api/tags' => Http::response(['models' => array_map(fn ($name) => ['name' => $name], $models)]),
        ]);
    }

    public function test_doctor_fails_when_there_is_no_admin_account_and_says_how_to_fix_it(): void
    {
        $this->fakeOllamaWithModels(['qwen3:8b']);

        $this->artisan('barq:doctor')
            ->expectsOutputToContain('[FAIL] Admin account')
            ->expectsOutputToContain(windows_os() ? 'fix: local\\create-admin.bat' : 'fix: php artisan barq:create-admin')
            ->assertFailed();
    }

    public function test_doctor_passes_and_confirms_the_configured_ollama_model_is_downloaded(): void
    {
        config(['services.ollama.model' => 'qwen3:8b']);
        User::factory()->create();
        Template::factory()->create();
        $this->fakeOllamaWithModels(['llama3:latest', 'qwen3:8b']);

        $this->artisan('barq:doctor')
            ->expectsOutputToContain('Ollama model qwen3:8b')
            ->doesntExpectOutputToContain('ollama pull')
            ->expectsOutputToContain('All required checks passed.')
            ->assertSuccessful();
    }

    public function test_doctor_tells_how_to_download_a_missing_ollama_model_without_failing(): void
    {
        config(['services.ollama.model' => 'qwen3:4b']);
        User::factory()->create();
        Template::factory()->create();
        $this->fakeOllamaWithModels(['qwen3:8b']);

        $this->artisan('barq:doctor')
            ->expectsOutputToContain('[WARN] Ollama model qwen3:4b')
            ->expectsOutputToContain('fix: ollama pull qwen3:4b')
            ->assertSuccessful();
    }

    public function test_doctor_treats_an_untagged_model_name_as_latest(): void
    {
        config(['services.ollama.model' => 'mistral']);
        User::factory()->create();
        Template::factory()->create();
        $this->fakeOllamaWithModels(['mistral:latest']);

        $this->artisan('barq:doctor')
            ->doesntExpectOutputToContain('ollama pull')
            ->assertSuccessful();
    }

    public function test_doctor_only_warns_when_ollama_is_not_running(): void
    {
        User::factory()->create();
        Template::factory()->create();
        Http::fake(fn () => throw new ConnectionException('Connection refused'));

        $this->artisan('barq:doctor')
            ->expectsOutputToContain('[WARN] Ollama is not running')
            ->assertSuccessful();
    }

    public function test_local_setup_refuses_to_run_on_the_production_server(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->artisan('barq:local-setup')
            ->expectsOutputToContain('local machines only')
            ->assertFailed();
    }

    public function test_local_setup_creates_the_admin_account_when_none_exists_and_skips_an_existing_library(): void
    {
        if (windows_os()) {
            $this->markTestSkipped('على ويندوز الأمر مبيسألش عن الحساب (setup.ps1 بيسأل بنفسه) — شوف LocalSetup.');
        }

        // لينك مؤقت عشان storage:link مايعملش public/storage حقيقي جوّه الريبو وقت التست.
        $linkDir = storage_path('framework/testing/links-'.uniqid());
        File::ensureDirectoryExists($linkDir);
        config(['filesystems.links' => [$linkDir.'/storage' => storage_path('app/public')]]);

        Template::factory()->create();
        $this->fakeOllamaWithModels(['qwen3:8b']);

        try {
            // (2026-10-10) المكتبة بتتحدّث لو نسختها قديمة (--if-outdated) بدل ما تتخطّى لمجرد إن فيه قوالب.
            $this->artisan('barq:local-setup')
                ->expectsOutputToContain('Checking the template library')
                ->expectsQuestion('Name / الاسم', 'فؤاد')
                ->expectsQuestion('Email / البريد الإلكتروني', 'owner@example.com')
                ->expectsQuestion('Password, 8+ characters / كلمة المرور (٨ حروف على الأقل)', 'local-pass-123')
                ->expectsQuestion('Confirm password / تأكيد كلمة المرور', 'local-pass-123')
                ->expectsOutputToContain('All required checks passed.')
                ->assertSuccessful();

            $this->assertSame(['owner@example.com'], User::pluck('email')->all());
            $this->assertSame(301, Template::count(), 'the existing template stays, the 300 library templates are added');
            $this->assertTrue(file_exists($linkDir.'/storage'));
        } finally {
            File::deleteDirectory($linkDir);
        }
    }

    public function test_local_setup_can_leave_the_admin_account_and_health_check_to_the_windows_script(): void
    {
        Template::factory()->create();
        config(['filesystems.links' => []]);

        $this->artisan('barq:local-setup', ['--skip-admin' => true])
            ->expectsOutputToContain('Checking the template library')
            ->doesntExpectOutputToContain('Health check')
            ->assertSuccessful();

        $this->assertSame(0, User::count());
    }

    public function test_create_admin_check_reports_whether_an_account_exists(): void
    {
        $this->artisan('barq:create-admin', ['--check' => true])
            ->expectsOutputToContain('No admin account yet.')
            ->assertFailed();

        User::factory()->create();

        $this->artisan('barq:create-admin', ['--check' => true])
            ->expectsOutputToContain('An admin account exists.')
            ->assertSuccessful();
    }

    // ويندوز: setup.ps1 / create-admin.ps1 بيسألوا بـRead-Host وبيبعتوا للأمر ده على stdin (3 سطور
    // base64 لـUTF-8). التست بيشغّل artisan كـprocess حقيقي ببايب على stdin بالظبط زي PowerShell،
    // على ملف SQLite مؤقت (الـ:memory: بتاع التستات مش مرئي لـprocess تاني).
    public function test_create_admin_reads_an_arabic_name_and_password_from_stdin_exactly(): void
    {
        $database = storage_path('framework/testing/admin-stdin-'.uniqid().'.sqlite');
        touch($database);
        $env = ['DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $database, 'APP_ENV' => 'testing'];

        try {
            $migrate = new Process([PHP_BINARY, 'artisan', 'migrate', '--force'], base_path(), $env);
            $migrate->mustRun();

            $password = 'كلمة-سر-عربي-123';
            $payload = implode("\n", array_map('base64_encode', ['فؤاد', ' owner@example.com ', $password]))."\n";

            $create = new Process([PHP_BINARY, 'artisan', 'barq:create-admin', '--stdin'], base_path(), $env, $payload);
            $create->run();
            $this->assertSame(0, $create->getExitCode(), $create->getOutput().$create->getErrorOutput());

            $check = new Process([PHP_BINARY, 'artisan', 'barq:create-admin', '--check'], base_path(), $env);
            $check->run();
            $this->assertSame(0, $check->getExitCode());

            $row = (new PDO('sqlite:'.$database))->query('select name, email, password from users')->fetch(PDO::FETCH_ASSOC);
            $this->assertSame('فؤاد', $row['name']);
            $this->assertSame('owner@example.com', $row['email']);
            $this->assertTrue(Hash::check($password, $row['password']));

            // الاسم فاضي (Enter) = سطر base64 فاضي في الأول → الاسم الحالي بيفضل، ونفس الإيميل = تغيير الباسورد.
            $payload = implode("\n", array_map('base64_encode', ['', 'owner@example.com', 'new-password-456']))."\n";
            $update = new Process([PHP_BINARY, 'artisan', 'barq:create-admin', '--stdin'], base_path(), $env, $payload);
            $update->run();
            $this->assertSame(0, $update->getExitCode(), $update->getOutput().$update->getErrorOutput());
            $row = (new PDO('sqlite:'.$database))->query('select name, password, (select count(*) from users) as total from users')->fetch(PDO::FETCH_ASSOC);
            $this->assertSame('فؤاد', $row['name']);
            $this->assertSame(1, (int) $row['total']);
            $this->assertTrue(Hash::check('new-password-456', $row['password']));

            $garbage = new Process([PHP_BINARY, 'artisan', 'barq:create-admin', '--stdin'], base_path(), $env, "not base64!\n");
            $garbage->run();
            $this->assertSame(1, $garbage->getExitCode());
            $this->assertStringContainsString('Expected three base64-encoded lines', $garbage->getOutput().$garbage->getErrorOutput());
        } finally {
            @unlink($database);
        }
    }
}
