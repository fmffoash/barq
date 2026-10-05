<?php

namespace App\Console\Commands;

use App\Models\Template;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

// تجهيز برق على جهاز محلي (2026-10-05) — كل اللي بعد `composer install` و`.env` في أمر واحد
// بيتنده من سكريبتات local/ (ويندوز وماك/لينكس بنفس المنطق بالظبط، بدل ما يتكرر مرتين). آمن
// يتشغّل أكتر من مرة: كل خطوة بتتخطّى نفسها لو اتعملت قبل كده. ممنوع على السيرفر (production)
// عشان محدش يشغّله هناك بالغلط.
#[Signature('barq:local-setup')]
#[Description('Prepare this machine to run the app locally (database, storage link, template library, admin account)')]
class LocalSetup extends Command
{
    public function handle(): int
    {
        if ($this->laravel->environment('production')) {
            $this->error('barq:local-setup is for local machines only (APP_ENV=production here).');

            return self::FAILURE;
        }

        $this->ensureSqliteDatabaseFile();

        if ($this->call('migrate', ['--force' => true]) !== self::SUCCESS) {
            $this->error('Database migration failed — see the error above.');

            return self::FAILURE;
        }

        // نفس اللينكات اللي storage:link بيعملها (config/filesystems.php) — public/storage عادةً.
        if (collect(array_keys(config('filesystems.links', [])))->contains(fn ($link) => ! file_exists($link))) {
            $this->call('storage:link');
        }

        if (Template::query()->exists()) {
            $this->line('Template library already present — skipped.');
        } else {
            $this->line('Creating the template library (takes a few seconds)...');
            $this->call('barq:seed-template-library');
        }

        if (! User::query()->exists()) {
            $this->newLine();
            $this->line('<options=bold>Create your admin account</> — you will be asked for: name, email, password (8+ characters), and the password again.');
            $this->line('The password is typed hidden and is never saved in any file.');

            for ($attempt = 1; $attempt <= 3 && ! User::query()->exists(); $attempt++) {
                $this->call('barq:create-admin');
            }

            if (! User::query()->exists()) {
                $this->warn('No admin account yet — run `php artisan barq:create-admin` when ready.');
            }
        }

        return $this->call('barq:doctor');
    }

    private function ensureSqliteDatabaseFile(): void
    {
        $connection = config('database.connections.'.config('database.default'));

        if (($connection['driver'] ?? null) !== 'sqlite') {
            return;
        }

        $path = (string) ($connection['database'] ?? '');
        if ($path === '' || $path === ':memory:' || file_exists($path)) {
            return;
        }

        File::ensureDirectoryExists(dirname($path));
        touch($path);
        $this->line("Created SQLite database: {$path}");
    }
}
