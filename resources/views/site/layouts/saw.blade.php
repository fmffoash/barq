{{-- تصميم Claude Design (2026-10-13) — 09-construction-decor/05-carpentry.html. اتحوّل من HTML جاهز (convert98): الشكل ثابت والمحتوى من خانات
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

body{--hf:var(--site-font-heading);--hw:700;--on:var(--site-surface);--rad:0px;--bw:2px;--bc:color-mix(in srgb,var(--site-text) 18%,transparent);--shd:none;--h1:82px;--h2:56px;}

.btn{background:var(--site-primary);color:var(--on);font-weight:700;clip-path:polygon(10px 0,100% 0,calc(100% - 10px) 100%,0 100%);transition:filter .3s}
.btn:hover{filter:brightness(1.12)}
.btn.alt{background:transparent;color:var(--site-text);box-shadow:inset 0 0 0 2px var(--site-text);clip-path:none}
.hl{color:var(--site-primary)}
.kicker{color:var(--site-primary);font-weight:700;letter-spacing:1px}
.kicker::before{content:"";width:36px;height:3px;background:var(--site-primary)}
.ph{background:repeating-linear-gradient(45deg,var(--site-surface) 0 20px,color-mix(in srgb,var(--site-primary) 12%,var(--site-surface)) 20px 40px)}
.scene{background:var(--site-surface);clip-path:polygon(16% 0,84% 0,100% 16%,100% 84%,84% 100%,16% 100%,0 84%,0 16%)}
.scene-glow{position:absolute;inset:-40px;border-radius:50%;background:radial-gradient(circle,color-mix(in srgb,var(--site-primary) 35%,transparent),transparent 65%);animation:glow 3s ease-in-out infinite;pointer-events:none}

.shead{margin-bottom:56px}.shead.split{display:flex;align-items:end;justify-content:space-between;gap:24px;flex-wrap:wrap}.shead.split p{max-width:380px}.shead.center p{margin-top:12px}
.ghead{margin-bottom:52px}
.rhead{margin-bottom:56px}
.n4{position:absolute;top:0;inset-inline:0;z-index:20}
.n4 .wrap{display:flex;align-items:center;gap:36px;height:96px}.n4 .links{margin-inline-start:auto}.n4 .links a{color:var(--site-text)}
.hv5{position:relative;min-height:860px;display:flex;align-items:flex-end;padding:200px 0 110px;overflow:hidden}
.hv5 .bg{position:absolute;inset:0}.hv5::after{content:"";position:absolute;inset:0;background:linear-gradient(to top,var(--site-background) 10%,color-mix(in srgb,var(--site-background) 60%,transparent) 55%,color-mix(in srgb,var(--site-background) 35%,transparent));pointer-events:none}
.hv5 .wrap{position:relative;z-index:2;width:100%;display:grid;grid-template-columns:1.3fr 1fr;gap:40px;align-items:end}.hv5 .sp{justify-self:start}
.a6 .in{border-inline-start:2px solid var(--site-primary);padding-inline-start:56px;max-width:1040px}.a6 h2{font-size:22px;color:var(--site-primary);margin-bottom:24px}
.a6 p{font-family:var(--hf);font-size:32px;line-height:1.75;font-weight:500}
.s4 .svc{display:grid;grid-template-columns:repeat(var(--cols),minmax(0,1fr));gap:20px}
.s4 li{background:var(--site-surface);border-radius:var(--rad);border:var(--bw) solid var(--bc);padding:32px 28px 36px;transition:background .25s,color .25s}
.s4 li::before{content:"";display:block;width:48px;height:48px;border-radius:50%;border:8px solid var(--site-primary);margin-bottom:26px}
.s4 li:hover{background:var(--site-text);color:var(--site-background)}.s4 li:hover p{color:color-mix(in srgb,var(--site-background) 75%,transparent)}.s4 h3{font-size:26px;margin-bottom:10px}.s4 p{color:var(--site-muted)}
.g1 .grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));grid-auto-rows:260px;gap:18px}.g1 .ph{border-radius:var(--rad)}.g1 .ph:first-child{grid-column:span 2;grid-row:span 2}
.r5{border-top:1px solid var(--bc)}.r5 .list{display:grid;gap:56px}.r5 figure{max-width:840px}.r5 figure:nth-child(2){margin-inline-start:auto;text-align:end}
.r5 blockquote{font-family:var(--hf);font-size:30px;line-height:1.7;font-weight:500}.r5 blockquote::before{content:"«"}.r5 blockquote::after{content:"»"}.r5 figcaption{margin-top:14px;color:var(--site-primary);font-weight:700}
.c5{padding:140px 0;position:relative;overflow:hidden}.c5::before{content:"";position:absolute;left:50%;top:50%;width:700px;height:700px;margin:-350px 0 0 -350px;border-radius:50%;background:radial-gradient(circle,color-mix(in srgb,var(--site-primary) 25%,transparent),transparent 65%);animation:glow 4s ease-in-out infinite;pointer-events:none}
.c5 .wrap{position:relative}.c5 h2{font-size:calc(var(--h2) * 1.25)}.c5 p{font-size:21px;color:var(--site-muted);margin:18px auto 42px;max-width:600px}
.f3{padding:40px 0 30px;overflow:hidden}.f3 .big{font-family:var(--hf);font-weight:var(--hw);font-size:130px;line-height:1.1;color:color-mix(in srgb,var(--site-text) 8%,transparent);white-space:nowrap}
.f3 .row{display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap;color:var(--site-muted);font-size:15px;border-top:1px solid var(--bc);padding-top:22px}

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
<header class="n4"><div class="wrap"><a class="brand" href="#top">{{ $project->name }}</a><nav class="links" aria-label="أقسام الصفحة"><a href="#about">مين إحنا</a><a href="#services">خدماتنا</a><a href="#gallery">صور</a><a href="#reviews">آراء</a></nav><a class="btn" href="{{ $D->href('contact_link') }}"@if ($D->external('contact_link')) target="_blank" rel="noopener"@endif>تواصل</a></div></header>
<main id="page-top">
<section id="hero" class="hv5"><div class="bg"><img class="ph " data-slot="hero_image" src="{{ $D->image('hero_image') }}" alt="{{ $project->name }}" loading="eager" fetchpriority="high"></div><div class="wrap cl"><div><span class="kicker">نجارة وأثاث حسب الطلب</span><h1 data-slot="hero_title">{!! $D->text('hero_title') !!}</h1><p class="sub" data-slot="hero_subtitle">{!! $D->text('hero_subtitle') !!}</p><div class="actions"><a class="btn" data-slot="hero_cta" href="{{ $D->href('hero_cta') }}"@if ($D->external('hero_cta')) target="_blank" rel="noopener"@endif>ابعت تصميمك</a><a class="btn alt" href="#services">شغلنا</a></div></div><div class="sp"><div class="scene" style="width:320px;height:320px"><div class="scene-glow" aria-hidden="true"></div><div data-scene style="width:292px;height:292px">@include('site.partials.depth.scene', ['sceneKey' => ($sceneOverride ?? null) ?: 'saw'])</div></div></div></div></section>
@if ($D->has('about'))<section class="sec a6" id="about"><div class="wrap"><div class="in"><h2 class="" data-slot="about_title">{!! $D->text('about_title') !!}</h2><p data-slot="about_body">{!! $D->text('about_body') !!}</p></div></div></section>@endif
@if ($D->has('services'))<section class="sec s4" id="services"><div class="wrap"><div class="shead split"><h2 class="" data-slot="services_title">{!! $D->text('services_title') !!}</h2></div><ul class="svc cl2" data-slot="services_list" style="--cols:3">@foreach ($D->services() as $e)<li><h3>{{ $e['title'] }}</h3>@if ($e['desc'])<p>{{ $e['desc'] }}</p>@endif</li>@endforeach</ul></div></section>@endif
@if ($D->has('gallery'))<section class="sec g1" id="gallery"><div class="wrap"><div class="ghead "><h2 class="" data-slot="gallery_title">{!! $D->text('gallery_title') !!}</h2></div><div class="grid cl2"><img class="ph " data-slot="gallery_image_1" src="{{ $D->image('gallery_image_1') }}" alt="{{ $project->name }}" loading="lazy"><img class="ph " data-slot="gallery_image_2" src="{{ $D->image('gallery_image_2') }}" alt="{{ $project->name }}" loading="lazy"><img class="ph " data-slot="gallery_image_3" src="{{ $D->image('gallery_image_3') }}" alt="{{ $project->name }}" loading="lazy"><img class="ph " data-slot="gallery_image_4" src="{{ $D->image('gallery_image_4') }}" alt="{{ $project->name }}" loading="lazy"><img class="ph " data-slot="gallery_image_5" src="{{ $D->image('gallery_image_5') }}" alt="{{ $project->name }}" loading="lazy"></div></div>@php $more = $D->extraGallery(['gallery_image_1', 'gallery_image_2', 'gallery_image_3', 'gallery_image_4', 'gallery_image_5']); @endphp@if ($more)<div class="wrap bq-more-gallery">@foreach ($more as $k => $src)<img class="ph" data-slot="{{ $k }}" src="{{ $src }}" alt="{{ $project->name }}" loading="lazy">@endforeach</div>@endif</section>@endif
@if ($D->has('testimonials'))<section class="sec r5" id="reviews"><div class="wrap"><div class="rhead "><h2 class="" data-slot="testimonials_title">{!! $D->text('testimonials_title') !!}</h2>@if ($D->rating())@include('site.partials.rating-badge', ['badge' => ['rating' => $D->rating()]])@endif</div><div class="rv list cl">@foreach ($D->testimonials() as $t)<figure data-slot="testimonials_list">@if ($t['stars'])<span class="bq-stars" role="img" aria-label="تقييم {{ $t['stars'] }} من 5">{{ str_repeat('★', $t['stars']) }}</span>@endif<blockquote class="bq-quote">{{ $t['quote'] }}</blockquote><figcaption>{{ $t['name'] }}</figcaption></figure>@endforeach</div></div></section>@endif
@if ($D->has('contact'))<section class="c5" id="contact"><div class="wrap center"><h2 class="" data-slot="contact_title">{!! $D->text('contact_title') !!}</h2><p data-slot="contact_note">{!! $D->text('contact_note') !!}</p><a class="btn big" data-slot="contact_link" href="{{ $D->href('contact_link') }}"@if ($D->external('contact_link')) target="_blank" rel="noopener"@endif>ابعت على واتساب</a></div></section>@endif
</main>
<footer class="f3"><div class="wrap"><div class="big" aria-hidden="true">{{ $project->name }}</div><div class="row"><span>{{ $project->name }}</span><span>كل الحقوق محفوظة</span></div></div></footer>
