<?php

namespace App\Console\Commands;

use App\Services\DataTransferService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

// الخطوة التانية من نقل التشغيل: بيستبدل كل بيانات التشغيل الحالي بمحتوى ملف عمله
// barq:export-data (غالباً من السيرفر). استبدال كامل مش دمج — عشان كده بيسأل قبل أي حاجة.
#[Signature('barq:import-data {path : Backup zip created by barq:export-data} {--force : Do not ask for confirmation}')]
#[Description('Replace all local data with a backup made by barq:export-data')]
class ImportData extends Command
{
    public function handle(DataTransferService $transfer): int
    {
        $path = (string) $this->argument('path');

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm(
            'This DELETES all current templates, projects, sites, chat history and admin accounts here, and replaces them with the backup. Continue?'
        )) {
            $this->line('Cancelled — nothing was changed.');

            return self::FAILURE;
        }

        try {
            $result = $transfer->import($path);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Table', 'Rows'],
            collect($result['tables'])->map(fn ($count, $table) => [$table, $count])->values()->all(),
        );
        $this->line("Uploaded images restored: {$result['files']}");

        if ($result['skipped_files'] !== []) {
            $this->warn('Skipped (not an image): '.implode(', ', $result['skipped_files']));
        }

        $this->info('Import finished. Log in with the same email/password you used on the server.');

        return self::SUCCESS;
    }
}
