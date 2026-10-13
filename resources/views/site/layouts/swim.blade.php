{{-- تصميم Claude Design (2026-10-13) — 04-fitness/05-swimming.html. اتحوّل من HTML جاهز (convert98): الشكل ثابت والمحتوى من خانات
     الموقع عن طريق App\Support\DesignedPage، والرسمة 3D جوه [data-scene]. ماتعدّلش يدوي من غير ما تراجع الأصل. --}}
@php $D = new \App\Support\DesignedPage($sections, $contactAction ?? null); @endphp
<style>@verbatim


*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;background:var(--site-background);color:var(--site-text);font-family:inherit;font-size:18px;line-height:1.8;overflow-x:hidden}
h1,h2,h3{font-family:var(--hf);font-weight:var(--hw);line-height:1.25;margin:0}
h1{font-size:var(--h1);line-height:1.15}
h2{font-size:var(--h2)}
p{margin:0}
a{color:inherit}
.wrap{max-width:1240px;margin:0 auto;padding:0 48px}
.sec{padding:110px 0}
.center{text-align:center}
.muted{color:var(--site-muted)}
.sub{font-size:21px;color:var(--site-muted);margin-top:22px;max-width:540px}
.actions{display:flex;gap:16px;margin-top:38px;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;justify-content:center;min-height:52px;padding:0 30px;text-decoration:none;font-weight:600;font-size:18px;white-space:nowrap}
.btn.big{min-height:62px;padding:0 44px;font-size:21px}
.kicker{display:inline-flex;align-items:center;gap:12px;font-size:16px;margin-bottom:22px}
.ph{display:flex;align-items:center;justify-content:center;text-align:center;color:var(--site-muted);font-size:15px;font-weight:600;line-height:1.6;width:100%;height:100%;overflow:hidden}
.card{background:var(--site-surface);border:var(--bw) solid var(--bc);border-radius:var(--rad);box-shadow:var(--shd)}
.frame{overflow:hidden;border-radius:var(--rad)}
.scene{position:relative;display:flex;align-items:center;justify-content:center;flex:none}
[data-scene]{position:relative;border-radius:inherit}
.brand{font-family:var(--hf);font-weight:var(--hw);font-size:26px;text-decoration:none;color:var(--site-primary);white-space:nowrap}
.links{display:flex;gap:28px;flex-wrap:wrap}
.links a{text-decoration:none;color:var(--site-muted);font-weight:500}
.links a:hover{color:var(--site-primary)}
.svc{list-style:none;margin:0;padding:0}
.rv figure{margin:0}
.rv blockquote{margin:0}
@keyframes spin{to{rotate:360deg}}
@keyframes bob{0%,100%{translate:0 0}50%{translate:0 -14px}}
@keyframes float{0%,100%{translate:0 0}50%{translate:0 -16px}}
@keyframes pulse{0%{scale:.92;opacity:1}100%{scale:1.18;opacity:0}}
@keyframes wobble{0%,100%{rotate:-6deg}50%{rotate:6deg}}
@keyframes morph{0%,100%{border-radius:58% 42% 50% 50%/45% 55% 45% 55%}50%{border-radius:42% 58% 45% 55%/55% 45% 55% 45%}}
@keyframes glow{0%,100%{opacity:.7;scale:1}50%{opacity:1;scale:1.08}}
@keyframes blink{50%{opacity:0}}
@keyframes bounce{0%,100%{translate:0 0}40%{translate:0 -22px}60%{translate:0 -18px}}
@keyframes fade{0%,100%{opacity:.4}50%{opacity:1}}
@keyframes slide{from{background-position:0 0}to{background-position:80px 0}}

body{--hf:var(--site-font-heading);--hw:700;--on:var(--site-surface);--rad:22px;--bw:1px;--bc:color-mix(in srgb,var(--site-text) 10%,transparent);--shd:0 12px 30px rgba(0,0,0,.06);--h1:62px;--h2:46px;}

.btn{border-radius:999px;background:var(--site-primary);color:var(--on);transition:translate .3s,box-shadow .3s}
.btn:hover{translate:0 -2px;box-shadow:0 10px 24px rgba(0,0,0,.12)}
.btn.alt{background:transparent;color:var(--site-primary);box-shadow:inset 0 0 0 2px var(--site-primary)}
.hl{color:var(--site-primary)}
.kicker{color:var(--site-primary);background:color-mix(in srgb,var(--site-primary) 10%,var(--site-surface));padding:6px 16px;border-radius:999px;font-weight:600}
.ph{background:radial-gradient(color-mix(in srgb,var(--site-primary) 20%,transparent) 2px,transparent 2.5px) 0 0/22px 22px,color-mix(in srgb,var(--site-primary) 5%,var(--site-surface))}
.scene{border-radius:50%;background:var(--site-surface);box-shadow:0 18px 40px rgba(0,0,0,.08)}
.scene::before{content:"";position:absolute;inset:-12px;border-radius:50%;border:2px solid color-mix(in srgb,var(--site-primary) 30%,transparent);animation:pulse 3.5s ease-out infinite}

.shead{margin-bottom:56px}.shead.split{display:flex;align-items:end;justify-content:space-between;gap:24px;flex-wrap:wrap}.shead.split p{max-width:380px}.shead.center p{margin-top:12px}
.ghead{margin-bottom:52px}
.rhead{margin-bottom:56px}
.n1{position:sticky;top:0;z-index:20;background:color-mix(in srgb,var(--site-background) 92%,transparent);backdrop-filter:blur(10px);border-bottom:1px solid color-mix(in srgb,var(--site-text) 10%,transparent)}
.n1 .wrap{display:flex;align-items:center;gap:36px;height:84px}.n1 .links{margin-inline-start:auto}
.hv10{padding:40px 0 110px}.hv10 .panel{position:relative;background:color-mix(in srgb,var(--site-primary) 11%,var(--site-surface));border-radius:calc(var(--rad) * 1.5);padding:80px 64px 64px;display:grid;grid-template-columns:1.1fr .9fr;gap:48px;align-items:center;margin-top:100px}
.hv10 .pic{height:460px}.hv10 .sp{position:absolute;top:-110px;inset-inline-end:90px}
.a1{background:var(--site-surface)}.a1 .wrap{display:grid;grid-template-columns:340px 1fr;gap:80px;align-items:start}
.a1 h2::after{content:"";display:block;width:64px;height:4px;background:var(--site-primary);margin-top:22px}.a1 p{font-size:23px;line-height:2}
.s6 .svc{display:grid;grid-template-columns:repeat(var(--cols),minmax(0,1fr));gap:28px}
.s6 li{background:var(--site-surface);border-radius:var(--rad);border:var(--bw) solid var(--bc);padding:38px 30px;text-align:center;transition:translate .3s,box-shadow .3s}
.s6 li:hover{translate:0 -8px;box-shadow:0 20px 40px rgba(0,0,0,.08)}.s6 li::before{content:"";display:block;width:84px;height:84px;margin:0 auto 22px;border-radius:50%;background:radial-gradient(circle at 35% 35%,var(--site-surface) 0 12%,var(--site-primary) 13% 100%)}
.s6 li:nth-child(even)::before{background:radial-gradient(circle at 35% 35%,var(--site-surface) 0 12%,color-mix(in srgb,var(--site-primary) 55%,var(--site-background)) 13% 100%)}.s6 h3{font-size:23px;margin-bottom:6px}.s6 p{color:var(--site-muted)}
.g7 .grid{display:grid;grid-template-columns:2fr 1fr;grid-template-rows:260px 260px;gap:20px}.g7 .ph{border-radius:var(--rad)}.g7 .ph:first-child{grid-row:span 2}
.r8{background:var(--site-surface)}.r8 .grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px}
.r8 figure{padding:32px;border-radius:var(--rad);border:1px solid var(--bc);background:var(--site-background)}.r8 .stars{display:flex;gap:4px;margin-bottom:16px}
.r8 svg{width:22px;height:22px;fill:var(--site-primary)}.r8 blockquote{font-size:19px;margin-bottom:18px}.r8 figcaption{font-weight:700}
.c6{padding:110px 0}.c6 .panel{display:grid;grid-template-columns:1fr 1fr;border-radius:var(--rad);overflow:hidden;border:var(--bw) solid var(--bc);box-shadow:var(--shd)}
.c6 .a{background:var(--site-primary);color:var(--on);padding:80px 56px;display:flex;align-items:center}.c6 .a h2{font-size:calc(var(--h2) * 1.15)}
.c6 .b{background:var(--site-surface);padding:80px 56px;display:flex;flex-direction:column;justify-content:center;align-items:flex-start;gap:30px}.c6 .b p{font-size:21px;color:var(--site-muted)}
.f1{border-top:1px solid color-mix(in srgb,var(--site-text) 12%,transparent);padding:30px 0;color:var(--site-muted);font-size:15px}.f1 .wrap{display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap}

@media (max-width:1100px){
  body{--h1:52px;--h2:40px}
  .links{display:none}
  .cl{grid-template-columns:1fr!important}
  .cl2{grid-template-columns:repeat(2,minmax(0,1fr))!important}
  .wrap{padding:0 28px}
}
@media (prefers-reduced-motion:reduce){
  *,*::before,*::after{animation:none!important;transition:none!important;scroll-behavior:auto!important}
}


/* barq: صور حقيقية مكان الـplaceholder، والرسمة 3D جوه مكانها، وكل قسم relative (الترتيب الحر في المحرر). */
img.ph{display:block;width:100%;height:100%;object-fit:cover;background:none;padding:0}
[data-scene]>.bq3d-scene{position:absolute;inset:0;width:100%!important;height:100%!important;scale:1}
main section{position:relative}
.bq-more-gallery{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;margin-top:22px}.bq-more-gallery img.ph{height:220px;border-radius:var(--rad,16px)}
.bq-stars{display:block;color:#f5a524;letter-spacing:2px;margin-bottom:8px}
@endverbatim</style>
<header class="n1"><div class="wrap"><a class="brand" href="#top">{{ $project->name }}</a><nav class="links" aria-label="أقسام الصفحة"><a href="#about">مين إحنا</a><a href="#services">خدماتنا</a><a href="#gallery">صور</a><a href="#reviews">آراء</a></nav><a class="btn" href="{{ $D->href('contact_link') }}"@if ($D->external('contact_link')) target="_blank" rel="noopener"@endif>سجّل</a></div></header>
<main id="page-top">
<section id="hero" class="hv10"><div class="wrap"><div class="panel cl"><div class="sp"><div class="scene" style="width:220px;height:220px"><div data-scene style="width:192px;height:192px">@include('site.partials.depth.scene', ['sceneKey' => ($sceneOverride ?? null) ?: 'swim'])</div></div></div><div><span class="kicker">تعليم سباحة للكبار والصغيرين</span><h1 data-slot="hero_title">{!! $D->text('hero_title') !!}</h1><p class="sub" data-slot="hero_subtitle">{!! $D->text('hero_subtitle') !!}</p><div class="actions"><a class="btn" data-slot="hero_cta" href="{{ $D->href('hero_cta') }}"@if ($D->external('hero_cta')) target="_blank" rel="noopener"@endif>سجل دلوقتي</a><a class="btn alt" href="#services">المستويات</a></div></div><div class="frame pic"><img class="ph " data-slot="hero_image" src="{{ $D->image('hero_image') }}" alt="{{ $project->name }}" loading="eager" fetchpriority="high"></div></div></div></section>
@if ($D->has('about'))<section class="sec a1" id="about"><div class="wrap cl"><h2 class="" data-slot="about_title">{!! $D->text('about_title') !!}</h2><p data-slot="about_body">{!! $D->text('about_body') !!}</p></div></section>@endif
@if ($D->has('services'))<section class="sec s6" id="services"><div class="wrap"><div class="shead center"><h2 class="" data-slot="services_title">{!! $D->text('services_title') !!}</h2></div><ul class="svc cl2" data-slot="services_list" style="--cols:3">@foreach ($D->services() as $e)<li><h3>{{ $e['title'] }}</h3>@if ($e['desc'])<p>{{ $e['desc'] }}</p>@endif</li>@endforeach</ul></div></section>@endif
@if ($D->has('gallery'))<section class="sec g7" id="gallery"><div class="wrap"><div class="ghead "><h2 class="" data-slot="gallery_title">{!! $D->text('gallery_title') !!}</h2></div><div class="grid cl"><img class="ph " data-slot="gallery_image_1" src="{{ $D->image('gallery_image_1') }}" alt="{{ $project->name }}" loading="lazy"><img class="ph " data-slot="gallery_image_2" src="{{ $D->image('gallery_image_2') }}" alt="{{ $project->name }}" loading="lazy"><img class="ph " data-slot="gallery_image_3" src="{{ $D->image('gallery_image_3') }}" alt="{{ $project->name }}" loading="lazy"></div></div>@php $more = $D->extraGallery(['gallery_image_1', 'gallery_image_2', 'gallery_image_3']); @endphp@if ($more)<div class="wrap bq-more-gallery">@foreach ($more as $k => $src)<img class="ph" data-slot="{{ $k }}" src="{{ $src }}" alt="{{ $project->name }}" loading="lazy">@endforeach</div>@endif</section>@endif
@if ($D->has('testimonials'))<section class="sec r8" id="reviews"><div class="wrap"><div class="rhead"><h2 class="" data-slot="testimonials_title">{!! $D->text('testimonials_title') !!}</h2>@if ($D->rating())@include('site.partials.rating-badge', ['badge' => ['rating' => $D->rating()]])@endif</div><div class="rv grid cl">@foreach ($D->testimonials() as $t)<figure data-slot="testimonials_list"><div class="stars" role="img" aria-label="تقييم خمس نجوم"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/></svg><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/></svg><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/></svg><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/></svg><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/></svg></div>@if ($t['stars'])<span class="bq-stars" role="img" aria-label="تقييم {{ $t['stars'] }} من 5">{{ str_repeat('★', $t['stars']) }}</span>@endif<blockquote class="bq-quote">{{ $t['quote'] }}</blockquote><figcaption>{{ $t['name'] }}</figcaption></figure>@endforeach</div></div></section>@endif
@if ($D->has('contact'))<section class="c6" id="contact"><div class="wrap"><div class="panel cl"><div class="a"><h2 class="" data-slot="contact_title">{!! $D->text('contact_title') !!}</h2></div><div class="b"><p data-slot="contact_note">{!! $D->text('contact_note') !!}</p><a class="btn big" data-slot="contact_link" href="{{ $D->href('contact_link') }}"@if ($D->external('contact_link')) target="_blank" rel="noopener"@endif>اتصل بالأكاديمية</a></div></div></div></section>@endif
</main>
<footer class="f1"><div class="wrap"><span>{{ $project->name }}</span><span>كل الحقوق محفوظة</span></div></footer>
