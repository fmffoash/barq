<?php

namespace App\Console\Commands;

use App\Models\Template;
use App\Services\TemplatePreviewService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

// أداة مطوّر (2026-10-06): بتولّد صورة حقيقية لشكل كل قالب (public/images/template-previews/
// {slug}.jpg + manifest.json بالبصمات) — اللي كروت /templates وصفحة "مشروع جديد" بتعرضها بدل
// صورة الفئة العامة. بترندر كل قالب بنفس SiteRenderer (TemplatePreviewService) لملفات HTML
// مؤقتة جوّه public، بتشغّل `php -S` مؤقت، وبتصوّرها بـPlaywright (scripts/template-thumbnails.cjs).
// محتاجة Node + Playwright — مش جزء من تشغيل التطبيق على جهاز فؤاد، بتتشغّل في جلسة تطوير.
// بتتخطّى أي قالب صورته موجودة وبصمته متغيّرتش (إلا مع --force).
#[Signature('barq:template-thumbnails
    {--only=* : Only these template slugs}
    {--force : Regenerate even when the template did not change}
    {--port=8097 : Port for the temporary local server}')]
#[Description('Developer tool: screenshot every landing template into public/images/template-previews (needs Node + Playwright)')]
class GenerateTemplateThumbnails extends Command
{
    public function handle(TemplatePreviewService $previews): int
    {
        $templates = Template::query()
            ->where('kind', 'landing')
            ->with(['slots', 'variants'])
            ->when($this->option('only'), fn ($query, $slugs) => $query->whereIn('slug', $slugs))
            ->orderBy('id')
            ->get();

        File::ensureDirectoryExists($previews->thumbnailDirectory());
        $manifest = $previews->manifest();

        $todo = $templates->filter(fn (Template $template) => $this->option('force')
            || ($manifest[$previews->safeSlug($template)] ?? null) !== $previews->fingerprint($template)
            || ! is_file($previews->thumbnailPath($template)));

        if ($todo->isEmpty()) {
            $this->info('All template pictures are up to date.');

            return self::SUCCESS;
        }

        $port = (int) $this->option('port');
        $root = "http://127.0.0.1:{$port}";
        $workName = '_template-thumbs-'.Str::lower(Str::random(8));
        $workDir = public_path($workName);
        File::ensureDirectoryExists($workDir);

        // @vite وasset() بيطلّعوا روابط على السيرفر المؤقت، فالصفحات بتحمّل CSS والخطوط والصور منه.
        URL::forceRootUrl($root);

        $jobs = [];
        foreach ($todo as $template) {
            $slug = $previews->safeSlug($template);
            File::put("{$workDir}/{$slug}.html", view('site.show', $previews->render($template))->render());
            $jobs[] = ['slug' => $slug, 'url' => "{$root}/{$workName}/{$slug}.html", 'out' => $previews->thumbnailPath($template)];
        }
        $jobsFile = "{$workDir}/jobs.json";
        File::put($jobsFile, json_encode($jobs, JSON_UNESCAPED_SLASHES));

        $server = new Process([PHP_BINARY, '-S', "127.0.0.1:{$port}", '-t', public_path(), base_path('local/server.php')], base_path());
        // php -S بيكتب سطر لوج لكل طلب — لو الخرج اتلقّط ومحدش قراه، الـpipe بيتملي بعد كام
        // صفحة والسيرفر بيقف (اتكشف بالتجربة عند القالب الـ22).
        $server->disableOutput();
        $server->start();

        try {
            $this->waitForServer("{$root}/{$workName}/jobs.json");
            $this->line('Taking '.count($jobs).' screenshots...');

            $globalModules = trim((string) (new Process(['npm', 'root', '-g']))->mustRun()->getOutput());
            $node = new Process(['node', base_path('scripts/template-thumbnails.cjs'), $jobsFile], base_path(), [
                'NODE_PATH' => implode(PATH_SEPARATOR, array_filter([getenv('NODE_PATH') ?: null, $globalModules])),
            ], null, 3600);
            $node->run(fn ($type, $output) => $this->output->write($output));

            $done = collect(explode("\n", $node->getOutput()))
                ->filter(fn ($line) => str_starts_with($line, 'ok '))
                ->map(fn ($line) => trim(substr($line, 3)))
                ->flip();

            foreach ($todo as $template) {
                if ($done->has($previews->safeSlug($template))) {
                    $manifest[$previews->safeSlug($template)] = $previews->fingerprint($template);
                }
            }
            $previews->writeManifest($manifest);

            $this->info("Saved {$done->count()} of ".count($jobs).' template pictures.');

            return $done->count() === count($jobs) ? self::SUCCESS : self::FAILURE;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } finally {
            $server->stop(2);
            File::deleteDirectory($workDir);
        }
    }

    private function waitForServer(string $url): void
    {
        for ($i = 0; $i < 50; $i++) {
            if (@file_get_contents($url) !== false) {
                return;
            }
            usleep(100_000);
        }

        throw new \RuntimeException("The temporary server did not start ({$url}).");
    }
}
