{{--
    "بينتو" — شبكة خلايا مختلفة الأحجام (خلية كبيرة + خلايا أصغر حواليها) لكل قسم، زي
    تصميمات الـ bento-grid الرايجة. كل خلية كارت دائري الحواف بخلفية متبادلة (surface/primary
    خافت) عشان الشبكة تبقى مقروءة بصرياً من غير ما تحتاج صور.
--}}
@include('site.partials.nav')

@forelse ($sections as $section)
    @php
        $textItems = $section['items']->whereIn('slot.slot_type', ['text', 'textarea']);
        $listItems = $section['items']->where('slot.slot_type', 'list');
        $imageItems = $section['items']->where('slot.slot_type', 'image');
        $linkItems = $section['items']->where('slot.slot_type', 'link');

        // لو القسم فيه أكتر من خانة "text" (زي "تواصل معنا": عنوان + ملاحظة قصيرة)، أول
        // خانة بس بتاخد شكل العنوان الكبير، والباقي بيترندر كنص مساند أصغر بدل عنوانين ضخمين
        // فوق بعض.
        $heading = $textItems->firstWhere('slot.slot_type', 'text');
        $supportingItems = $textItems->reject(fn ($item) => $heading && $item['slot']->key === $heading['slot']->key);
    @endphp

    <section id="{{ $section['key'] }}" class="px-4 py-10 sm:px-8">
        <div class="mx-auto max-w-6xl">
            @if ($section['kind'] === 'hero')
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="flex flex-col justify-center gap-4 rounded-[2rem] p-8 text-right shadow-md sm:col-span-2 sm:p-12" style="background-color: var(--site-surface);">
                        @if ($heading)
                            <h1 data-slot="{{ $heading['slot']->key }}" class="text-3xl font-extrabold sm:text-5xl">{!! $heading['value'] !!}</h1>
                        @endif
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="text-base leading-loose" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                        @endforeach
                        @foreach ($linkItems as $item)
                            <a href="{{ $item['value'] }}" target="_blank" rel="noopener" class="mt-1 inline-block w-fit rounded-full px-7 py-3 text-sm font-bold shadow-md transition hover:opacity-90" style="background-color: var(--site-primary); color: var(--site-background);">{{ $item['slot']->label() }}</a>
                        @endforeach
                    </div>
                    @if ($imageItems->isNotEmpty())
                        <img src="{{ $imageItems->first()['value'] }}" alt="{{ $imageItems->first()['slot']->label() }}" class="h-full min-h-[12rem] w-full rounded-[2rem] object-cover shadow-md" loading="lazy">
                    @else
                        <div class="flex min-h-[12rem] items-center justify-center rounded-[2rem] p-8 text-center text-sm shadow-md" style="background-color: color-mix(in srgb, var(--site-primary) 20%, transparent); color: var(--site-primary);">{{ $project->name }}</div>
                    @endif
                </div>
            @elseif ($section['kind'] === 'gallery')
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    @if ($textItems->isNotEmpty())
                        <div class="col-span-2 flex flex-col justify-center gap-2 rounded-[1.75rem] p-6 shadow-sm sm:row-span-2" style="background-color: color-mix(in srgb, var(--site-primary) 16%, transparent);">
                            @if ($heading)
                                <h2 data-slot="{{ $heading['slot']->key }}" class="text-2xl font-bold">{!! $heading['value'] !!}</h2>
                            @endif
                            @foreach ($supportingItems as $item)
                                <p data-slot="{{ $item['slot']->key }}" class="text-sm" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                            @endforeach
                        </div>
                    @endif
                    @foreach ($imageItems as $item)
                        <img src="{{ $item['value'] }}" alt="{{ $item['slot']->label() }}" class="aspect-square w-full rounded-[1.75rem] object-cover shadow-sm" loading="lazy">
                    @endforeach
                </div>
            @elseif ($section['kind'] === 'list')
                @if ($textItems->isNotEmpty())
                    <div class="mb-6 text-center">
                        @if ($heading)
                            <h2 data-slot="{{ $heading['slot']->key }}" class="text-3xl font-bold">{!! $heading['value'] !!}</h2>
                        @endif
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="mt-2" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                        @endforeach
                    </div>
                @endif
                @foreach ($listItems as $item)
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        @foreach ((array) $item['value'] as $i => $listItem)
                            <div
                                data-slot="{{ $item['slot']->key }}"
                                @class([
                                    'flex items-center justify-center rounded-[1.75rem] p-6 text-center text-base font-semibold shadow-sm',
                                    'col-span-2 py-10 text-xl' => $i === 0,
                                ])
                                style="background-color: {{ $i === 0 ? 'var(--site-primary)' : 'var(--site-surface)' }}; color: {{ $i === 0 ? 'var(--site-background)' : 'var(--site-text)' }};"
                            >
                                {{ $listItem }}
                            </div>
                        @endforeach
                    </div>
                @endforeach
            @elseif ($section['kind'] === 'cta')
                <div class="rounded-[2rem] p-10 text-center shadow-lg sm:p-14" style="background-color: var(--site-primary);">
                    @if ($heading)
                        <h2 data-slot="{{ $heading['slot']->key }}" class="text-3xl font-extrabold" style="color: var(--site-background);">{!! $heading['value'] !!}</h2>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" style="color: color-mix(in srgb, var(--site-background) 80%, transparent);">{!! $item['value'] !!}</p>
                    @endforeach
                    @foreach ($linkItems as $item)
                        <a href="{{ $item['value'] }}" target="_blank" rel="noopener" class="mt-4 inline-block rounded-full px-8 py-3.5 text-base font-bold shadow-md transition hover:opacity-90" style="background-color: var(--site-background); color: var(--site-primary);">{{ $item['slot']->label() }}</a>
                    @endforeach
                </div>
            @else
                <div class="mx-auto flex max-w-2xl flex-col gap-4 rounded-[1.75rem] p-8 text-center shadow-sm" style="background-color: var(--site-surface);">
                    @foreach ($section['items'] as $item)
                        @if ($heading && $item['slot']->key === $heading['slot']->key)<h2 data-slot="{{ $item['slot']->key }}" class="text-2xl font-bold">{!! $item['value'] !!}</h2>
                        @elseif ($item['slot']->slot_type === 'link')<a href="{{ $item['value'] }}" target="_blank" rel="noopener" class="mx-auto inline-block rounded-full px-7 py-3 text-sm font-bold shadow-sm" style="background-color: var(--site-primary); color: var(--site-background);">{{ $item['slot']->label() }}</a>
                        @else<p data-slot="{{ $item['slot']->key }}" style="color: var(--site-muted);">{!! $item['value'] !!}</p>@endif
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@empty
    <div class="flex min-h-screen flex-col items-center justify-center gap-3 px-6 text-center">
        <h1 class="text-2xl font-bold">{{ $project->name }}</h1>
        <p style="color: var(--site-muted);">الموقع لسه بيتجهّز، تعال زورنا تاني قريب.</p>
    </div>
@endforelse

@if ($sections->isNotEmpty())
    @include('site.partials.footer')
@endif
