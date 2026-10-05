<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

// نقل كل بيانات المشروع (الجداول + الصور المرفوعة) من تشغيل لتشغيل تاني في ملف zip واحد —
// اتعمل 2026-10-05 لنقل برق من السيرفر (MySQL) لجهاز فؤاد المحلي (SQLite). الصيغة JSON
// مستقلة عن نوع قاعدة البيانات، فالتصدير من MySQL والاستيراد في SQLite (أو العكس) شغّالين
// بنفس الملف من غير أي تحويل SQL يدوي (mysqldump مبيتقريش صح في SQLite — طريقة الهروب
// `\'`/`\n` مختلفة وبتبوّظ محتوى JSON).
class DataTransferService
{
    public const FORMAT = 'barq-data-backup';

    public const VERSION = 1;

    // بترتيب الاعتمادية (الأب قبل الابن) — الاستيراد بيدخّل بالترتيب ده وبيمسح بعكسه. جداول
    // التشغيل المؤقتة (sessions/cache/jobs/migrations) مش جزء من البيانات خالص.
    public const TABLES = [
        'users',
        'templates',
        'template_variants',
        'template_slots',
        'projects',
        'generated_sites',
        'unsupported_requests',
        'ai_chat_messages',
    ];

    // الامتدادات الوحيدة المقبولة من مجلد files/ وقت الاستيراد — كل اللي برق بيرفعه صور بس،
    // ومجلد storage/app/public بيتقدّم مباشرة على /storage، فملف .php فيه ممكن يتنفّذ.
    private const ALLOWED_FILE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'avif', 'ico'];

    /**
     * @return array{tables: array<string, int>, files: int}
     */
    public function export(string $zipPath): array
    {
        File::ensureDirectoryExists(dirname($zipPath));

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Could not create backup file: {$zipPath}");
        }

        $counts = [];
        foreach (self::TABLES as $table) {
            $rows = DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();

            if ($table === 'users') {
                // توكن "افتكرني" بيتساب ورا — الدخول على الجهاز الجديد بالباسورد العادي.
                $rows = array_map(fn ($row) => ['remember_token' => null] + $row, $rows);
            }

            $zip->addFromString(
                "tables/{$table}.json",
                json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            );
            $counts[$table] = count($rows);
        }

        $disk = Storage::disk('public');
        $files = array_values(array_filter($disk->allFiles(), fn ($path) => basename($path) !== '.gitignore'));
        foreach ($files as $file) {
            $zip->addFile($disk->path($file), 'files/'.$file);
        }

        $zip->addFromString('manifest.json', json_encode([
            'format' => self::FORMAT,
            'version' => self::VERSION,
            'created_at' => now()->toIso8601String(),
            'source_db_driver' => DB::connection()->getDriverName(),
            'tables' => $counts,
            'files' => count($files),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        if (! $zip->close()) {
            throw new RuntimeException("Could not write backup file: {$zipPath}");
        }

        return ['tables' => $counts, 'files' => count($files)];
    }

    /**
     * بيمسح كل بيانات الجداول في TABLES ويحط مكانها اللي في النسخة — مش دمج. الصور بتتضاف
     * فوق الموجود (مفيش مسح لصور محلية قديمة).
     *
     * @return array{tables: array<string, int>, files: int, skipped_files: list<string>}
     */
    public function import(string $zipPath): array
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException("Could not open backup file: {$zipPath}");
        }

        try {
            $manifest = json_decode((string) $zip->getFromName('manifest.json'), true);
            if (! is_array($manifest) || ($manifest['format'] ?? null) !== self::FORMAT) {
                throw new RuntimeException('This file is not a data backup created by barq:export-data.');
            }
            if ((int) ($manifest['version'] ?? 0) > self::VERSION) {
                throw new RuntimeException('This backup was made by a newer version of the code — update first (git pull), then import.');
            }

            // كل التحقق بيحصل قبل أي مسح — ملف ناقص أو مسار مريب يوقف العملية والداتا المحلية سليمة.
            $data = [];
            foreach (self::TABLES as $table) {
                if (! Schema::hasTable($table)) {
                    throw new RuntimeException("Table [{$table}] does not exist yet — run `php artisan migrate` first.");
                }

                $json = $zip->getFromName("tables/{$table}.json");
                $rows = $json === false ? null : json_decode($json, true);
                if (! is_array($rows) || ! array_is_list($rows)) {
                    throw new RuntimeException("Backup is missing or has a broken tables/{$table}.json.");
                }

                $data[$table] = $rows;
            }

            $files = [];
            $skipped = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = (string) $zip->getNameIndex($i);
                if (! str_starts_with($name, 'files/') || str_ends_with($name, '/')) {
                    continue;
                }

                $relative = substr($name, strlen('files/'));
                if (! self::isSafeRelativePath($relative)) {
                    throw new RuntimeException("Unsafe file path inside backup: {$name}");
                }
                if (! in_array(strtolower(pathinfo($relative, PATHINFO_EXTENSION)), self::ALLOWED_FILE_EXTENSIONS, true)) {
                    $skipped[] = $relative;

                    continue;
                }

                $files[$relative] = $i;
            }

            $this->replaceTables($data);

            $disk = Storage::disk('public');
            foreach ($files as $relative => $index) {
                $contents = $zip->getFromIndex($index);
                if ($contents === false) {
                    throw new RuntimeException("Could not read {$relative} from the backup.");
                }
                $disk->put($relative, $contents);
            }
        } finally {
            $zip->close();
        }

        return [
            'tables' => array_map('count', $data),
            'files' => count($files),
            'skipped_files' => $skipped,
        ];
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $data
     */
    private function replaceTables(array $data): void
    {
        // لازم برّه الـtransaction — SQLite بيتجاهل PRAGMA foreign_keys جوّه transaction مفتوحة.
        Schema::disableForeignKeyConstraints();

        try {
            DB::transaction(function () use ($data) {
                foreach (array_reverse(self::TABLES) as $table) {
                    DB::table($table)->delete();
                }

                // أي جلسة دخول قديمة ممكن تشاور على user_id مبقاش موجود أو بقى حد تاني.
                if (Schema::hasTable('sessions')) {
                    DB::table('sessions')->delete();
                }

                foreach (self::TABLES as $table) {
                    // أعمدة النسخة اللي مش موجودة محلياً بتتشال (نسخة أحدث بعمود زيادة مثلاً).
                    $columns = array_flip(Schema::getColumnListing($table));
                    $rows = array_map(fn (array $row) => array_intersect_key($row, $columns), $data[$table]);

                    // SQLite القديم حدّه 999 متغيّر في الاستعلام الواحد.
                    $chunkSize = max(1, intdiv(900, max(1, count($columns))));
                    foreach (array_chunk($rows, $chunkSize) as $chunk) {
                        DB::table($table)->insert($chunk);
                    }
                }
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    private static function isSafeRelativePath(string $path): bool
    {
        if ($path === '' || str_starts_with($path, '/') || str_contains($path, '\\')
            || str_contains($path, ':') || str_contains($path, "\0")) {
            return false;
        }

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }

        return true;
    }
}
