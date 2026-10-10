<?php

namespace App\Services\Ai;

use App\Services\OllamaService;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * كل اللي صفحة "إعدادات الذكاء الاصطناعي" بتعرضه (2026-10-10): Ollama شغال ولا لأ وإصداره،
 * النماذج المتسطّبة والمحمّلة في الذاكرة دلوقتي، السرعة اللي اتقاست فعلاً على الجهاز ده (AiStats)،
 * ورام/معالج/مساحة الجهاز — عشان فؤاد يعرف يختار نموذج يناسب الجهاز اللي البرنامج شغال عليه
 * (السيرفر المشترك مع طفرة، أو جهازه).
 */
class AiStatus
{
    /**
     * نماذج مقترحة (كلها qwen3 — البرومبتات متظبطة عليها، ومعاها think:false). الرام تقريبي:
     * حجم النموذج + ذاكرة سياق ~6000 توكن.
     */
    public const CATALOG = [
        'qwen3:4b' => ['label' => 'خفيف وسريع', 'ram_gb' => 3.5, 'note' => 'أسرع بحوالي الضعف، بس كتابته العربي أضعف وأقل التزاماً بالتعليمات.'],
        'qwen3:8b' => ['label' => 'متوازن (الافتراضي)', 'ram_gb' => 6.0, 'note' => 'اللي البرنامج متجرّب عليه — كويس في العربي وسرعته مقبولة على المعالج.'],
        'qwen3:14b' => ['label' => 'جودة أعلى', 'ram_gb' => 10.5, 'note' => 'محتوى أدق وأقرب للنشاط، بس أبطأ بحوالي الضعف.'],
        'qwen3:30b' => ['label' => 'الأقوى', 'ram_gb' => 20.0, 'note' => 'أحسن جودة، وسرعته قريبة من 8b (بيشغّل جزء منه بس كل مرة) — محتاج رام كتير.'],
    ];

    public const NUM_CTX_CHOICES = [4096, 6144, 8192, 12288, 16384];

    public function __construct(private readonly OllamaService $ollama) {}

    /**
     * @return array{up: bool, error: ?string, version: ?string, models: list<array<string, mixed>>, loaded: list<string>}
     */
    public function ollama(): array
    {
        $base = $this->ollama->baseUrl();

        try {
            $tags = Http::timeout(4)->connectTimeout(2)->get($base.'/api/tags');
        } catch (Throwable $e) {
            return ['up' => false, 'error' => $e->getMessage(), 'version' => null, 'models' => [], 'loaded' => []];
        }

        if (! $tags->successful()) {
            return ['up' => false, 'error' => 'HTTP '.$tags->status(), 'version' => null, 'models' => [], 'loaded' => []];
        }

        $version = null;
        $loaded = [];
        try {
            $version = Http::timeout(3)->get($base.'/api/version')->json('version');
            $loaded = collect(Http::timeout(3)->get($base.'/api/ps')->json('models') ?? [])
                ->map(fn ($m) => (string) ($m['name'] ?? $m['model'] ?? ''))
                ->filter()
                ->values()
                ->all();
        } catch (Throwable) {
            // الإصدار/المحمّل تفاصيل إضافية بس.
        }

        $models = collect($tags->json('models') ?? [])
            ->filter(fn ($m) => is_array($m) && filled($m['name'] ?? null))
            ->map(fn (array $m) => [
                'name' => (string) $m['name'],
                'size_gb' => round(((int) ($m['size'] ?? 0)) / 1e9, 1),
                'params' => (string) ($m['details']['parameter_size'] ?? ''),
                'quantization' => (string) ($m['details']['quantization_level'] ?? ''),
                'loaded' => in_array($m['name'], $loaded, true),
            ])
            ->sortBy('name')
            ->values()
            ->all();

        return ['up' => true, 'error' => null, 'version' => is_string($version) ? $version : null, 'models' => $models, 'loaded' => $loaded];
    }

    /**
     * @return array{ram_total_gb: ?float, ram_free_gb: ?float, cpu_cores: ?int, disk_free_gb: ?float}
     */
    public function hardware(): array
    {
        $ram = $this->memory();
        $disk = @disk_free_space(storage_path());

        return [
            'ram_total_gb' => $ram['total'],
            'ram_free_gb' => $ram['free'],
            'cpu_cores' => $this->cores(),
            'disk_free_gb' => is_float($disk) ? round($disk / 1e9, 1) : null,
        ];
    }

    /**
     * السرعة اللي اتقاست فعلاً للنموذج ده + وقت إنشاء موقع كامل تقريباً.
     *
     * @return array{measured: bool, eval_tps: ?float, prompt_tps: ?float, load_s: ?float, runs: int, site_minutes: ?float}
     */
    public function speed(string $model): array
    {
        $stats = AiStats::forModel($model);

        if (! isset($stats['eval_tps'])) {
            return ['measured' => false, 'eval_tps' => null, 'prompt_tps' => null, 'load_s' => null, 'runs' => (int) ($stats['n'] ?? 0), 'site_minutes' => null];
        }

        $estimate = AiStats::estimate($model, 'create');
        // برومبت الإنشاء (وصف + 20 خانة + قوالب الفئة) حوالي 9000 حرف.
        $seconds = $estimate['eval_tokens'] / max($estimate['eval_tps'], 0.1)
            + AiStats::promptTokens($model, 9000) / max($estimate['prompt_tps'], 0.1);

        return [
            'measured' => true,
            'eval_tps' => round((float) $stats['eval_tps'], 1),
            'prompt_tps' => isset($stats['prompt_tps']) ? round((float) $stats['prompt_tps'], 1) : null,
            'load_s' => isset($stats['load_ms']) ? round($stats['load_ms'] / 1000, 1) : null,
            'runs' => (int) ($stats['n'] ?? 0),
            'site_minutes' => round($seconds / 60, 1),
        ];
    }

    /**
     * كل نموذج مقترح: يناسب رام الجهاز ولا لأ (good/tight/no/unknown) ومتسطّب ولا لأ.
     *
     * @param  list<array<string, mixed>>  $installed
     * @return list<array<string, mixed>>
     */
    public function catalog(?float $ramTotalGb, array $installed): array
    {
        $names = array_column($installed, 'name');

        return collect(self::CATALOG)->map(function (array $info, string $name) use ($ramTotalGb, $names) {
            $fit = match (true) {
                $ramTotalGb === null => 'unknown',
                $ramTotalGb >= $info['ram_gb'] + 3 => 'good',
                $ramTotalGb >= $info['ram_gb'] + 1.5 => 'tight',
                default => 'no',
            };

            return $info + ['name' => $name, 'fit' => $fit, 'installed' => in_array($name, $names, true)];
        })->values()->all();
    }

    /**
     * @return array{total: ?float, free: ?float}
     */
    private function memory(): array
    {
        $info = @file_get_contents('/proc/meminfo');
        if (! is_string($info)) {
            return ['total' => null, 'free' => null];
        }

        $read = fn (string $field) => preg_match('/^'.$field.':\s+(\d+)\s+kB/m', $info, $m) ? round(((int) $m[1]) / 1024 / 1024, 1) : null;

        return ['total' => $read('MemTotal'), 'free' => $read('MemAvailable')];
    }

    private function cores(): ?int
    {
        $windows = getenv('NUMBER_OF_PROCESSORS');
        if (is_string($windows) && ctype_digit($windows)) {
            return (int) $windows;
        }

        $info = @file_get_contents('/proc/cpuinfo');

        return is_string($info) ? max(1, preg_match_all('/^processor\s*:/m', $info)) : null;
    }
}
