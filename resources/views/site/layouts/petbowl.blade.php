{{-- تصميم Claude Design (2026-10-13) — 20-pets/05-pet-food.html. اتحوّل من HTML جاهز (convert98): الشكل ثابت والمحتوى من خانات
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

body{--hf:var(--site-font-heading);--hw:700;--on:var(--site-surface);--rad:32px;--bw:4px;--bc:color-mix(in srgb,var(--site-primary) 30%,transparent);--shd:0 8px 0 color-mix(in srgb,var(--site-primary) 25%,transparent);--h1:62px;--h2:46px;}

.btn{border-radius:999px;background:var(--site-primary);color:var(--on);font-weight:700;box-shadow:0 6px 0 color-mix(in srgb,var(--site-text) 30%,transparent);transition:translate .15s,box-shadow .15s}
.btn:hover{translate:0 3px;box-shadow:0 3px 0 color-mix(in srgb,var(--site-text) 30%,transparent)}
.btn.alt{background:var(--site-surface);color:var(--site-primary)}
.hl{color:var(--site-primary);text-decoration:underline wavy color-mix(in srgb,var(--site-primary) 50%,transparent);text-underline-offset:12px;text-decoration-thickness:3px}
.kicker{color:var(--site-primary);background:color-mix(in srgb,var(--site-primary) 12%,var(--site-surface));padding:6px 18px;border-radius:999px;font-weight:700}
.ph{background:radial-gradient(color-mix(in srgb,var(--site-primary) 20%,transparent) 6px,transparent 7px) 0 0/36px 36px,var(--site-surface)}
.scene{border-radius:50%;background:var(--site-surface);border:4px dashed color-mix(in srgb,var(--site-primary) 40%,transparent);animation:bounce 2.8s ease-in-out infinite}

.shead{margin-bottom:56px}.shead.split{display:flex;align-items:end;justify-content:space-between;gap:24px;flex-wrap:wrap}.shead.split p{max-width:380px}.shead.center p{margin-top:12px}
.ghead{margin-bottom:52px}
.rhead{margin-bottom:56px}
.n3{position:sticky;top:16px;z-index:20;margin:16px auto 0;max-width:1200px;padding:0 20px}
.n3 .bar{display:flex;align-items:center;gap:28px;min-height:72px;padding:10px 12px 10px 12px;padding-inline-start:28px;border-radius:999px;background:var(--site-surface);box-shadow:0 10px 30px rgba(0,0,0,.08)}.n3 .links{margin-inline-start:auto}.n3 .btn{min-height:48px}
.hv4{padding:60px 0 100px}.hv4 .wrap{display:grid;grid-template-columns:1fr 540px;gap:48px;align-items:center}
.hv4 .disc{position:relative;width:540px;max-width:100%;aspect-ratio:1;justify-self:center}.hv4 .ring{position:absolute;inset:0;border-radius:50%;border:3px dashed var(--site-primary);animation:spin 30s linear infinite}
.hv4 .round{position:absolute;inset:28px;border-radius:50%}.hv4 .sp{position:absolute;top:-10px;inset-inline-start:-40px}
.a7 .wrap{display:grid;grid-template-columns:.9fr 1.1fr;gap:72px;align-items:center}.a7 .mark{height:420px;border-radius:var(--rad);border:var(--bw) solid var(--bc)}
.a7 .mark b{font-family:var(--hf);font-size:240px;line-height:1;color:var(--site-primary);font-weight:var(--hw)}.a7 p{font-size:21px;margin-top:24px}
.s1 .svc{display:grid;grid-template-columns:repeat(var(--cols),minmax(0,1fr));border-top:1px solid color-mix(in srgb,var(--site-text) 15%,transparent)}
.s1 li{padding:40px 32px 40px 0;padding-inline:0 32px;border-bottom:1px solid color-mix(in srgb,var(--site-text) 15%,transparent);counter-increment:s;transition:background .3s}
.s1 li:hover{background:color-mix(in srgb,var(--site-primary) 6%,transparent)}.s1 li::before{content:"0" counter(s);display:block;font-family:var(--hf);color:var(--site-primary);margin-bottom:12px}
.s1 h3{font-size:26px;margin-bottom:8px}.s1 p{color:var(--site-muted)}
.g3{background:var(--site-surface)}.g3 .grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:28px}
.g3 figure{margin:0;padding:12px 12px 18px;background:var(--site-surface);border:var(--bw) solid var(--bc);box-shadow:var(--shd)}.g3 .ph{height:260px}
.g3 figure:nth-child(odd){rotate:-2deg}.g3 figure:nth-child(even){rotate:2deg;margin-top:30px}
.r6 .grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:28px}.r6 figure{background:var(--site-surface);border-radius:var(--rad);border:var(--bw) solid var(--bc);box-shadow:var(--shd);padding:36px}
.r6 blockquote{font-size:19px;margin-bottom:22px}.r6 figcaption{display:flex;align-items:center;gap:14px;font-family:var(--hf);font-weight:700}
.r6 .av{width:46px;height:46px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:color-mix(in srgb,var(--site-primary) 18%,var(--site-surface));color:var(--site-primary)}
.c2{padding:60px 0 140px}.c2 .box{border:1px solid var(--site-primary);outline:1px solid color-mix(in srgb,var(--site-primary) 40%,transparent);outline-offset:10px;padding:90px 48px;background:var(--site-surface)}
.c2 p{color:var(--site-muted);font-size:20px;margin:20px auto 40px;max-width:560px}
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
<header class="n3"><div class="bar"><a class="brand" href="#top">{{ $project->name }}</a><nav class="links" aria-label="أقسام الصفحة"><a href="#about">مين إحنا</a><a href="#services">خدماتنا</a><a href="#gallery">صور</a><a href="#reviews">آراء</a></nav><a class="btn" href="{{ $D->href('contact_link') }}"@if ($D->external('contact_link')) target="_blank" rel="noopener"@endif>اطلب</a></div></header>
<main id="page-top">
<section id="hero" class="hv4"><div class="wrap cl"><div><span class="kicker">أكل ومستلزمات الحيوانات الأليفة</span><h1 data-slot="hero_title">{!! $D->text('hero_title') !!}</h1><p class="sub" data-slot="hero_subtitle">{!! $D->text('hero_subtitle') !!}</p><div class="actions"><a class="btn" data-slot="hero_cta" href="{{ $D->href('hero_cta') }}"@if ($D->external('hero_cta')) target="_blank" rel="noopener"@endif>اطلب على واتساب</a><a class="btn alt" href="#services">الأقسام</a></div></div><div class="disc"><div class="ring" aria-hidden="true"></div><div class="frame round"><img class="ph " data-slot="hero_image" src="{{ $D->image('hero_image') }}" alt="{{ $project->name }}" loading="eager" fetchpriority="high"></div><div class="sp"><div class="scene" style="width:230px;height:230px"><div data-scene style="width:202px;height:202px">@include('site.partials.depth.scene', ['sceneKey' => ($sceneOverride ?? null) ?: 'petbowl'])</div></div></div></div></div></section>
@if ($D->has('about'))<section class="sec a7" id="about"><div class="wrap cl"><div class="mark ph" aria-hidden="true"><b>ب</b></div><div><h2 class="" data-slot="about_title">{!! $D->text('about_title') !!}</h2><p data-slot="about_body">{!! $D->text('about_body') !!}</p></div></div></section>@endif
@if ($D->has('services'))<section class="sec s1" id="services"><div class="wrap"><div class="shead split"><h2 class="" data-slot="services_title">{!! $D->text('services_title') !!}</h2></div><ul class="svc cl2" data-slot="services_list" style="--cols:3">@foreach ($D->services() as $e)<li><h3>{{ $e['title'] }}</h3>@if ($e['desc'])<p>{{ $e['desc'] }}</p>@endif</li>@endforeach</ul></div></section>@endif
@if ($D->has('gallery'))<section class="sec g3" id="gallery"><div class="wrap"><div class="ghead center"><h2 class="" data-slot="gallery_title">{!! $D->text('gallery_title') !!}</h2></div><div class="grid cl2"><figure><img class="ph " data-slot="gallery_image_1" src="{{ $D->image('gallery_image_1') }}" alt="{{ $project->name }}" loading="lazy"></figure><figure><img class="ph " data-slot="gallery_image_2" src="{{ $D->image('gallery_image_2') }}" alt="{{ $project->name }}" loading="lazy"></figure><figure><img class="ph " data-slot="gallery_image_3" src="{{ $D->image('gallery_image_3') }}" alt="{{ $project->name }}" loading="lazy"></figure><figure><img class="ph " data-slot="gallery_image_4" src="{{ $D->image('gallery_image_4') }}" alt="{{ $project->name }}" loading="lazy"></figure></div></div>@php $more = $D->extraGallery(['gallery_image_1', 'gallery_image_2', 'gallery_image_3', 'gallery_image_4']); @endphp@if ($more)<div class="wrap bq-more-gallery">@foreach ($more as $k => $src)<img class="ph" data-slot="{{ $k }}" src="{{ $src }}" alt="{{ $project->name }}" loading="lazy">@endforeach</div>@endif</section>@endif
@if ($D->has('testimonials'))<section class="sec r6" id="reviews"><div class="wrap"><div class="rhead center"><h2 class="" data-slot="testimonials_title">{!! $D->text('testimonials_title') !!}</h2>@if ($D->rating())@include('site.partials.rating-badge', ['badge' => ['rating' => $D->rating()]])@endif</div><div class="rv grid cl">@foreach ($D->testimonials() as $t)<figure data-slot="testimonials_list">@if ($t['stars'])<span class="bq-stars" role="img" aria-label="تقييم {{ $t['stars'] }} من 5">{{ str_repeat('★', $t['stars']) }}</span>@endif<blockquote class="bq-quote">{{ $t['quote'] }}</blockquote><figcaption><span class="av" aria-hidden="true">{{ $t['initial'] }}</span>{{ $t['name'] }}</figcaption></figure>@endforeach</div></div></section>@endif
@if ($D->has('contact'))<section class="c2" id="contact"><div class="wrap"><div class="box center"><h2 class="" data-slot="contact_title">{!! $D->text('contact_title') !!}</h2><p data-slot="contact_note">{!! $D->text('contact_note') !!}</p><a class="btn big" data-slot="contact_link" href="{{ $D->href('contact_link') }}"@if ($D->external('contact_link')) target="_blank" rel="noopener"@endif>ابعت على واتساب</a></div></div></section>@endif
</main>
<footer class="f1"><div class="wrap"><span>{{ $project->name }}</span><span>كل الحقوق محفوظة</span></div></footer>
