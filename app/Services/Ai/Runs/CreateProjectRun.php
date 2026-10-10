<?php

namespace App\Services\Ai\Runs;

use App\Models\AiRun;
use App\Services\Ai\AiResult;
use App\Services\AiProjectAssistantService;
use App\Services\PhotoPoolService;
use Illuminate\Http\Request;

// "أنشئ مشروع بالذكاء الاصطناعي" (صفحة /ai) — نفس AiChatController::store() بس بالعدّاد.
class CreateProjectRun implements RunHandler
{
    public function __construct(
        private readonly AiProjectAssistantService $assistant,
        private readonly PhotoPoolService $photos,
    ) {}

    /**
     * قواعد الفورم — مشتركة مع AiChatController::store() (المسار من غير جافاسكريبت). الصور:
     * مرفوعة/ملزوقة كملفات (photos) أو روابط صور من كوبي جوجل مابس (photo_urls، السيرفر
     * بينزّلها — PhotoPoolService بيقبل سيرفرات صور جوجل بس). svg مستبعد عمداً زي باقي الرفع.
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:30000'],
            'template_id' => ['nullable', 'integer', 'exists:templates,id'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'font' => ['nullable', 'string'],
            'photos' => ['nullable', 'array', 'max:'.PhotoPoolService::MAX_PHOTOS],
            'photos.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:8192'],
            'photo_urls' => ['nullable', 'array', 'max:'.(PhotoPoolService::MAX_PHOTOS * 3)],
            'photo_urls.*' => ['string', 'max:2048'],
        ];
    }

    public function prepare(Request $request): array
    {
        $data = $request->validate(self::rules());

        $photos = $this->collectPhotos($request, $data);
        $prepared = $this->assistant->prepareCreate(
            $data['message'],
            $data['template_id'] ?? null,
            $data['color'] ?? null,
            $data['font'] ?? null,
            $photos,
        );

        if (! $prepared['ok']) {
            $this->photos->discard($photos);
        }

        return $prepared + ['redirect' => null];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    public function collectPhotos(Request $request, array $data): array
    {
        $uploaded = $this->photos->storeUploads((array) $request->file('photos', []));
        $room = PhotoPoolService::MAX_PHOTOS - count($uploaded);

        $imported = $room > 0 ? $this->photos->importUrls(array_slice((array) ($data['photo_urls'] ?? []), 0, $room * 3)) : [];

        return array_slice(array_merge($uploaded, $imported), 0, PhotoPoolService::MAX_PHOTOS);
    }

    public function complete(AiRun $run, AiResult $result): array
    {
        $outcome = $this->assistant->completeCreate($run->context_json ?? [], $result);

        return [
            'ok' => $outcome['ok'],
            'reply' => $outcome['reply'],
            'redirect' => $outcome['ok'] ? route('ai-chat.show', $outcome['project']) : null,
        ];
    }
}
