<?php

namespace App\Services;

use App\Models\GeneratedSite;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

// بيبني نسخة تصدير كاملة كملفات ثابتة (HTML/CSS/الخطوط/الصور) من موقع ناتج معيّن — صفر
// اعتماد على Laravel وقت التشغيل، فينفع يتحط على أي استضافة ملفات ثابتة عادية. مقصود بس على
// القوالب من نوع "landing" (صفحة هبوط) — الـ "wordpress" مالهاش رندر حقيقي أصلاً لسه (Phase 5).
class SiteExportService
{
    public function __construct(private readonly SiteRenderer $renderer)
    {
    }

    // بيرجع مسار ملف zip جاهز على القرص (مسؤولية الاستدعاء إنه يبعته للمتصفح ويمسحه بعدين).
    public function export(GeneratedSite $site): string
    {
        $project = $site->project;
        $template = $project->template;

        if ($template->kind !== 'landing') {
            throw new RuntimeException('تصدير الملفات الثابتة متاح للمواقع من نوع "صفحة هبوط" بس.');
        }

        $exportDir = storage_path('app/exports/'.Str::uuid());
        $assetsDir = $exportDir.'/assets';

        File::ensureDirectoryExists($assetsDir.'/images');

        $data = $this->renderer->render($site);
        $data['sections'] = $this->rewriteImagePaths($data['sections'], $assetsDir.'/images');

        file_put_contents($exportDir.'/index.html', view('site.export', $data)->render());

        $this->copyStyles($assetsDir);

        $zipName = (Str::slug($project->name) ?: 'site').'-'.now()->format('Ymd-His').'.zip';
        $zipPath = storage_path('app/exports/'.$zipName);
        $this->zipDirectory($exportDir, $zipPath);

        File::deleteDirectory($exportDir);

        $site->forceFill(['exported_at' => now()])->save();

        return $zipPath;
    }

    // بيحوّل كل خانة صورة من مسار `/storage/{path}` (اللي بيتخزّن وقت رفع الصورة، شوف
    // GeneratedSiteController::update) لمسار نسبي جوّه حزمة التصدير، وبينسخ الملف الحقيقي معاها.
    // أي خانة قيمتها مش ملف حقيقي موجود (مش صورة، أو المسار مش موجود على القرص لأي سبب) بتتسيب
    // زي ما هي من غير كسر التصدير كله.
    private function rewriteImagePaths(Collection $sections, string $imagesDir): Collection
    {
        return $sections->map(function (array $section) use ($imagesDir) {
            $section['items'] = $section['items']->map(function (array $item) use ($imagesDir) {
                if ($item['slot']->slot_type !== 'image' || ! is_string($item['value'])) {
                    return $item;
                }

                $relativePath = ltrim(Str::after($item['value'], '/storage/'), '/');
                $disk = Storage::disk('public');

                if (! $disk->exists($relativePath)) {
                    return $item;
                }

                $filename = basename($relativePath);
                File::copy($disk->path($relativePath), $imagesDir.'/'.$filename);
                $item['value'] = 'assets/images/'.$filename;

                return $item;
            });

            return $section;
        });
    }

    // بينسخ CSS المولّد بالـ build + كل الخطوط اللي بتعتمد عليها. قايمة الخطوط دي بتيجي من
    // manifest.json بتاع Vite نفسه (المصدر الوحيد اللي فعلاً بيعرف إيه المطلوب بدقة، مش
    // تخمين/glob على مجلد الـ assets العام). مسارات الخطوط في الـ CSS الأصلي مطلقة
    // (/build/assets/...) وده مش محمول — بنحوّلها لمسارات نسبية (نفس مجلد الـ CSS) عشان
    // الحزمة الناتجة تفضل شغّالة من أي مكان تتحط فيه.
    private function copyStyles(string $assetsDir): void
    {
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $cssEntry = $manifest['resources/css/app.css'];

        $css = file_get_contents(public_path('build/'.$cssEntry['file']));
        $css = str_replace('/build/assets/', '', $css);
        file_put_contents($assetsDir.'/app.css', $css);

        foreach ($cssEntry['assets'] ?? [] as $asset) {
            File::copy(public_path('build/'.$asset), $assetsDir.'/'.basename($asset));
        }
    }

    private function zipDirectory(string $sourceDir, string $zipPath): void
    {
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            $relativePath = substr($file->getPathname(), strlen($sourceDir) + 1);
            $zip->addFile($file->getPathname(), $relativePath);
        }

        $zip->close();
    }
}
