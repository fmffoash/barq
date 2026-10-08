{{--
    بيانات المكان + صور المشروع (2026-10-08) — في صفحة المشروع وصفحة الشات.
    - بيانات المكان: اللي اتعرف من كوبي جوجل مابس بالحرف (PlaceParser) — للرجوع ليها بس.
    - صور المشروع: كل صورة اتلزقت/اترفعت (photo_pool_json)، وأي واحدة تتحط في خانة صورة بدوسة
      (SitePhotoController). الصور الجديدة بتتضاف بنفس طريقة صفحة الإنشاء (زرار/سحب/Ctrl+V).
--}}
@php
    $place = $project->place_json ?? [];
    $site = $project->site;
    $pool = $site?->photo_pool_json ?? [];
    $imageSlots = $project->template?->slots->where('slot_type', 'image')->values() ?? collect();
    $usedIn = collect($imageSlots)->mapWithKeys(fn ($slot) => [$slot->key => $site?->content($slot->key)])->filter();
    $showPhotos = $site && $project->template?->kind === 'landing' && $imageSlots->isNotEmpty();
@endphp

@if ($place || $showPhotos)
    <div class="mt-8 grid gap-6 {{ $place && $showPhotos ? 'lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]' : '' }}">
        @if ($place)
            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
                <h2 class="mb-4 text-lg font-semibold text-slate-100">📍 بيانات المكان</h2>
                <dl class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-2.5 text-sm">
                    @if (! empty($place['name']))
                        <dt class="text-slate-500">الاسم</dt><dd class="text-slate-200">{{ $place['name'] }}</dd>
                    @endif
                    @if (! empty($place['kind']))
                        <dt class="text-slate-500">النشاط</dt><dd class="text-slate-200">{{ $place['kind'] }}</dd>
                    @endif
                    @if (! empty($place['rating']))
                        <dt class="text-slate-500">التقييم</dt>
                        <dd class="text-slate-200"><span class="text-amber-400">★</span> {{ $place['rating'] }} @if (! empty($place['reviews']))<span class="text-slate-500">({{ $place['reviews'] }} تقييم)</span>@endif</dd>
                    @endif
                    @if (! empty($place['phones']))
                        <dt class="text-slate-500">التليفون</dt>
                        <dd class="text-slate-200" dir="ltr" style="text-align: right">{{ implode(' / ', $place['phones']) }}</dd>
                    @endif
                    @if (! empty($place['address']))
                        <dt class="text-slate-500">العنوان</dt><dd class="text-slate-200">{{ $place['address'] }}</dd>
                    @endif
                    @if (! empty($place['hours']))
                        <dt class="text-slate-500">المواعيد</dt>
                        <dd class="space-y-0.5 text-slate-200">
                            @foreach ($place['hours'] as $line)
                                <div>{{ $line }}</div>
                            @endforeach
                        </dd>
                    @endif
                    @if (! empty($place['website']) && preg_match('#^https?://#i', $place['website']))
                        <dt class="text-slate-500">الموقع</dt>
                        <dd><a href="{{ $place['website'] }}" target="_blank" rel="noopener noreferrer" dir="ltr" class="text-amber-400 hover:underline">{{ $place['website'] }}</a></dd>
                    @endif
                    @if (! empty($place['maps_url']) && preg_match('#^https?://#i', $place['maps_url']))
                        <dt class="text-slate-500">الخريطة</dt>
                        <dd><a href="{{ $place['maps_url'] }}" target="_blank" rel="noopener noreferrer" class="text-amber-400 hover:underline">افتح على جوجل مابس ←</a></dd>
                    @endif
                    @if (! empty($place['plus_code']))
                        <dt class="text-slate-500">Plus Code</dt><dd class="text-slate-200" dir="ltr" style="text-align: right">{{ $place['plus_code'] }}</dd>
                    @endif
                </dl>
            </div>
        @endif

        @if ($showPhotos)
            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
                <h2 class="mb-1 text-lg font-semibold text-slate-100">🖼️ صور المشروع</h2>
                <p class="mb-4 text-xs text-slate-500">كل الصور اللي لزقتها أو رفعتها للمشروع ده — اختار خانة جنب أي صورة ودوس "حط".</p>

                @if ($pool)
                    <div class="mb-5 grid grid-cols-[repeat(auto-fill,minmax(150px,1fr))] gap-3">
                        @foreach ($pool as $photo)
                            @php $slotsUsing = $usedIn->filter(fn ($value) => $value === $photo)->keys(); @endphp
                            <div class="overflow-hidden rounded-xl border {{ $slotsUsing->isNotEmpty() ? 'border-amber-400/50' : 'border-slate-800' }} bg-slate-950">
                                <div class="relative aspect-[4/3]">
                                    <img src="{{ $photo }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                    @if ($slotsUsing->isNotEmpty())
                                        <span class="absolute right-1.5 top-1.5 rounded bg-slate-950/85 px-1.5 py-0.5 text-[10px] text-amber-300">
                                            {{ $slotsUsing->map(fn ($key) => $imageSlots->firstWhere('key', $key)?->label() ?? $key)->implode('، ') }}
                                        </span>
                                    @endif
                                    <form method="POST" action="{{ route('projects.site.photos.destroy', $project) }}" class="absolute left-1.5 top-1.5" onsubmit="return confirm('تشيل الصورة دي من مخزن صور المشروع؟');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="photo" value="{{ $photo }}">
                                        <button type="submit" class="h-6 w-6 rounded-full bg-slate-950/85 text-sm leading-6 text-slate-300 transition hover:bg-red-600 hover:text-white" aria-label="شيل الصورة">×</button>
                                    </form>
                                </div>
                                <form method="POST" action="{{ route('projects.site.photos.use', $project) }}" class="flex gap-1.5 p-2">
                                    @csrf
                                    <input type="hidden" name="photo" value="{{ $photo }}">
                                    <select name="slot_key" class="min-w-0 flex-1 rounded-md border border-slate-700 bg-slate-900 px-1.5 py-1 text-xs text-slate-200 outline-none focus:border-amber-400" aria-label="الخانة">
                                        @foreach ($imageSlots as $slot)
                                            <option value="{{ $slot->key }}">{{ $slot->label() }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="shrink-0 rounded-md bg-amber-400 px-2.5 py-1 text-xs font-semibold text-slate-950 transition hover:bg-amber-300">حط</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('projects.site.photos.store', $project) }}" enctype="multipart/form-data">
                    @csrf
                    {{-- tabindex عشان دوسة على المربع تخليه هو اللي بيستقبل Ctrl+V. --}}
                    <div tabindex="0" class="rounded-lg border border-dashed border-slate-800 p-3 outline-none transition focus:border-amber-400/60 data-[drag=true]:border-amber-400 data-[drag=true]:bg-amber-400/5" data-photo-tray data-mode="pool">
                        <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple class="hidden" data-photo-input>
                        <div class="mb-3 flex flex-wrap items-center gap-2 empty:hidden" data-photo-list></div>
                        <div data-photo-urls></div>
                        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                            <button type="button" class="rounded-lg border border-slate-700 px-3 py-1.5 text-slate-300 transition hover:border-amber-400 hover:text-amber-400" data-photo-pick>📷 اختار صور</button>
                            <span>أو الزق صور/كوبي جوجل مابس هنا (اضغط على المربع ده وبعدين Ctrl+V)، أو اسحبها.</span>
                            <button type="submit" class="ms-auto rounded-lg bg-amber-400 px-3 py-1.5 font-semibold text-slate-950 transition hover:bg-amber-300">أضف للمخزن</button>
                        </div>
                        <p class="mt-2 text-xs text-amber-300/80" data-photo-hint></p>
                    </div>
                </form>
            </div>
        @endif
    </div>
@endif
