{{--
    بارشيال عام واحد بيرندر أي قسم أياً كان اسمه (hero/services/contact/...) — كل خانة جوّه
    القسم بتترندر حسب نوعها (slot_type)، فمفيش داعي لبارشيال منفصل لكل قسم ممكن صاحب القالب
    يخترعه.
--}}
<section id="{{ $section['key'] }}" class="border-b border-white/5 px-6 py-16 last:border-b-0 sm:px-10">
    <div class="mx-auto flex max-w-4xl flex-col gap-8">
        @foreach ($section['items'] as $item)
            @php $slot = $item['slot']; $value = $item['value']; @endphp

            @switch($slot->slot_type)
                @case('image')
                    <img
                        src="{{ $value }}"
                        alt="{{ $slot->label() }}"
                        class="mx-auto max-h-[420px] w-full rounded-2xl object-cover shadow-lg"
                        loading="lazy"
                    >
                    @break

                @case('link')
                    <div class="text-center">
                        <a
                            href="{{ $value }}"
                            target="_blank"
                            rel="noopener"
                            class="inline-block rounded-full px-8 py-3 text-base font-semibold shadow-md transition hover:opacity-90"
                            style="background-color: var(--site-primary); color: var(--site-background);"
                        >
                            {{ $slot->label() }}
                        </a>
                    </div>
                    @break

                @case('list')
                    <ul class="grid gap-4 sm:grid-cols-2">
                        @foreach ((array) $value as $listItem)
                            <li
                                class="rounded-xl border px-5 py-4 text-base leading-relaxed"
                                style="background-color: var(--site-surface); border-color: color-mix(in srgb, var(--site-text) 10%, transparent);"
                            >
                                {{ $listItem }}
                            </li>
                        @endforeach
                    </ul>
                    @break

                @case('textarea')
                    <p class="whitespace-pre-line text-lg leading-loose" style="color: var(--site-muted);">
                        {{ $value }}
                    </p>
                    @break

                @default
                    <h2 class="text-3xl font-bold sm:text-4xl">{{ $value }}</h2>
            @endswitch
        @endforeach
    </div>
</section>
