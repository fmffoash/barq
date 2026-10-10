<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\Ai\AiStatus;
use App\Services\Ai\Runs\BenchmarkRun;
use App\Services\OllamaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * إعدادات الذكاء الاصطناعي (2026-10-10): حالة Ollama، النموذج المستخدم (من المتسطّب بس)، حجم
 * السياق، السرعة الحقيقية على الجهاز، واقتراح نماذج تناسب رامه. تنزيل نموذج جديد بيتعمل من
 * الطرفية (ollama pull) — مش من الصفحة: تنزيل جيجات على سيرفر مشترك قرار يتاخد بإيد مش بدوسة.
 */
class AiSettingsController extends Controller
{
    public function show(AiStatus $status, OllamaService $ollama): View
    {
        $model = $ollama->model();
        $hardware = $status->hardware();
        $server = $status->ollama();

        return view('ai-settings.show', [
            'model' => $model,
            'defaultModel' => (string) config('services.ollama.model'),
            'numCtx' => $ollama->numCtx(),
            'defaultNumCtx' => (int) config('services.ollama.num_ctx', 8192),
            'numCtxChoices' => AiStatus::NUM_CTX_CHOICES,
            'timeout' => $ollama->timeout(),
            'server' => $server,
            'hardware' => $hardware,
            'speed' => $status->speed($model),
            'catalog' => $status->catalog($hardware['ram_total_gb'], $server['models']),
            'modelInstalled' => collect($server['models'])->contains(fn ($m) => in_array($m['name'], [$model, $model.':latest'], true)),
            'isLoaded' => in_array($model, $server['loaded'], true) || in_array($model.':latest', $server['loaded'], true),
        ]);
    }

    // اختبار السرعة من غير جافاسكريبت (العادي: BenchmarkRun على العدّاد).
    public function benchmark(Request $request, BenchmarkRun $benchmark, OllamaService $ollama): RedirectResponse
    {
        $prepared = $benchmark->prepare($request);
        $ai = $prepared['ai'];
        $result = $ollama->preflight() ?? $ollama->run($ai['prompt'], $ai['schema'], $ai['task']);

        $speed = app(AiStatus::class)->speed($ollama->model());
        $message = ! $result->ok
            ? $result->message($ollama->model())
            : ($speed['measured'] ? '✓ السرعة على الجهاز ده: '.$speed['eval_tps'].' توكن/ثانية.' : '✓ النموذج رد — جرّب تاني لو السرعة متقاستش.');

        return redirect()->route('ai-settings.show')->with('status', $message);
    }

    public function update(Request $request, AiStatus $status): RedirectResponse
    {
        $installed = array_column($status->ollama()['models'], 'name');

        $data = $request->validate([
            // فاضي = النموذج الافتراضي من .env. غير كده لازم يكون متسطّب فعلاً.
            'model' => ['nullable', 'string', Rule::in($installed)],
            'num_ctx' => ['nullable', 'integer', Rule::in(AiStatus::NUM_CTX_CHOICES)],
        ], [
            'model.in' => 'النموذج ده مش متسطّب على الجهاز — نزّله الأول (ollama pull) أو اختار من القايمة.',
        ]);

        filled($data['model'] ?? null) ? Setting::put('ai.model', $data['model']) : Setting::forget('ai.model');
        filled($data['num_ctx'] ?? null) ? Setting::put('ai.num_ctx', (int) $data['num_ctx']) : Setting::forget('ai.num_ctx');

        return redirect()->route('ai-settings.show')->with('status', 'اتحفظت إعدادات الذكاء الاصطناعي.');
    }
}
