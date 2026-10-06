<?php

namespace App\Console\Commands;

use App\Models\Template;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

// فحص صحة التشغيل المحلي (2026-10-05) — أول حاجة تتعمل لو أي حاجة "مش شغالة" على الجهاز:
// بيقول بالظبط إيه الناقص وإيه الأمر اللي يصلّحه، بدل التخمين. FAIL = برق مش هيشتغل صح،
// WARN = ميزة جانبية بس (الذكاء الاصطناعي/الصور المرفوعة/المكتبة) متأثرة.
// الرسايل بالإنجليزي عمداً: طرفية ويندوز مبتعرضش العربي (RTL) صح.
#[Signature('barq:doctor')]
#[Description('Check that this machine has everything needed to run the app (PHP, DB, assets, admin, Ollama)')]
class Doctor extends Command
{
    private bool $failed = false;

    public function handle(): int
    {
        $this->newLine();
        $this->line('<options=bold>Health check</>');

        $this->checkPhp();
        $this->checkAppKey();

        if ($this->checkDatabase()) {
            $this->checkAdminAndLibrary();
        }

        $this->checkFrontendAssets();
        $this->checkStorage();
        $this->checkOllama();

        $this->newLine();
        if ($this->failed) {
            $this->error('Some required checks failed — run the "fix" command next to each FAIL, then run '.(windows_os() ? 'local\\doctor.bat' : '`php artisan barq:doctor`').' again.');

            return self::FAILURE;
        }

        $this->info('All required checks passed.');

        return self::SUCCESS;
    }

    private function checkPhp(): void
    {
        $this->report(PHP_VERSION_ID >= 80300 ? 'ok' : 'fail', 'PHP '.PHP_VERSION, 'Install PHP 8.3 or newer');

        $driver = config('database.connections.'.config('database.default').'.driver');
        $required = ['mbstring', 'openssl', 'pdo', 'fileinfo', 'zip', 'dom', 'tokenizer', 'ctype'];
        $required[] = $driver === 'mysql' ? 'pdo_mysql' : 'pdo_sqlite';

        $missing = array_values(array_filter($required, fn ($ext) => ! extension_loaded($ext)));
        $this->report(
            $missing === [] ? 'ok' : 'fail',
            $missing === [] ? 'PHP extensions' : 'Missing PHP extensions: '.implode(', ', $missing),
            windows_os()
                ? 'Run local\\setup.bat again (it sets up its own PHP with everything enabled)'
                : 'Enable them in php.ini (e.g. extension='.($missing[0] ?? 'zip').') — Laravel Herd has them all enabled',
        );

        // Guzzle بيعرف يشتغل من غير curl، بس curl هو اللي متجرّب فعلياً مع نداءات Ollama الطويلة.
        if (! extension_loaded('curl')) {
            $this->report('warn', 'PHP extension curl is missing (AI requests may be unreliable)', 'Enable extension=curl in php.ini');
        }
    }

    private function checkAppKey(): void
    {
        $this->report(config('app.key') ? 'ok' : 'fail', 'APP_KEY', $this->artisan('key:generate'));
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            $this->report('fail', 'Database connection: '.$e->getMessage(), windows_os() ? 'Run local\\setup.bat again' : 'php artisan barq:local-setup');

            return false;
        }

        $migrator = app('migrator');
        if (! $migrator->repositoryExists()) {
            $this->report('fail', 'Database is empty (no tables yet)', $this->artisan('migrate'));

            return false;
        }

        $files = array_keys($migrator->getMigrationFiles([database_path('migrations')]));
        $pending = array_diff($files, $migrator->getRepository()->getRan());
        $this->report(
            $pending === [] ? 'ok' : 'fail',
            $pending === [] ? 'Database ('.DB::connection()->getDriverName().', up to date)' : count($pending).' pending migration(s)',
            $this->artisan('migrate'),
        );

        return $pending === [];
    }

    private function checkAdminAndLibrary(): void
    {
        $this->report(User::query()->exists() ? 'ok' : 'fail', 'Admin account', windows_os() ? 'local\\create-admin.bat' : 'php artisan barq:create-admin');

        $templates = Template::query()->count();
        $this->report(
            $templates > 0 ? 'ok' : 'warn',
            "Templates in library: {$templates}",
            $this->artisan('barq:seed-template-library'),
        );
    }

    private function checkFrontendAssets(): void
    {
        $this->report(
            is_file(public_path('build/manifest.json')) ? 'ok' : 'fail',
            'Built CSS/JS (public/build)',
            windows_os() ? 'Run local\\update.bat (it rebuilds the interface)' : 'npm ci && npm run build',
        );

        // public/hot بيفضل موجود لو `npm run dev` اتقفل غلط — ووجوده بيخلّي @vite يدوّر على سيرفر
        // Vite مش شغّال، فالصفحات بتطلع من غير أي تنسيق خالص.
        if (is_file(public_path('hot'))) {
            $this->report('warn', 'public/hot exists (pages load CSS from `npm run dev`)', 'If `npm run dev` is not running, delete the file public/hot');
        }
    }

    private function checkStorage(): void
    {
        $writable = is_writable(storage_path('logs')) && is_writable(storage_path('framework'))
            && is_writable(base_path('bootstrap/cache'));
        $this->report($writable ? 'ok' : 'fail', 'storage/ and bootstrap/cache are writable', 'Give your user write access to storage/ and bootstrap/cache/');

        $missingLinks = array_filter(array_keys(config('filesystems.links', [])), fn ($link) => ! file_exists($link));
        $this->report(
            $missingLinks === [] ? 'ok' : 'warn',
            'Uploaded images link (public/storage)',
            $this->artisan('storage:link'),
        );
    }

    private function checkOllama(): void
    {
        $baseUrl = rtrim((string) config('services.ollama.base_url'), '/');
        $model = (string) config('services.ollama.model');

        try {
            $response = Http::timeout(3)->get($baseUrl.'/api/tags');
            $installed = $response->successful() ? collect($response->json('models', []))->pluck('name')->all() : null;
        } catch (Throwable) {
            $installed = null;
        }

        if ($installed === null) {
            $this->report('warn', "Ollama is not running at {$baseUrl} (AI features won't work)", 'Start the Ollama app (or run: ollama serve)');

            return;
        }

        // Ollama بيعرض الموديل من غير tag صريح باسم "name:latest".
        $wanted = str_contains($model, ':') ? $model : $model.':latest';
        $this->report(
            in_array($wanted, $installed, true) ? 'ok' : 'warn',
            "Ollama model {$model}",
            "ollama pull {$model}",
        );
    }

    // على ويندوز PHP الخاص بالتطبيق (.runtime) مش في الـPATH العام، فـ`php artisan` في شباك عادي
    // بيقول "php is not recognized" — local\\artisan.bat بيحطه في الـPATH الأول.
    private function artisan(string $arguments): string
    {
        return (windows_os() ? 'local\\artisan.bat ' : 'php artisan ').$arguments;
    }

    private function report(string $status, string $label, string $fix): void
    {
        [$tag, $style] = match ($status) {
            'ok' => ['OK  ', 'info'],
            'warn' => ['WARN', 'comment'],
            default => ['FAIL', 'error'],
        };

        $this->line("  <{$style}>[{$tag}]</{$style}> {$label}");

        if ($status !== 'ok') {
            $this->line("         fix: {$fix}");
        }

        if ($status === 'fail') {
            $this->failed = true;
        }
    }
}
