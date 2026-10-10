<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\PhotoPoolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * مخزن صور المشروع (2026-10-08) — الصور اللي فؤاد لزقها/رفعها مع رسالة الإنشاء أو بعد كده.
 * يضيف صور، يحط أي صورة منهم في خانة صورة (الغلاف/المعرض...) بدوسة، أو يشيلها من المخزن.
 * الصورة اللي بتتحط لازم تكون من المخزن نفسه بالحرف — مفيش مسار حر بييجي من الفورم.
 *
 * (2026-10-10) المحرر المباشر بيستخدم نفس المسارات بـJSON (Accept: application/json): رفع صورة
 * وحطها في الخانة على طول (slot_key)، واختيار صورة من المخزن أو من صور القالب الأصلية، أو رجوع
 * لصورة مرفوعة قبل كده ("تراجع").
 */
class SitePhotoController extends Controller
{
    public function __construct(private readonly PhotoPoolService $photos) {}

    public function store(Request $request, Project $project): RedirectResponse|JsonResponse
    {
        $project->loadMissing('template.slots');
        $imageSlots = $project->template->slots->where('slot_type', 'image');

        $data = $request->validate([
            'photos' => ['nullable', 'array', 'max:'.PhotoPoolService::MAX_PHOTOS],
            'photos.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:8192'],
            'photo_urls' => ['nullable', 'array', 'max:'.(PhotoPoolService::MAX_PHOTOS * 3)],
            'photo_urls.*' => ['string', 'max:2048'],
            'slot_key' => ['nullable', 'string', Rule::in($imageSlots->pluck('key')->all())],
        ]);

        $site = $project->site()->firstOrFail();
        $pool = $site->photo_pool_json ?? [];
        $room = max(0, 40 - count($pool));

        $added = array_merge(
            $this->photos->storeUploads((array) $request->file('photos', [])),
            $this->photos->importUrls((array) ($data['photo_urls'] ?? [])),
        );
        $kept = array_slice($added, 0, $room);
        $this->photos->discard(array_slice($added, $room));

        $updates = ['photo_pool_json' => array_values(array_merge($pool, $kept))];

        // من المحرر المباشر: أول صورة اترفعت بتتحط في الخانة اللي فؤاد كان واقف عليها.
        if (filled($data['slot_key'] ?? null) && $kept !== []) {
            $content = $site->content_json ?? [];
            $content[$data['slot_key']] = $kept[0];
            $updates['content_json'] = $content;
        }

        $site->update($updates);

        $message = $kept === []
            ? 'مفيش صور اتضافت — اتأكد إنها صور (JPG/PNG/WebP) أو روابط صور جوجل مابس.'
            : 'اتضاف '.count($kept).' صورة لمخزن صور المشروع.';

        if ($request->expectsJson()) {
            return response()->json(['ok' => $kept !== [], 'message' => $message, 'added' => $kept], $kept === [] ? 422 : 200);
        }

        return back()->with('status', $message);
    }

    public function use(Request $request, Project $project): RedirectResponse|JsonResponse
    {
        $project->loadMissing('template.slots');
        $site = $project->site()->firstOrFail();
        $imageSlots = $project->template->slots->where('slot_type', 'image');

        $data = $request->validate([
            'photo' => ['required', 'string', 'max:300'],
            'slot_key' => ['required', 'string', Rule::in($imageSlots->pluck('key')->all())],
        ]);

        if (! $this->isUsable($data['photo'], $site->photo_pool_json ?? [], $imageSlots->pluck('default_value')->filter()->all())) {
            throw ValidationException::withMessages(['photo' => 'الصورة دي مش من صور المشروع.']);
        }

        $content = $site->content_json ?? [];
        $content[$data['slot_key']] = $data['photo'];
        $site->update(['content_json' => $content]);

        $label = $imageSlots->firstWhere('key', $data['slot_key'])?->label() ?? $data['slot_key'];
        $message = "تمام، الصورة بقت في \"{$label}\".";

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }

        return back()->with('status', $message);
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $site = $project->site()->firstOrFail();
        $pool = $site->photo_pool_json ?? [];

        $data = $request->validate(['photo' => ['required', 'string', Rule::in($pool)]]);

        $site->update(['photo_pool_json' => array_values(array_diff($pool, [$data['photo']])) ?: null]);

        // الملف نفسه بيتمسح بس لو مش مستخدم في أي خانة دلوقتي.
        if (! in_array($data['photo'], array_values(array_filter((array) ($site->content_json ?? []), 'is_string')), true)) {
            $this->photos->discard([$data['photo']]);
        }

        return back()->with('status', 'اتشالت الصورة من مخزن صور المشروع.');
    }

    /**
     * صورة ينفع تتحط في خانة: من مخزن المشروع، أو من صور القالب الأصلية، أو صورة مرفوعة قبل
     * كده على البرنامج نفسه (رجوع بـ"تراجع" لصورة اتشالت من الخانة). مفيش أي مسار تاني.
     *
     * @param  list<string>  $pool
     * @param  list<string>  $templateDefaults
     */
    private function isUsable(string $photo, array $pool, array $templateDefaults): bool
    {
        if (in_array($photo, $pool, true) || in_array($photo, $templateDefaults, true)) {
            return true;
        }

        return (bool) preg_match('~^/storage/site-images/[A-Za-z0-9_-]+\.(?:jpe?g|png|webp|gif)$~i', $photo)
            && Storage::disk('public')->exists(substr($photo, strlen('/storage/')));
    }
}
