{{-- تصميم Claude Design (2026-10-13) — 02-medical/07-pharmacy.html. اتحوّل من HTML جاهز (convert98): الشكل ثابت والمحتوى من خانات
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

body{--hf:var(--site-font-heading);--hw:700;--on:var(--site-surface);--rad:40px;--bw:0px;--bc:transparent;--shd:0 20px 50px rgba(0,0,0,.08);--h1:68px;--h2:50px;}

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
.n3{position:sticky;top:16px;z-index:20;margin:16px auto 0;max-width:1200px;padding:0 20px}
.n3 .bar{display:flex;align-items:center;gap:28px;min-height:72px;padding:10px 12px 10px 12px;padding-inline-start:28px;border-radius:999px;background:var(--site-surface);box-shadow:0 10px 30px rgba(0,0,0,.08)}.n3 .links{margin-inline-start:auto}.n3 .btn{min-height:48px}
.hv12{padding:64px 0 110px}.hv12 .sp{display:flex;justify-content:center;margin:0 auto 36px;width:max-content}.hv12 h1{max-width:940px;margin:0 auto}.hv12 .sub{margin-inline:auto;max-width:640px}
.hv12 .band{height:420px;margin-top:70px}
.a3 .in{padding:64px 72px;display:grid;grid-template-columns:auto 1fr;gap:56px;align-items:center}.a3 h2{color:var(--site-primary);max-width:320px}.a3 p{font-size:21px}
.s6 .svc{display:grid;grid-template-columns:repeat(var(--cols),minmax(0,1fr));gap:28px}
.s6 li{background:var(--site-surface);border-radius:var(--rad);border:var(--bw) solid var(--bc);padding:38px 30px;text-align:center;transition:translate .3s,box-shadow .3s}
.s6 li:hover{translate:0 -8px;box-shadow:0 20px 40px rgba(0,0,0,.08)}.s6 li::before{content:"";display:block;width:84px;height:84px;margin:0 auto 22px;border-radius:50%;background:radial-gradient(circle at 35% 35%,var(--site-surface) 0 12%,var(--site-primary) 13% 100%)}
.s6 li:nth-child(even)::before{background:radial-gradient(circle at 35% 35%,var(--site-surface) 0 12%,color-mix(in srgb,var(--site-primary) 55%,var(--site-background)) 13% 100%)}.s6 h3{font-size:23px;margin-bottom:6px}.s6 p{color:var(--site-muted)}
.g6 .mas{columns:3;column-gap:20px}.g6 .it{break-inside:avoid;margin-bottom:20px;border-radius:var(--rad);overflow:hidden}@media (max-width:1100px){.g6 .mas{columns:2}}
.r1{background:color-mix(in srgb,var(--site-primary) 9%,var(--site-background))}.r1 .grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:32px}
.r1 figure{background:var(--site-surface);padding:40px 36px;box-shadow:0 14px 30px rgba(0,0,0,.08);position:relative}.r1 figure:nth-child(1){rotate:-1.5deg}.r1 figure:nth-child(3){rotate:1.5deg}
.r1 figure::before{content:"";position:absolute;top:-12px;left:50%;width:90px;height:24px;margin-left:-45px;background:color-mix(in srgb,var(--site-primary) 28%,transparent)}
.r1 blockquote{font-size:19px;margin-bottom:22px}.r1 figcaption{font-weight:700;color:var(--site-primary)}
.c1{padding:130px 0}.c1 h2{font-size:calc(var(--h2) * 1.2)}.c1 p{font-size:21px;color:var(--site-muted);margin:20px auto 40px;max-width:580px}
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
<header class="n3"><div class="bar"><a class="brand" href="#top">{{ $project->name }}</a><nav class="links" aria-label="أقسام الصفحة"><a href="#about">مين إحنا</a><a href="#services">خدماتنا</a><a href="#gallery">صور</a><a href="#reviews">آراء</a></nav><a class="btn" href="{{ $D->href('contact_link') }}"@if ($D->external('contact_link')) target="_blank" rel="noopener"@endif>اطلب دلوقتي</a></div></header>
<main id="page-top">
<section id="hero" class="hv12"><div class="wrap center"><div class="sp"><div class="scene" style="width:290px;height:290px"><div data-scene style="width:262px;height:262px">@include('site.partials.depth.scene', ['sceneKey' => ($sceneOverride ?? null) ?: 'capsule'])</div></div></div><span class="kicker">توصيل للبيت ٢٤ ساعة</span><h1 data-slot="hero_title">{!! $D->text('hero_title') !!}</h1><p class="sub" data-slot="hero_subtitle">{!! $D->text('hero_subtitle') !!}</p><div class="actions" style="justify-content:center"><a class="btn" data-slot="hero_cta" href="{{ $D->href('hero_cta') }}"@if ($D->external('hero_cta')) target="_blank" rel="noopener"@endif>ابعت الروشتة</a><a class="btn alt" href="#services">خدماتنا</a></div><div class="frame band"><img class="ph " data-slot="hero_image" src="{{ $D->image('hero_image') }}" alt="{{ $project->name }}" loading="eager" fetchpriority="high"></div></div></section>
@if ($D->has('about'))<section class="sec a3" id="about"><div class="wrap"><div class="card in cl"><h2 class="" data-slot="about_title">{!! $D->text('about_title') !!}</h2><p data-slot="about_body">{!! $D->text('about_body') !!}</p></div></div></section>@endif
@if ($D->has('services'))<section class="sec s6" id="services"><div class="wrap"><div class="shead center"><h2 class="" data-slot="services_title">{!! $D->text('services_title') !!}</h2></div><ul class="svc cl2" data-slot="services_list" style="--cols:3">@foreach ($D->services() as $e)<li><h3>{{ $e['title'] }}</h3>@if ($e['desc'])<p>{{ $e['desc'] }}</p>@endif</li>@endforeach</ul></div></section>@endif
@if ($D->has('gallery'))<section class="sec g6" id="gallery"><div class="wrap"><div class="ghead "><h2 class="" data-slot="gallery_title">{!! $D->text('gallery_title') !!}</h2></div><div class="mas"><div class="it" style="height:380px"><img class="ph " data-slot="gallery_image_1" src="{{ $D->image('gallery_image_1') }}" alt="{{ $project->name }}" loading="lazy"></div><div class="it" style="height:260px"><img class="ph " data-slot="gallery_image_2" src="{{ $D->image('gallery_image_2') }}" alt="{{ $project->name }}" loading="lazy"></div><div class="it" style="height:320px"><img class="ph " data-slot="gallery_image_3" src="{{ $D->image('gallery_image_3') }}" alt="{{ $project->name }}" loading="lazy"></div><div class="it" style="height:300px"><img class="ph " data-slot="gallery_image_4" src="{{ $D->image('gallery_image_4') }}" alt="{{ $project->name }}" loading="lazy"></div><div class="it" style="height:360px"><img class="ph " data-slot="gallery_image_5" src="{{ $D->image('gallery_image_5') }}" alt="{{ $project->name }}" loading="lazy"></div><div class="it" style="height:240px"><img class="ph " data-slot="gallery_image_6" src="{{ $D->image('gallery_image_6') }}" alt="{{ $project->name }}" loading="lazy"></div></div></div>@php $more = $D->extraGallery(['gallery_image_1', 'gallery_image_2', 'gallery_image_3', 'gallery_image_4', 'gallery_image_5', 'gallery_image_6']); @endphp@if ($more)<div class="wrap bq-more-gallery">@foreach ($more as $k => $src)<img class="ph" data-slot="{{ $k }}" src="{{ $src }}" alt="{{ $project->name }}" loading="lazy">@endforeach</div>@endif</section>@endif
@if ($D->has('testimonials'))<section class="sec r1" id="reviews"><div class="wrap"><div class="rhead center"><h2 class="" data-slot="testimonials_title">{!! $D->text('testimonials_title') !!}</h2>@if ($D->rating())@include('site.partials.rating-badge', ['badge' => ['rating' => $D->rating()]])@endif</div><div class="rv grid cl">@foreach ($D->testimonials() as $t)<figure data-slot="testimonials_list">@if ($t['stars'])<span class="bq-stars" role="img" aria-label="تقييم {{ $t['stars'] }} من 5">{{ str_repeat('★', $t['stars']) }}</span>@endif<blockquote class="bq-quote">{{ $t['quote'] }}</blockquote><figcaption>{{ $t['name'] }}</figcaption></figure>@endforeach</div></div></section>@endif
@if ($D->has('contact'))<section class="c1" id="contact"><div class="wrap center"><h2 class="" data-slot="contact_title">{!! $D->text('contact_title') !!}</h2><p data-slot="contact_note">{!! $D->text('contact_note') !!}</p><a class="btn big" data-slot="contact_link" href="{{ $D->href('contact_link') }}"@if ($D->external('contact_link')) target="_blank" rel="noopener"@endif>ابعت على واتساب</a></div></section>@endif
</main>
<footer class="f2"><b>{{ $project->name }}</b><span>كل الحقوق محفوظة</span></footer>
