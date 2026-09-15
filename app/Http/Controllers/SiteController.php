<?php

namespace App\Http\Controllers;

use App\Models\GeneratedSite;
use Illuminate\Http\Request;
use Illuminate\View\View;

// عرض الموقع الناتج فعلياً للزائر — بيتفعّل بعد ما DetectSite middleware يتأكد إن السب دومين
// بيمثّل موقع موجود ويحمّله جوّه attributes الطلب. القوالب من نوع "ووردبريس" لسه مالهاش رندر
// حقيقي (مرحلة قادمة في الخطة)، فبنعرضلها صفحة "قريباً" بدل ما نحاول نبني بيها صفحة عادية.
class SiteController extends Controller
{
    public function show(Request $request): View
    {
        /** @var GeneratedSite $site */
        $site = $request->attributes->get('site');

        $project = $site->project;
        $template = $project->template;

        if ($template->kind === 'wordpress') {
            return view('site.coming-soon', ['project' => $project]);
        }

        $variant = $project->variant;

        $slotsBySection = $template->slots->groupBy('section_key');

        // ترتيب الأقسام: بنستخدم اللي متحدد في النسخة صراحة، وإلا بنرجع لترتيب أول ظهور
        // للأقسام جوّه خانات القالب نفسه (اللي أصلاً مرتّبة بـ sort_order).
        $sectionOrder = filled($variant?->sections_json)
            ? $variant->sections_json
            : $slotsBySection->keys()->all();

        $sections = collect($sectionOrder)
            ->filter(fn ($key) => $slotsBySection->has($key))
            ->map(function ($key) use ($slotsBySection, $site) {
                $items = $slotsBySection->get($key)
                    ->sortBy('sort_order')
                    ->map(fn ($slot) => [
                        'slot' => $slot,
                        'value' => $site->content($slot->key),
                    ])
                    ->filter(fn (array $item) => filled($item['value']))
                    ->values();

                return ['key' => $key, 'items' => $items];
            })
            // قسم من غير أي قيمة متعبّاة فيه لسه (المشروع لسه بيتظبط) بنسيبه من غير ما يترندر
            // فاضي وسط الصفحة.
            ->filter(fn (array $section) => $section['items']->isNotEmpty())
            ->values();

        $colors = array_merge([
            'primary' => '#f59e0b',
            'background' => '#0b1220',
            'surface' => '#111a2e',
            'text' => '#f1f5f9',
            'muted' => '#94a3b8',
        ], $variant?->colors_json ?? []);

        return view('site.show', [
            'project' => $project,
            'sections' => $sections,
            'colors' => $colors,
        ]);
    }
}
