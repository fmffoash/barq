<?php

namespace App\Services\Ai;

// نتيجة نداء واحد للذكاء الاصطناعي (2026-10-06). قبل كده أي فشل (Ollama مقفول، النموذج مش
// متسطّب، المهلة خلصت، رد مش JSON) كان بيرجع null وفؤاد يشوف نفس الرسالة "النموذج مش متاح"
// من غير ما يعرف يعمل إيه. دلوقتي كل فشل ليه نوع ورسالة عربي فيها الحل.
final class AiResult
{
    public const DOWN = 'down';

    public const MODEL_MISSING = 'model_missing';

    public const TIMEOUT = 'timeout';

    public const BAD_JSON = 'bad_json';

    public const TOO_LONG = 'too_long';

    public const HTTP_ERROR = 'http_error';

    /**
     * @param  array<mixed, mixed>|null  $data
     * @param  array<string, int|float|string|null>  $metrics
     */
    private function __construct(
        public readonly bool $ok,
        public readonly ?array $data,
        public readonly string $text,
        public readonly ?string $error,
        public readonly string $detail,
        public readonly array $metrics,
    ) {}

    /**
     * @param  array<mixed, mixed>  $data
     * @param  array<string, int|float|string|null>  $metrics
     */
    public static function success(array $data, string $text, array $metrics = []): self
    {
        return new self(true, $data, $text, null, '', $metrics);
    }

    /**
     * @param  array<string, int|float|string|null>  $metrics
     */
    public static function failure(string $error, string $detail = '', string $text = '', array $metrics = []): self
    {
        return new self(false, null, $text, $error, $detail, $metrics);
    }

    // رسالة لفؤاد بالسبب والحل — بتتعرض في الشات/الصفحة بدل "النموذج مش متاح" العامة.
    public function message(?string $model = null): string
    {
        $model = $model ?: (string) config('services.ollama.model');

        return match ($this->error) {
            null => '',
            self::DOWN => 'برنامج الذكاء الاصطناعي (Ollama) مش شغال دلوقتي. شغّله من قايمة ابدأ (Ollama) واستنى ثواني وجرّب تاني.',
            self::MODEL_MISSING => "نموذج الذكاء الاصطناعي \"{$model}\" مش متسطّب على الجهاز. نزّله من صفحة إعدادات الذكاء الاصطناعي أو اختار نموذج تاني.",
            self::TIMEOUT => 'الذكاء الاصطناعي خد وقت أطول من المسموح ووقفناه. جرّب تاني (المرة التانية بتبقى أسرع لأن النموذج بيكون اتحمّل)، أو اختار نموذج أخف من صفحة الإعدادات.',
            self::TOO_LONG => 'الرد طلع أطول من الحد ومكملش. جرّب تختصر طلبك أو تقسّمه على كذا رسالة.',
            self::BAD_JSON => 'الذكاء الاصطناعي رجّع رد مش مفهوم المرة دي. جرّب تاني — لو اتكررت، جرّب نموذج تاني من صفحة الإعدادات.',
            default => 'حصلت مشكلة في الذكاء الاصطناعي'.($this->detail !== '' ? ': '.$this->detail : '').'. جرّب تاني بعد شوية.',
        };
    }
}
