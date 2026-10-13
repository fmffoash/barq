{{-- تصميم Claude Design (2026-10-13) — 02-medical/04-orthopedics.html. اتحوّل من HTML جاهز (convert98): الشكل ثابت والمحتوى من خانات
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

body{--hf:var(--site-font-heading);--hw:800;--on:var(--site-surface);--rad:18px;--bw:0px;--bc:transparent;--shd:0 18px 40px rgba(0,0,0,.12);--h1:96px;--h2:64px;}

.btn{border-radius:12px;background:var(--site-primary);color:var(--on);font-family:var(--hf);font-weight:700;transition:scale .2s,box-shadow .2s}
.btn:hover{scale:1.04;box-shadow:0 12px 24px rgba(0,0,0,.18)}
.btn.alt{background:var(--site-text);color:var(--site-background)}
.hl{color:var(--site-primary)}
.kicker{color:var(--site-primary);font-weight:700}
.kicker::before{content:"";width:12px;height:12px;border-radius:50%;background:var(--site-primary)}
.ph{background:conic-gradient(from 45deg,var(--site-surface) 25%,color-mix(in srgb,var(--site-primary) 10%,var(--site-surface)) 0 50%,var(--site-surface) 0 75%,color-mix(in srgb,var(--site-primary) 10%,var(--site-surface)) 0) 0 0/40px 40px}
.scene{border-radius:50%;background:var(--site-primary);box-shadow:0 20px 40px rgba(0,0,0,.2);animation:bob 5s ease-in-out infinite}

.shead{margin-bottom:56px}.shead.split{display:flex;align-items:end;justify-content:space-between;gap:24px;flex-wrap:wrap}.shead.split p{max-width:380px}.shead.center p{margin-top:12px}
.ghead{margin-bottom:52px}
.rhead{margin-bottom:56px}
.n2{position:sticky;top:0;z-index:20;background:color-mix(in srgb,var(--site-background) 92%,transparent);backdrop-filter:blur(10px);border-bottom:1px solid var(--bc)}
.n2 .wrap{display:grid;grid-template-columns:1fr auto 1fr;align-items:center;height:92px;gap:24px}.n2 .brand{font-size:30px;text-align:center}.n2 .btn{justify-self:end}
@media (max-width:1100px){.n2 .wrap{grid-template-columns:auto 1fr}.n2 .btn{justify-self:end}}
.hv4{padding:60px 0 100px}.hv4 .wrap{display:grid;grid-template-columns:1fr 540px;gap:48px;align-items:center}
.hv4 .disc{position:relative;width:540px;max-width:100%;aspect-ratio:1;justify-self:center}.hv4 .ring{position:absolute;inset:0;border-radius:50%;border:3px dashed var(--site-primary);animation:spin 30s linear infinite}
.hv4 .round{position:absolute;inset:28px;border-radius:50%}.hv4 .sp{position:absolute;top:-10px;inset-inline-start:-40px}
.a4{background:var(--site-primary);color:var(--on);padding:140px 0;clip-path:polygon(0 6%,100% 0,100% 94%,0 100%)}
.a4 .wrap{display:grid;grid-template-columns:1fr 1.3fr;gap:64px;align-items:center}.a4 h2{font-size:calc(var(--h2) * 1.2)}.a4 p{font-size:22px;line-height:1.95}
.s4 .svc{display:grid;grid-template-columns:repeat(var(--cols),minmax(0,1fr));gap:20px}
.s4 li{background:var(--site-surface);border-radius:var(--rad);border:var(--bw) solid var(--bc);padding:32px 28px 36px;transition:background .25s,color .25s}
.s4 li::before{content:"";display:block;width:48px;height:48px;border-radius:50%;border:8px solid var(--site-primary);margin-bottom:26px}
.s4 li:hover{background:var(--site-text);color:var(--site-background)}.s4 li:hover p{color:color-mix(in srgb,var(--site-background) 75%,transparent)}.s4 h3{font-size:26px;margin-bottom:10px}.s4 p{color:var(--site-muted)}
.g9 .grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:22px;align-items:end}.g9 .it{border-radius:var(--rad);overflow:hidden}
.r4{background:var(--site-text);color:var(--site-background)}.r4 .grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px}
.r4 figure{padding:36px;border-radius:var(--rad);border:2px solid color-mix(in srgb,var(--site-background) 18%,transparent);display:flex;flex-direction:column;justify-content:space-between;gap:28px}
.r4 blockquote{font-size:20px}.r4 figcaption{font-family:var(--hf);font-weight:700;font-size:20px}.r4 figcaption::before{content:"";display:inline-block;width:28px;height:4px;background:var(--site-primary);margin-inline-end:12px;vertical-align:middle}
.c4{padding:110px 0;border-top:1px solid var(--bc)}.c4 .wrap{display:flex;align-items:center;justify-content:space-between;gap:40px;flex-wrap:wrap}.c4 h2{font-size:calc(var(--h2) * 1.3)}.c4 p{font-size:20px;color:var(--site-muted);margin-top:12px}
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
<header class="n2"><div class="wrap"><nav class="links" aria-label="أقسام الصفحة"><a href="#about">مين إحنا</a><a href="#services">خدماتنا</a><a href="#gallery">صور</a><a href="#reviews">آراء</a></nav><a class="brand" href="#top">{{ $project->name }}</a><a class="btn" href="{{ $D->href('contact_link') }}"@if ($D->external('contact_link')) target="_blank" rel="noopener"@endif>احجز دلوقتي</a></div></header>
<main id="page-top">
<section id="hero" class="hv4"><div class="wrap cl"><div><span class="kicker">عظام ومفاصل وإصابات ملاعب</span><h1 data-slot="hero_title">{!! $D->text('hero_title') !!}</h1><p class="sub" data-slot="hero_subtitle">{!! $D->text('hero_subtitle') !!}</p><div class="actions"><a class="btn" data-slot="hero_cta" href="{{ $D->href('hero_cta') }}"@if ($D->external('hero_cta')) target="_blank" rel="noopener"@endif>احجز كشف</a><a class="btn alt" href="#services">شوف العلاج</a></div></div><div class="disc"><div class="ring" aria-hidden="true"></div><div class="frame round"><img class="ph " data-slot="hero_image" src="{{ $D->image('hero_image') }}" alt="{{ $project->name }}" loading="eager" fetchpriority="high"></div><div class="sp"><div class="scene" style="width:230px;height:230px"><div data-scene style="width:202px;height:202px">@include('site.partials.depth.scene', ['sceneKey' => ($sceneOverride ?? null) ?: 'joint'])</div></div></div></div></div></section>
@if ($D->has('about'))<section class="a4" id="about"><div class="wrap cl"><h2 class="" data-slot="about_title">{!! $D->text('about_title') !!}</h2><p data-slot="about_body">{!! $D->text('about_body') !!}</p></div></section>@endif
@if ($D->has('services'))<section class="sec s4" id="services"><div class="wrap"><div class="shead split"><h2 class="" data-slot="services_title">{!! $D->text('services_title') !!}</h2><p class="muted">من أول الكشف لحد آخر جلسة تأهيل.</p></div><ul class="svc cl2" data-slot="services_list" style="--cols:4">@foreach ($D->services() as $e)<li><h3>{{ $e['title'] }}</h3>@if ($e['desc'])<p>{{ $e['desc'] }}</p>@endif</li>@endforeach</ul></div></section>@endif
@if ($D->has('gallery'))<section class="sec g9" id="gallery"><div class="wrap"><div class="ghead "><h2 class="" data-slot="gallery_title">{!! $D->text('gallery_title') !!}</h2></div><div class="grid cl2"><div class="it" style="height:440px"><img class="ph " data-slot="gallery_image_1" src="{{ $D->image('gallery_image_1') }}" alt="{{ $project->name }}" loading="lazy"></div><div class="it" style="height:320px"><img class="ph " data-slot="gallery_image_2" src="{{ $D->image('gallery_image_2') }}" alt="{{ $project->name }}" loading="lazy"></div><div class="it" style="height:440px"><img class="ph " data-slot="gallery_image_3" src="{{ $D->image('gallery_image_3') }}" alt="{{ $project->name }}" loading="lazy"></div><div class="it" style="height:320px"><img class="ph " data-slot="gallery_image_4" src="{{ $D->image('gallery_image_4') }}" alt="{{ $project->name }}" loading="lazy"></div></div></div>@php $more = $D->extraGallery(['gallery_image_1', 'gallery_image_2', 'gallery_image_3', 'gallery_image_4']); @endphp@if ($more)<div class="wrap bq-more-gallery">@foreach ($more as $k => $src)<img class="ph" data-slot="{{ $k }}" src="{{ $src }}" alt="{{ $project->name }}" loading="lazy">@endforeach</div>@endif</section>@endif
@if ($D->has('testimonials'))<section class="sec r4" id="reviews"><div class="wrap"><div class="rhead "><h2 class="" data-slot="testimonials_title">{!! $D->text('testimonials_title') !!}</h2>@if ($D->rating())@include('site.partials.rating-badge', ['badge' => ['rating' => $D->rating()]])@endif</div><div class="rv grid cl">@foreach ($D->testimonials() as $t)<figure data-slot="testimonials_list">@if ($t['stars'])<span class="bq-stars" role="img" aria-label="تقييم {{ $t['stars'] }} من 5">{{ str_repeat('★', $t['stars']) }}</span>@endif<blockquote class="bq-quote">{{ $t['quote'] }}</blockquote><figcaption>{{ $t['name'] }}</figcaption></figure>@endforeach</div></div></section>@endif
@if ($D->has('contact'))<section class="c4" id="contact"><div class="wrap"><div><h2 class="" data-slot="contact_title">{!! $D->text('contact_title') !!}</h2><p data-slot="contact_note">{!! $D->text('contact_note') !!}</p></div><a class="btn big" data-slot="contact_link" href="{{ $D->href('contact_link') }}"@if ($D->external('contact_link')) target="_blank" rel="noopener"@endif>اتصل واحجز</a></div></section>@endif
</main>
<footer class="f1"><div class="wrap"><span>{{ $project->name }}</span><span>كل الحقوق محفوظة</span></div></footer>
