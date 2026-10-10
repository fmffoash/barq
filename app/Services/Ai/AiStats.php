<?php

namespace App\Services\Ai;

use App\Models\Setting;

// سرعة جهاز فؤاد الحقيقية مع كل نموذج (2026-10-06) — عشان العدّاد يقول "باقي حوالي 12 ثانية"
// بأرقام من جهازه هو مش تخمين. كل نداء بيرجّع من Ollama توقيتاته (تحميل النموذج/قراية الطلب/
// كتابة الرد بالـnanoseconds وعدد التوكنز)، وبنحتفظ بمتوسط متحرك لكل نموذج ولكل نوع طلب.
class AiStats
{
    private const KEY = 'ai.stats';

    private const ALPHA = 0.4;

    // قيم مبدئية متحفّظة لجهاز من غير كارت شاشة لحد ما أول نداء حقيقي يتقاس.
    private const DEFAULTS = ['prompt_tps' => 40.0, 'eval_tps' => 6.0, 'load_ms' => 20000.0];

    // طول الرد المتوقع (بالتوكنز) لكل نوع طلب لحد ما يتقاس فعلاً.
    private const DEFAULT_EVAL_TOKENS = [
        'create' => 900, 'content' => 700, 'follow_up' => 300, 'rewrite' => 220,
        'extract' => 800, 'classify' => 200, 'benchmark' => 120,
    ];

    // عدد الحروف في التوكن الواحد تقريباً (عربي + شوية إنجليزي) لحد ما يتقاس من ردود حقيقية —
    // العدّاد محتاجه عشان يقدّر وقت "قراية الطلب" قبل ما Ollama يقول عدد التوكنز الفعلي.
    private const DEFAULT_CHARS_PER_TOKEN = 2.8;

    /**
     * @param  array<string, mixed>  $metrics  الحقول اللي Ollama بيرجّعها في آخر رد
     * @param  int  $promptChars  طول البرومبت بالحروف (0 = مش معروف)
     */
    public static function record(string $model, string $task, array $metrics, int $promptChars = 0): void
    {
        $all = (array) Setting::get(self::KEY, []);
        $stats = $all[$model] ?? ['tasks' => [], 'n' => 0];

        $promptCount = (int) ($metrics['prompt_eval_count'] ?? 0);
        $promptNs = (int) ($metrics['prompt_eval_duration'] ?? 0);
        $evalCount = (int) ($metrics['eval_count'] ?? 0);
        $evalNs = (int) ($metrics['eval_duration'] ?? 0);
        $loadNs = (int) ($metrics['load_duration'] ?? 0);

        // قراية طلب قصير جداً (أو متكاش من الكاش) بتدي سرعة مش حقيقية، فبنتجاهلها.
        if ($promptCount >= 50 && $promptNs > 0) {
            $stats['prompt_tps'] = self::ema($stats['prompt_tps'] ?? null, $promptCount / ($promptNs / 1e9));
        }
        if ($evalCount >= 10 && $evalNs > 0) {
            $stats['eval_tps'] = self::ema($stats['eval_tps'] ?? null, $evalCount / ($evalNs / 1e9));
        }
        // تحميل أقل من ثانية = النموذج كان محمّل أصلاً، مش وقت تحميل حقيقي.
        if ($loadNs > 1e9) {
            $stats['load_ms'] = self::ema($stats['load_ms'] ?? null, $loadNs / 1e6);
        }
        if ($evalCount > 0) {
            $stats['tasks'][$task]['eval_count'] = self::ema($stats['tasks'][$task]['eval_count'] ?? null, $evalCount);
        }
        // Ollama بيعيد استخدام أول الطلب لو اتكرر (الكاش) فبيعدّ توكنز أقل من الحقيقي — نسبة
        // برّه المعقول (أو طلب قصير) معناها كاش، فبنتجاهلها.
        if ($promptChars >= 500 && $promptCount >= 150) {
            $ratio = $promptChars / $promptCount;
            if ($ratio >= 1.2 && $ratio <= 6.0) {
                $stats['chars_per_token'] = self::ema($stats['chars_per_token'] ?? null, $ratio);
            }
        }

        $stats['n'] = (int) ($stats['n'] ?? 0) + 1;
        $stats['updated_at'] = now()->toIso8601String();
        $all[$model] = $stats;

        Setting::put(self::KEY, $all);
    }

    /**
     * أرقام التقدير اللي العدّاد بيبدأ بيها (وبيصحّحها وهو شغال من السرعة الفعلية).
     *
     * @return array{prompt_tps: float, eval_tps: float, load_ms: float, eval_tokens: int, measured: bool}
     */
    public static function estimate(string $model, string $task): array
    {
        $stats = ((array) Setting::get(self::KEY, []))[$model] ?? [];

        return [
            'prompt_tps' => round((float) ($stats['prompt_tps'] ?? self::DEFAULTS['prompt_tps']), 2),
            'eval_tps' => round((float) ($stats['eval_tps'] ?? self::DEFAULTS['eval_tps']), 2),
            'load_ms' => round((float) ($stats['load_ms'] ?? self::DEFAULTS['load_ms'])),
            'eval_tokens' => (int) round((float) ($stats['tasks'][$task]['eval_count'] ?? self::DEFAULT_EVAL_TOKENS[$task] ?? 400)),
            'measured' => isset($stats['eval_tps']),
        ];
    }

    // تقدير عدد توكنز برومبت طوله $chars حرف بالنموذج ده.
    public static function promptTokens(string $model, int $chars): int
    {
        $stats = ((array) Setting::get(self::KEY, []))[$model] ?? [];
        $ratio = (float) ($stats['chars_per_token'] ?? self::DEFAULT_CHARS_PER_TOKEN);

        return (int) ceil($chars / max($ratio, 1.0));
    }

    /**
     * @return array<string, mixed>
     */
    public static function forModel(string $model): array
    {
        return ((array) Setting::get(self::KEY, []))[$model] ?? [];
    }

    private static function ema(?float $old, float $new): float
    {
        return $old === null ? $new : (1 - self::ALPHA) * $old + self::ALPHA * $new;
    }
}
