{{-- تصميم Claude Design (2026-10-13) — 18-photo-video/03-aerial.html. اتحوّل من HTML جاهز (convert98): الشكل ثابت والمحتوى من خانات
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

body{--hf:var(--site-font-heading);--hw:800;--on:var(--site-background);--rad:14px;--bw:1px;--bc:color-mix(in srgb,var(--site-primary) 35%,transparent);--shd:0 0 40px color-mix(in srgb,var(--site-primary) 16%,transparent);--h1:72px;--h2:50px;}

.btn{border-radius:10px;background:var(--site-primary);color:var(--on);font-weight:700;transition:box-shadow .3s}
.btn:hover{box-shadow:0 0 30px color-mix(in srgb,var(--site-primary) 55%,transparent)}
.btn.alt{background:transparent;color:var(--site-text);box-shadow:inset 0 0 0 1px var(--site-primary)}
.hl{color:var(--site-primary);text-shadow:0 0 28px color-mix(in srgb,var(--site-primary) 50%,transparent)}
.kicker{color:var(--site-primary);border:1px solid color-mix(in srgb,var(--site-primary) 45%,transparent);padding:5px 14px;border-radius:999px;font-weight:600}
.kicker::before{content:"";width:8px;height:8px;border-radius:50%;background:var(--site-primary);animation:blink 1.6s steps(2) infinite}
.ph{background:linear-gradient(color-mix(in srgb,var(--site-primary) 10%,transparent) 1px,transparent 1px) 0 0/40px 40px,linear-gradient(90deg,color-mix(in srgb,var(--site-primary) 10%,transparent) 1px,transparent 1px) 0 0/40px 40px,var(--site-surface)}
.scene{border-radius:50%;background:var(--site-surface);box-shadow:0 0 50px color-mix(in srgb,var(--site-primary) 20%,transparent)}
.scene::before{content:"";position:absolute;inset:-4px;border-radius:50%;background:conic-gradient(var(--site-primary),transparent 30%,transparent 70%,var(--site-primary));-webkit-mask:radial-gradient(farthest-side,transparent calc(100% - 3px),rgba(0,0,0,1) calc(100% - 2px));mask:radial-gradient(farthest-side,transparent calc(100% - 3px),rgba(0,0,0,1) calc(100% - 2px));animation:spin 6s linear infinite}

.shead{margin-bottom:56px}.shead.split{display:flex;align-items:end;justify-content:space-between;gap:24px;flex-wrap:wrap}.shead.split p{max-width:380px}.shead.center p{margin-top:12px}
.ghead{margin-bottom:52px}
.rhead{margin-bottom:56px}
.n1{position:sticky;top:0;z-index:20;background:color-mix(in srgb,var(--site-background) 92%,transparent);backdrop-filter:blur(10px);border-bottom:1px solid color-mix(in srgb,var(--site-text) 10%,transparent)}
.n1 .wrap{display:flex;align-items:center;gap:36px;height:84px}.n1 .links{margin-inline-start:auto}
.hv7{padding:80px 0 130px}.hv7 .wrap{display:grid;grid-template-columns:1fr 1.05fr;gap:64px;align-items:center}
.hv7 .vis{position:relative}.hv7 .outline{position:absolute;top:0;right:0;width:calc(100% - 40px);height:540px;border:2px solid var(--site-primary);border-radius:var(--rad)}
.hv7 .pic{position:relative;margin-top:40px;width:calc(100% - 40px);height:540px}.hv7 .sp{position:absolute;bottom:-70px;right:-10px;padding:14px}
.a6 .in{border-inline-start:2px solid var(--site-primary);padding-inline-start:56px;max-width:1040px}.a6 h2{font-size:22px;color:var(--site-primary);margin-bottom:24px}
.a6 p{font-family:var(--hf);font-size:32px;line-height:1.75;font-weight:500}
.s4 .svc{display:grid;grid-template-columns:repeat(var(--cols),minmax(0,1fr));gap:20px}
.s4 li{background:var(--site-surface);border-radius:var(--rad);border:var(--bw) solid var(--bc);padding:32px 28px 36px;transition:background .25s,color .25s}
.s4 li::before{content:"";display:block;width:48px;height:48px;border-radius:50%;border:8px solid var(--site-primary);margin-bottom:26px}
.s4 li:hover{background:var(--site-text);color:var(--site-background)}.s4 li:hover p{color:color-mix(in srgb,var(--site-background) 75%,transparent)}.s4 h3{font-size:26px;margin-bottom:10px}.s4 p{color:var(--site-muted)}
.g2 .grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:28px}.g2 .ph{height:520px;border-radius:var(--rad)}.g2 .ph:nth-child(2){margin-top:60px}
.r6 .grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:28px}.r6 figure{background:var(--site-surface);border-radius:var(--rad);border:var(--bw) solid var(--bc);box-shadow:var(--shd);padding:36px}
.r6 blockquote{font-size:19px;margin-bottom:22px}.r6 figcaption{display:flex;align-items:center;gap:14px;font-family:var(--hf);font-weight:700}
.r6 .av{width:46px;height:46px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:color-mix(in srgb,var(--site-primary) 18%,var(--site-surface));color:var(--site-primary)}
.c4{padding:110px 0;border-top:1px solid var(--bc)}.c4 .wrap{display:flex;align-items:center;justify-content:space-between;gap:40px;flex-wrap:wrap}.c4 h2{font-size:calc(var(--h2) * 1.3)}.c4 p{font-size:20px;color:var(--site-muted);margin-top:12px}
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
<header class="n1"><div class="wrap"><a class="brand" href="#top">{{ $project->name }}</a><nav class="links" aria-label="أقسام الصفحة"><a href="#about">مين إحنا</a><a href="#services">خدماتنا</a><a href="#gallery">صور</a><a href="#reviews">آراء</a></nav><a class="btn" href="{{ $D->href('contact_link') }}"@if ($D->external('contact_link')) target="_blank" rel="noopener"@endif>تواصل</a></div></header>
<main id="page-top">
<section id="hero" class="hv7"><div class="wrap cl"><div><span class="kicker">تصوير بالدرون مرخص</span><h1 data-slot="hero_title">{!! $D->text('hero_title') !!}</h1><p class="sub" data-slot="hero_subtitle">{!! $D->text('hero_subtitle') !!}</p><div class="actions"><a class="btn" data-slot="hero_cta" href="{{ $D->href('hero_cta') }}"@if ($D->external('hero_cta')) target="_blank" rel="noopener"@endif>اطلب تصوير</a><a class="btn alt" href="#services">الخدمات</a></div></div><div class="vis"><div class="outline" aria-hidden="true"></div><div class="frame pic"><img class="ph " data-slot="hero_image" src="{{ $D->image('hero_image') }}" alt="{{ $project->name }}" loading="eager" fetchpriority="high"></div><div class="sp card"><div class="scene" style="width:200px;height:200px"><div data-scene style="width:172px;height:172px">@include('site.partials.depth.scene', ['sceneKey' => ($sceneOverride ?? null) ?: 'drone'])</div></div></div></div></div></section>
@if ($D->has('about'))<section class="sec a6" id="about"><div class="wrap"><div class="in"><h2 class="" data-slot="about_title">{!! $D->text('about_title') !!}</h2><p data-slot="about_body">{!! $D->text('about_body') !!}</p></div></div></section>@endif
@if ($D->has('services'))<section class="sec s4" id="services"><div class="wrap"><div class="shead split"><h2 class="" data-slot="services_title">{!! $D->text('services_title') !!}</h2></div><ul class="svc cl2" data-slot="services_list" style="--cols:3">@foreach ($D->services() as $e)<li><h3>{{ $e['title'] }}</h3>@if ($e['desc'])<p>{{ $e['desc'] }}</p>@endif</li>@endforeach</ul></div></section>@endif
@if ($D->has('gallery'))<section class="sec g2" id="gallery"><div class="wrap"><div class="ghead center"><h2 class="" data-slot="gallery_title">{!! $D->text('gallery_title') !!}</h2></div><div class="grid cl"><img class="ph " data-slot="gallery_image_1" src="{{ $D->image('gallery_image_1') }}" alt="{{ $project->name }}" loading="lazy"><img class="ph " data-slot="gallery_image_2" src="{{ $D->image('gallery_image_2') }}" alt="{{ $project->name }}" loading="lazy"><img class="ph " data-slot="gallery_image_3" src="{{ $D->image('gallery_image_3') }}" alt="{{ $project->name }}" loading="lazy"></div></div>@php $more = $D->extraGallery(['gallery_image_1', 'gallery_image_2', 'gallery_image_3']); @endphp@if ($more)<div class="wrap bq-more-gallery">@foreach ($more as $k => $src)<img class="ph" data-slot="{{ $k }}" src="{{ $src }}" alt="{{ $project->name }}" loading="lazy">@endforeach</div>@endif</section>@endif
@if ($D->has('testimonials'))<section class="sec r6" id="reviews"><div class="wrap"><div class="rhead center"><h2 class="" data-slot="testimonials_title">{!! $D->text('testimonials_title') !!}</h2>@if ($D->rating())@include('site.partials.rating-badge', ['badge' => ['rating' => $D->rating()]])@endif</div><div class="rv grid cl">@foreach ($D->testimonials() as $t)<figure data-slot="testimonials_list">@if ($t['stars'])<span class="bq-stars" role="img" aria-label="تقييم {{ $t['stars'] }} من 5">{{ str_repeat('★', $t['stars']) }}</span>@endif<blockquote class="bq-quote">{{ $t['quote'] }}</blockquote><figcaption><span class="av" aria-hidden="true">{{ $t['initial'] }}</span>{{ $t['name'] }}</figcaption></figure>@endforeach</div></div></section>@endif
@if ($D->has('contact'))<section class="c4" id="contact"><div class="wrap"><div><h2 class="" data-slot="contact_title">{!! $D->text('contact_title') !!}</h2><p data-slot="contact_note">{!! $D->text('contact_note') !!}</p></div><a class="btn big" data-slot="contact_link" href="{{ $D->href('contact_link') }}"@if ($D->external('contact_link')) target="_blank" rel="noopener"@endif>ابعت على واتساب</a></div></section>@endif
</main>
<footer class="f2"><b>{{ $project->name }}</b><span>كل الحقوق محفوظة</span></footer>
