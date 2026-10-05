<?php

namespace Tests\Feature;

use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
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
            ->expectsOutputToContain('fix: php artisan barq:create-admin')
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
        // لينك مؤقت عشان storage:link مايعملش public/storage حقيقي جوّه الريبو وقت التست.
        $linkDir = storage_path('framework/testing/links-'.uniqid());
        File::ensureDirectoryExists($linkDir);
        config(['filesystems.links' => [$linkDir.'/storage' => storage_path('app/public')]]);

        Template::factory()->create();
        $this->fakeOllamaWithModels(['qwen3:8b']);

        try {
            $this->artisan('barq:local-setup')
                ->expectsOutputToContain('Template library already present')
                ->expectsQuestion('Name / الاسم', 'فؤاد')
                ->expectsQuestion('Email / البريد الإلكتروني', 'owner@example.com')
                ->expectsQuestion('Password, 8+ characters / كلمة المرور (٨ حروف على الأقل)', 'local-pass-123')
                ->expectsQuestion('Confirm password / تأكيد كلمة المرور', 'local-pass-123')
                ->expectsOutputToContain('All required checks passed.')
                ->assertSuccessful();

            $this->assertSame(['owner@example.com'], User::pluck('email')->all());
            $this->assertSame(1, Template::count());
            $this->assertTrue(file_exists($linkDir.'/storage'));
        } finally {
            File::deleteDirectory($linkDir);
        }
    }
}
