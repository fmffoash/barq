<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\PhotoPoolService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * مخزن صور المشروع (2026-10-08) — الصور اللي فؤاد لزقها/رفعها مع رسالة الإنشاء أو بعد كده.
 * يضيف صور، يحط أي صورة منهم في خانة صورة (الغلاف/المعرض...) بدوسة، أو يشيلها من المخزن.
 * الصورة اللي بتتحط لازم تكون من المخزن نفسه بالحرف — مفيش مسار حر بييجي من الفورم.
 */
class SitePhotoController extends Controller
{
    public function __construct(private readonly PhotoPoolService $photos) {}

    public function store(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'photos' => ['nullable', 'array', 'max:'.PhotoPoolService::MAX_PHOTOS],
            'photos.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:8192'],
            'photo_urls' => ['nullable', 'array', 'max:'.(PhotoPoolService::MAX_PHOTOS * 3)],
            'photo_urls.*' => ['string', 'max:2048'],
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

        $site->update(['photo_pool_json' => array_values(array_merge($pool, $kept))]);

        return back()->with('status', $kept === []
            ? 'مفيش صور اتضافت — اتأكد إنها صور (JPG/PNG/WebP) أو روابط صور جوجل مابس.'
            : 'اتضاف '.count($kept).' صورة لمخزن صور المشروع.');
    }

    public function use(Request $request, Project $project): RedirectResponse
    {
        $project->loadMissing('template.slots');
        $site = $project->site()->firstOrFail();
        $pool = $site->photo_pool_json ?? [];
        $imageSlots = $project->template->slots->where('slot_type', 'image');

        $data = $request->validate([
            'photo' => ['required', 'string', Rule::in($pool)],
            'slot_key' => ['required', 'string', Rule::in($imageSlots->pluck('key')->all())],
        ]);

        $content = $site->content_json ?? [];
        $content[$data['slot_key']] = $data['photo'];
        $site->update(['content_json' => $content]);

        $label = $imageSlots->firstWhere('key', $data['slot_key'])?->label() ?? $data['slot_key'];

        return back()->with('status', "تمام، الصورة بقت في \"{$label}\".");
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
}
