<?php

namespace App\Console\Commands;

use App\Services\DataTransferService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

// بيصدّر كل بيانات برق (القوالب/المشاريع/المواقع/المحادثات/حساب الأدمن + الصور المرفوعة) في
// ملف zip واحد — الخطوة الأولى لنقل التشغيل من السيرفر للجهاز المحلي (docs/LOCAL-SETUP.md).
// الرسايل بالإنجليزي عمداً: الأمر ده بيتشغّل من طرفية (SSH أو ويندوز) مبتعرضش العربي صح.
#[Signature('barq:export-data {--output= : Where to write the backup zip (default: storage/app/backups/)}')]
#[Description('Export all data (tables + uploaded images) to a portable zip backup')]
class ExportData extends Command
{
    public function handle(DataTransferService $transfer): int
    {
        $output = $this->option('output')
            ?: storage_path('app/backups/barq-data-'.now()->format('Y-m-d_His').'.zip');

        try {
            $result = $transfer->export($output);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Table', 'Rows'],
            collect($result['tables'])->map(fn ($count, $table) => [$table, $count])->values()->all(),
        );
        $this->line("Uploaded images: {$result['files']}");
        $this->info("Backup written to: {$output}");

        return self::SUCCESS;
    }
}
