{{-- تصميم Claude Design (2026-10-13) — 02-medical/06-pediatrician.html. اتحوّل من HTML جاهز (convert98): الشكل ثابت والمحتوى من خانات
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
.hv6{padding:70px 0 110px;overflow:hidden}.hv6 .wrap{display:grid;grid-template-columns:1fr 1fr;gap:56px;align-items:center}
.hv6 .zone{position:relative;height:560px}.hv6 .blob{position:absolute;inset:20px 40px 40px 20px;overflow:hidden;border-radius:58% 42% 50% 50%/45% 55% 45% 55%;animation:morph 12s ease-in-out infinite;box-shadow:0 30px 60px rgba(0,0,0,.12)}
.hv6 .sp{position:absolute;bottom:-10px;inset-inline-end:-10px;animation:float 6s ease-in-out infinite}
.hv6 .bub span{position:absolute;border-radius:50%;background:color-mix(in srgb,var(--site-primary) 22%,transparent);animation:float 7s ease-in-out infinite}
.hv6 .bub span:nth-child(1){width:70px;height:70px;top:6px;inset-inline-end:10px}.hv6 .bub span:nth-child(2){width:34px;height:34px;top:120px;inset-inline-start:0;animation-delay:-2s}.hv6 .bub span:nth-child(3){width:22px;height:22px;bottom:80px;inset-inline-start:60px;animation-delay:-4s}
.a3 .in{padding:64px 72px;display:grid;grid-template-columns:auto 1fr;gap:56px;align-items:center}.a3 h2{color:var(--site-primary);max-width:320px}.a3 p{font-size:21px}
.s6 .svc{display:grid;grid-template-columns:repeat(var(--cols),minmax(0,1fr));gap:28px}
.s6 li{background:var(--site-surface);border-radius:var(--rad);border:var(--bw) solid var(--bc);padding:38px 30px;text-align:center;transition:translate .3s,box-shadow .3s}
.s6 li:hover{translate:0 -8px;box-shadow:0 20px 40px rgba(0,0,0,.08)}.s6 li::before{content:"";display:block;width:84px;height:84px;margin:0 auto 22px;border-radius:50%;background:radial-gradient(circle at 35% 35%,var(--site-surface) 0 12%,var(--site-primary) 13% 100%)}
.s6 li:nth-child(even)::before{background:radial-gradient(circle at 35% 35%,var(--site-surface) 0 12%,color-mix(in srgb,var(--site-primary) 55%,var(--site-background)) 13% 100%)}.s6 h3{font-size:23px;margin-bottom:6px}.s6 p{color:var(--site-muted)}
.g5{background:color-mix(in srgb,var(--site-primary) 8%,var(--site-background))}.g5 .row{display:flex;justify-content:center;gap:40px;flex-wrap:wrap}
.g5 .c{width:320px;height:320px;border-radius:50%;overflow:hidden;border:10px solid var(--site-surface);box-shadow:0 20px 40px rgba(0,0,0,.1)}.g5 .c:nth-child(2){margin-top:60px}
.r3 .grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:32px}
.r3 blockquote{position:relative;padding:28px;background:var(--site-surface);border:var(--bw) solid var(--site-text);border-radius:22px;font-size:19px;font-weight:600}
.r3 blockquote::after{content:"";position:absolute;bottom:-14px;inset-inline-start:40px;width:24px;height:24px;background:var(--site-surface);border-inline-end:var(--bw) solid var(--site-text);border-bottom:var(--bw) solid var(--site-text);rotate:45deg}
.r3 figcaption{margin-top:26px;padding-inline-start:30px;font-family:var(--hf);color:var(--site-primary);font-weight:700}
.c3{padding:110px 0}.c3 .box{position:relative;overflow:hidden;background:var(--site-primary);color:var(--on);border-radius:calc(var(--rad) * 1.4);border:var(--bw) solid var(--bc);padding:90px 40px}
.c3 .box::before,.c3 .box::after{content:"";position:absolute;border-radius:50%;background:color-mix(in srgb,var(--on) 14%,transparent);animation:float 8s ease-in-out infinite}
.c3 .box::before{width:260px;height:260px;top:-80px;left:-60px}.c3 .box::after{width:160px;height:160px;bottom:-50px;right:80px;animation-delay:-3s}
.c3 h2,.c3 p,.c3 .btn{position:relative;z-index:1}.c3 p{font-size:21px;margin:16px auto 36px;max-width:580px}.c3 .btn.inv{background:var(--on);color:var(--site-primary)}
.f2{padding:40px 0;text-align:center;color:var(--site-muted);font-size:15px;border-top:1px solid var(--bc)}.f2 b{display:block;font-family:var(--hf);color:var(--site-primary);font-size:24px;margin-bottom:6px}

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
<header class="n3"><div class="bar"><a class="brand" href="#top">{{ $project->name }}</a><nav class="links" aria-label="أقسام الصفحة"><a href="#about">مين إحنا</a><a href="#services">خدماتنا</a><a href="#gallery">صور</a><a href="#reviews">آراء</a></nav><a class="btn" href="{{ $D->href('contact_link') }}"@if ($D->external('contact_link')) target="_blank" rel="noopener"@endif>احجز</a></div></header>
<main id="page-top">
<section id="hero" class="hv6"><div class="wrap cl"><div><span class="kicker">طب الأطفال وحديثي الولادة</span><h1 data-slot="hero_title">{!! $D->text('hero_title') !!}</h1><p class="sub" data-slot="hero_subtitle">{!! $D->text('hero_subtitle') !!}</p><div class="actions"><a class="btn" data-slot="hero_cta" href="{{ $D->href('hero_cta') }}"@if ($D->external('hero_cta')) target="_blank" rel="noopener"@endif>احجز كشف</a><a class="btn alt" href="#services">خدماتنا</a></div></div><div class="zone"><div class="bub" aria-hidden="true"><span></span><span></span><span></span></div><div class="blob"><img class="ph " data-slot="hero_image" src="{{ $D->image('hero_image') }}" alt="{{ $project->name }}" loading="eager" fetchpriority="high"></div><div class="sp"><div class="scene" style="width:240px;height:240px"><div data-scene style="width:212px;height:212px">@include('site.partials.depth.scene', ['sceneKey' => ($sceneOverride ?? null) ?: 'teddy'])</div></div></div></div></div></section>
@if ($D->has('about'))<section class="sec a3" id="about"><div class="wrap"><div class="card in cl"><h2 class="" data-slot="about_title">{!! $D->text('about_title') !!}</h2><p data-slot="about_body">{!! $D->text('about_body') !!}</p></div></div></section>@endif
@if ($D->has('services'))<section class="sec s6" id="services"><div class="wrap"><div class="shead center"><h2 class="" data-slot="services_title">{!! $D->text('services_title') !!}</h2></div><ul class="svc cl2" data-slot="services_list" style="--cols:3">@foreach ($D->services() as $e)<li><h3>{{ $e['title'] }}</h3>@if ($e['desc'])<p>{{ $e['desc'] }}</p>@endif</li>@endforeach</ul></div></section>@endif
@if ($D->has('gallery'))<section class="sec g5" id="gallery"><div class="wrap"><div class="ghead center"><h2 class="" data-slot="gallery_title">{!! $D->text('gallery_title') !!}</h2></div><div class="row"><div class="c"><img class="ph " data-slot="gallery_image_1" src="{{ $D->image('gallery_image_1') }}" alt="{{ $project->name }}" loading="lazy"></div><div class="c"><img class="ph " data-slot="gallery_image_2" src="{{ $D->image('gallery_image_2') }}" alt="{{ $project->name }}" loading="lazy"></div><div class="c"><img class="ph " data-slot="gallery_image_3" src="{{ $D->image('gallery_image_3') }}" alt="{{ $project->name }}" loading="lazy"></div></div></div>@php $more = $D->extraGallery(['gallery_image_1', 'gallery_image_2', 'gallery_image_3']); @endphp@if ($more)<div class="wrap bq-more-gallery">@foreach ($more as $k => $src)<img class="ph" data-slot="{{ $k }}" src="{{ $src }}" alt="{{ $project->name }}" loading="lazy">@endforeach</div>@endif</section>@endif
@if ($D->has('testimonials'))<section class="sec r3" id="reviews"><div class="wrap"><div class="rhead center"><h2 class="" data-slot="testimonials_title">{!! $D->text('testimonials_title') !!}</h2>@if ($D->rating())@include('site.partials.rating-badge', ['badge' => ['rating' => $D->rating()]])@endif</div><div class="rv grid cl">@foreach ($D->testimonials() as $t)<figure data-slot="testimonials_list">@if ($t['stars'])<span class="bq-stars" role="img" aria-label="تقييم {{ $t['stars'] }} من 5">{{ str_repeat('★', $t['stars']) }}</span>@endif<blockquote class="bq-quote">{{ $t['quote'] }}</blockquote><figcaption>{{ $t['name'] }}</figcaption></figure>@endforeach</div></div></section>@endif
@if ($D->has('contact'))<section class="c3" id="contact"><div class="wrap"><div class="box center"><h2 class="" data-slot="contact_title">{!! $D->text('contact_title') !!}</h2><p data-slot="contact_note">{!! $D->text('contact_note') !!}</p><a class="btn big inv" data-slot="contact_link" href="{{ $D->href('contact_link') }}"@if ($D->external('contact_link')) target="_blank" rel="noopener"@endif>احجزي على واتساب</a></div></div></section>@endif
</main>
<footer class="f2"><b>{{ $project->name }}</b><span>كل الحقوق محفوظة</span></footer>
