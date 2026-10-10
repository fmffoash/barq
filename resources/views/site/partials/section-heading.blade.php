{{--
    رأس قسم مشترك (عائلة التصميمات المدوّرة: modern/gallery/bento): سطر صغير باسم القسم
    (eyebrow، نسخة عرض من غير data-slot) + العنوان h2 (خانة قابلة للتعديل) + نجوم تقييم جوجل لو
    موجودة + النص المساند. كل الإعدادات في مصفوفة $head (عشان مفيش متغير صفحة يتسرّب):
      'heading' / 'supporting' => من الـsection (عنصر خانة / Collection عناصر)
      'label'   => اسم القسم ($section['label'])، null = من غير eyebrow
      'rating'  => $section['rating']
      'align'   => 'center' (الافتراضي) أو 'start'
      'h2Class' => مقاس العنوان لو مختلف
--}}
@php
    $shCenter = ($head['align'] ?? 'center') === 'center';
    $shHeading = $head['heading'] ?? null;
    $shSupporting = $head['supporting'] ?? collect();
@endphp
@if ($shHeading || $shSupporting->isNotEmpty() || ! empty($head['rating']))
    <div @class(['flex flex-col gap-3', 'mx-auto max-w-2xl items-center text-center' => $shCenter, 'items-start' => ! $shCenter])>
        @if (filled($head['label'] ?? null))
            <span class="inline-flex items-center gap-2 text-sm font-bold" style="color: var(--site-primary);">
                <span class="h-0.5 w-6 rounded-full" style="background-color: var(--site-primary);" aria-hidden="true"></span>
                {{ $head['label'] }}
            </span>
        @endif
        @if ($shHeading)
            <h2 data-slot="{{ $shHeading['slot']->key }}" class="{{ $head['h2Class'] ?? 'text-[clamp(1.85rem,3.2vw,2.6rem)] font-extrabold leading-[1.3]' }}">{!! $shHeading['value'] !!}</h2>
        @endif
        @if (! empty($head['rating']))
            @include('site.partials.rating-badge', ['badge' => ['rating' => $head['rating']]])
        @endif
        @foreach ($shSupporting as $shItem)
            <p data-slot="{{ $shItem['slot']->key }}" class="text-lg leading-loose" style="color: var(--site-muted);">{!! $shItem['value'] !!}</p>
        @endforeach
    </div>
@endif
