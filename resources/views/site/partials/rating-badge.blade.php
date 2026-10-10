{{--
    نجوم تقييم جوجل جنب عنوان قسم الآراء — بتظهر بس لو العنوان نفسه فيه تقييم حقيقي
    ("تقييمنا 4.7 ★ على جوجل من 437 تقييم"، AiProjectAssistantService::finalizeContent).
    العنوان نفسه بيترندر في مكانه زي ما هو (خانة قابلة للتعديل)؛ ده زخرفة بس.
    @include('site.partials.rating-badge', ['badge' => ['rating' => $section['rating'], 'class' => '...', 'style' => '...']])
--}}
@php $rbRating = (float) $badge['rating']; @endphp
<span class="inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-sm font-bold {{ $badge['class'] ?? '' }}" style="{{ $badge['style'] ?? 'background-color: color-mix(in srgb, var(--site-text) 7%, transparent);' }}" role="img" aria-label="تقييم {{ $rbRating }} من 5 على جوجل">
    @include('site.partials.icon', ['name' => 'google', 'class' => 'h-4 w-4'])
    <span class="flex items-center gap-0.5">
        @for ($rbS = 1; $rbS <= 5; $rbS++)
            @php $rbFill = max(0, min(1, $rbRating - ($rbS - 1))); @endphp
            <span class="relative inline-block h-4 w-4">
                <span class="absolute inset-0" style="color: color-mix(in srgb, currentColor 25%, transparent);">@include('site.partials.icon', ['name' => 'star', 'class' => 'h-4 w-4'])</span>
                <span class="absolute inset-y-0 start-0 overflow-hidden" style="width: {{ round($rbFill * 100) }}%; color: #f5b301;">@include('site.partials.icon', ['name' => 'star', 'class' => 'h-4 w-4'])</span>
            </span>
        @endfor
    </span>
    <span>{{ rtrim(rtrim(number_format($rbRating, 1, '.', ''), '0'), '.') }}</span>
</span>
