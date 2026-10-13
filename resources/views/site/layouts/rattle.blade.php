{{-- تصميم Claude Design (2026-10-13) — 17-nurseries/02-baby-nursery.html. اتحوّل من HTML جاهز (convert98): الشكل ثابت والمحتوى من خانات
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

body{--hf:var(--site-font-heading);--hw:800;--on:var(--site-surface);--rad:40px;--bw:0px;--bc:transparent;--shd:0 20px 50px rgba(0,0,0,.08);--h1:68px;--h2:50px;}

.btn{border-radius:999px;background:var(--site-primary);color:var(--on);transition:scale .25s}
.btn:hover{scale:1.05}
.btn.alt{background:var(--site-surface);color:var(--site-primary)}
.hl{position:relative;z-index:0;color:var(--site-primary);white-space:nowrap}
.hl::after{content:"";position:absolute;inset-inline:0;bottom:.12em;height:.28em;border-radius:1em;background:color-mix(in srgb,var(--site-primary) 18%,transparent);z-index:-1}
.kicker{color:var(--site-primary);font-weight:700}
.kicker::before{content:"";width:14px;height:14px;border-radius:0 50%;background:var(--site-primary)}
.ph{background:radial-gradient(circle at 30% 30%,var(--site-surface),color-mix(in srgb,var(--site-primary) 18%,var(--site-background)))}
.scene{background:var(--site-surface);box-shadow:0 20px 40px rgba(0,0,0,.1);border-radius:58% 42% 50% 50%/45% 55% 45% 55%;animation:morph 12s ease-in-out infinite}

.shead{margin-bottom:56px}.shead.split{display:flex;align-items:end;justify-content:space-between;gap:24px;flex-wrap:wrap}.shead.split p{max-width:380px}.shead.center p{margin-top:12px}
.ghead{margin-bottom:52px}
.rhead{margin-bottom:56px}
.n1{position:sticky;top:0;z-index:20;background:color-mix(in srgb,var(--site-background) 92%,transparent);backdrop-filter:blur(10px);border-bottom:1px solid color-mix(in srgb,var(--site-text) 10%,transparent)}
.n1 .wrap{display:flex;align-items:center;gap:36px;height:84px}.n1 .links{margin-inline-start:auto}
.hv1{padding:72px 0 120px}.hv1 .wrap{display:grid;grid-template-columns:1.05fr .95fr;gap:72px;align-items:center}
.hv1 .vis{position:relative;max-width:500px;width:100%;justify-self:center}.hv1 .arch{aspect-ratio:500/620;border-radius:250px 250px var(--rad) var(--rad);box-shadow:0 30px 60px rgba(0,0,0,.12)}
.hv1 .sp{position:absolute;inset-inline-start:-90px;bottom:-50px}
.a2 .wrap{max-width:900px}.a2 p{font-size:22px;line-height:2.1;margin-top:28px;color:var(--site-muted)}
.a2 .orn{display:flex;align-items:center;justify-content:center;gap:16px;margin-bottom:26px}.a2 .orn::before,.a2 .orn::after{content:"";width:80px;height:1px;background:var(--site-primary)}
.a2 .orn i{width:10px;height:10px;rotate:45deg;border:1px solid var(--site-primary);display:block}
.s6 .svc{display:grid;grid-template-columns:repeat(var(--cols),minmax(0,1fr));gap:28px}
.s6 li{background:var(--site-surface);border-radius:var(--rad);border:var(--bw) solid var(--bc);padding:38px 30px;text-align:center;transition:translate .3s,box-shadow .3s}
.s6 li:hover{translate:0 -8px;box-shadow:0 20px 40px rgba(0,0,0,.08)}.s6 li::before{content:"";display:block;width:84px;height:84px;margin:0 auto 22px;border-radius:50%;background:radial-gradient(circle at 35% 35%,var(--site-surface) 0 12%,var(--site-primary) 13% 100%)}
.s6 li:nth-child(even)::before{background:radial-gradient(circle at 35% 35%,var(--site-surface) 0 12%,color-mix(in srgb,var(--site-primary) 55%,var(--site-background)) 13% 100%)}.s6 h3{font-size:23px;margin-bottom:6px}.s6 p{color:var(--site-muted)}
.g6 .mas{columns:3;column-gap:20px}.g6 .it{break-inside:avoid;margin-bottom:20px;border-radius:var(--rad);overflow:hidden}@media (max-width:1100px){.g6 .mas{columns:2}}
.r2{border-top:1px solid var(--bc)}.r2 .grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr))}
.r2 figure{padding:0 40px;text-align:center;border-inline-start:1px solid var(--bc)}.r2 figure:first-child{border:0}
.r2 figure::before{content:"\201D";display:block;font-family:var(--hf);font-size:90px;line-height:.6;color:var(--site-primary);margin-bottom:18px}
.r2 blockquote{font-family:var(--hf);font-size:24px;line-height:1.7;margin-bottom:20px}.r2 figcaption{color:var(--site-primary);font-size:15px;letter-spacing:1px}
.c3{padding:110px 0}.c3 .box{position:relative;overflow:hidden;background:var(--site-primary);color:var(--on);border-radius:calc(var(--rad) * 1.4);border:var(--bw) solid var(--bc);padding:90px 40px}
.c3 .box::before,.c3 .box::after{content:"";position:absolute;border-radius:50%;background:color-mix(in srgb,var(--on) 14%,transparent);animation:float 8s ease-in-out infinite}
.c3 .box::before{width:260px;height:260px;top:-80px;left:-60px}.c3 .box::after{width:160px;height:160px;bottom:-50px;right:80px;animation-delay:-3s}
.c3 h2,.c3 p,.c3 .btn{position:relative;z-index:1}.c3 p{font-size:21px;margin:16px auto 36px;max-width:580px}.c3 .btn.inv{background:var(--on);color:var(--site-primary)}
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
<header class="n1"><div class="wrap"><a class="brand" href="#top">{{ $project->name }}</a><nav class="links" aria-label="أقسام الصفحة"><a href="#about">مين إحنا</a><a href="#services">خدماتنا</a><a href="#gallery">صور</a><a href="#reviews">آراء</a></nav><a class="btn" href="{{ $D->href('contact_link') }}"@if ($D->external('contact_link')) target="_blank" rel="noopener"@endif>اتصل</a></div></header>
<main id="page-top">
<section id="hero" class="hv1"><div class="wrap cl"><div><span class="kicker">حضانة من ٣ شهور لـ٣ سنين</span><h1 data-slot="hero_title">{!! $D->text('hero_title') !!}</h1><p class="sub" data-slot="hero_subtitle">{!! $D->text('hero_subtitle') !!}</p><div class="actions"><a class="btn" data-slot="hero_cta" href="{{ $D->href('hero_cta') }}"@if ($D->external('hero_cta')) target="_blank" rel="noopener"@endif>احجزي زيارة</a><a class="btn alt" href="#services">خدماتنا</a></div></div><div class="vis"><div class="frame arch"><img class="ph " data-slot="hero_image" src="{{ $D->image('hero_image') }}" alt="{{ $project->name }}" loading="eager" fetchpriority="high"></div><div class="sp"><div class="scene" style="width:260px;height:260px"><div data-scene style="width:232px;height:232px">@include('site.partials.depth.scene', ['sceneKey' => ($sceneOverride ?? null) ?: 'rattle'])</div></div></div></div></div></section>
@if ($D->has('about'))<section class="sec a2" id="about"><div class="wrap center"><div class="orn" aria-hidden="true"><i></i></div><h2 class="" data-slot="about_title">{!! $D->text('about_title') !!}</h2><p data-slot="about_body">{!! $D->text('about_body') !!}</p></div></section>@endif
@if ($D->has('services'))<section class="sec s6" id="services"><div class="wrap"><div class="shead center"><h2 class="" data-slot="services_title">{!! $D->text('services_title') !!}</h2></div><ul class="svc cl2" data-slot="services_list" style="--cols:3">@foreach ($D->services() as $e)<li><h3>{{ $e['title'] }}</h3>@if ($e['desc'])<p>{{ $e['desc'] }}</p>@endif</li>@endforeach</ul></div></section>@endif
@if ($D->has('gallery'))<section class="sec g6" id="gallery"><div class="wrap"><div class="ghead "><h2 class="" data-slot="gallery_title">{!! $D->text('gallery_title') !!}</h2></div><div class="mas"><div class="it" style="height:380px"><img class="ph " data-slot="gallery_image_1" src="{{ $D->image('gallery_image_1') }}" alt="{{ $project->name }}" loading="lazy"></div><div class="it" style="height:260px"><img class="ph " data-slot="gallery_image_2" src="{{ $D->image('gallery_image_2') }}" alt="{{ $project->name }}" loading="lazy"></div><div class="it" style="height:320px"><img class="ph " data-slot="gallery_image_3" src="{{ $D->image('gallery_image_3') }}" alt="{{ $project->name }}" loading="lazy"></div><div class="it" style="height:300px"><img class="ph " data-slot="gallery_image_4" src="{{ $D->image('gallery_image_4') }}" alt="{{ $project->name }}" loading="lazy"></div><div class="it" style="height:360px"><img class="ph " data-slot="gallery_image_5" src="{{ $D->image('gallery_image_5') }}" alt="{{ $project->name }}" loading="lazy"></div><div class="it" style="height:240px"><img class="ph " data-slot="gallery_image_6" src="{{ $D->image('gallery_image_6') }}" alt="{{ $project->name }}" loading="lazy"></div></div></div>@php $more = $D->extraGallery(['gallery_image_1', 'gallery_image_2', 'gallery_image_3', 'gallery_image_4', 'gallery_image_5', 'gallery_image_6']); @endphp@if ($more)<div class="wrap bq-more-gallery">@foreach ($more as $k => $src)<img class="ph" data-slot="{{ $k }}" src="{{ $src }}" alt="{{ $project->name }}" loading="lazy">@endforeach</div>@endif</section>@endif
@if ($D->has('testimonials'))<section class="sec r2" id="reviews"><div class="wrap"><div class="rhead center"><h2 class="" data-slot="testimonials_title">{!! $D->text('testimonials_title') !!}</h2>@if ($D->rating())@include('site.partials.rating-badge', ['badge' => ['rating' => $D->rating()]])@endif</div><div class="rv grid cl">@foreach ($D->testimonials() as $t)<figure data-slot="testimonials_list">@if ($t['stars'])<span class="bq-stars" role="img" aria-label="تقييم {{ $t['stars'] }} من 5">{{ str_repeat('★', $t['stars']) }}</span>@endif<blockquote class="bq-quote">{{ $t['quote'] }}</blockquote><figcaption>{{ $t['name'] }}</figcaption></figure>@endforeach</div></div></section>@endif
@if ($D->has('contact'))<section class="c3" id="contact"><div class="wrap"><div class="box center"><h2 class="" data-slot="contact_title">{!! $D->text('contact_title') !!}</h2><p data-slot="contact_note">{!! $D->text('contact_note') !!}</p><a class="btn big inv" data-slot="contact_link" href="{{ $D->href('contact_link') }}"@if ($D->external('contact_link')) target="_blank" rel="noopener"@endif>اتصلي بالحضانة</a></div></div></section>@endif
</main>
<footer class="f1"><div class="wrap"><span>{{ $project->name }}</span><span>كل الحقوق محفوظة</span></div></footer>
