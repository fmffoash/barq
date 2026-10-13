{{-- تصميم Claude Design (2026-10-13) — 02-medical/09-dermatology.html. اتحوّل من HTML جاهز (convert98): الشكل ثابت والمحتوى من خانات
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

body{--hf:var(--site-font-heading);--hw:700;--on:var(--site-surface);--rad:0px;--bw:1px;--bc:color-mix(in srgb,var(--site-primary) 35%,transparent);--shd:none;--h1:80px;--h2:52px;}

.btn{border:1px solid var(--site-primary);background:var(--site-primary);color:var(--on);letter-spacing:.5px;transition:background .4s,color .4s}
.btn:hover{background:transparent;color:var(--site-primary)}
.btn.alt{background:transparent;color:var(--site-primary)}
.btn.alt:hover{background:var(--site-primary);color:var(--on)}
.hl{color:var(--site-primary)}
.kicker{color:var(--site-primary);letter-spacing:1px}
.kicker::before,.kicker::after{content:"";width:44px;height:1px;background:var(--site-primary)}
.ph{background:radial-gradient(circle at 50% 40%,color-mix(in srgb,var(--site-primary) 14%,var(--site-surface)),var(--site-surface));outline:1px solid color-mix(in srgb,var(--site-primary) 30%,transparent);outline-offset:-1px}
.scene{border-radius:50%;border:1px solid var(--site-primary);background:var(--site-background)}
.scene::before{content:"";position:absolute;inset:-14px;border-radius:50%;border:1px dashed color-mix(in srgb,var(--site-primary) 50%,transparent);animation:spin 40s linear infinite}

.shead{margin-bottom:56px}.shead.split{display:flex;align-items:end;justify-content:space-between;gap:24px;flex-wrap:wrap}.shead.split p{max-width:380px}.shead.center p{margin-top:12px}
.ghead{margin-bottom:52px}
.rhead{margin-bottom:56px}
.n2{position:sticky;top:0;z-index:20;background:color-mix(in srgb,var(--site-background) 92%,transparent);backdrop-filter:blur(10px);border-bottom:1px solid var(--bc)}
.n2 .wrap{display:grid;grid-template-columns:1fr auto 1fr;align-items:center;height:92px;gap:24px}.n2 .brand{font-size:30px;text-align:center}.n2 .btn{justify-self:end}
@media (max-width:1100px){.n2 .wrap{grid-template-columns:auto 1fr}.n2 .btn{justify-self:end}}
.hv2{padding:90px 0 0}.hv2 h1{max-width:960px;margin:0 auto}.hv2 .sub{margin-inline:auto;max-width:660px}
.hv2 .stage{position:relative;margin-top:72px;padding-bottom:150px}.hv2 .wide{height:540px}.hv2 .sp{position:absolute;left:50%;bottom:0;translate:-50% 0}
.a2 .wrap{max-width:900px}.a2 p{font-size:22px;line-height:2.1;margin-top:28px;color:var(--site-muted)}
.a2 .orn{display:flex;align-items:center;justify-content:center;gap:16px;margin-bottom:26px}.a2 .orn::before,.a2 .orn::after{content:"";width:80px;height:1px;background:var(--site-primary)}
.a2 .orn i{width:10px;height:10px;rotate:45deg;border:1px solid var(--site-primary);display:block}
.s2{background:var(--site-surface)}.s2 .svc{max-width:900px;margin:0 auto;display:grid;gap:34px}
.s2 li{display:grid;grid-template-columns:auto 1fr;gap:0 24px;align-items:baseline}.s2 h3{font-size:29px}
.s2 li::after{content:"";grid-column:2;grid-row:1;border-bottom:1px dotted color-mix(in srgb,var(--site-primary) 55%,transparent);translate:0 -8px}.s2 p{grid-column:1/-1;color:var(--site-muted);margin-top:4px}
.g2 .grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:28px}.g2 .ph{height:520px;border-radius:var(--rad)}.g2 .ph:nth-child(2){margin-top:60px}
.r5{border-top:1px solid var(--bc)}.r5 .list{display:grid;gap:56px}.r5 figure{max-width:840px}.r5 figure:nth-child(2){margin-inline-start:auto;text-align:end}
.r5 blockquote{font-family:var(--hf);font-size:30px;line-height:1.7;font-weight:500}.r5 blockquote::before{content:"«"}.r5 blockquote::after{content:"»"}.r5 figcaption{margin-top:14px;color:var(--site-primary);font-weight:700}
.c2{padding:60px 0 140px}.c2 .box{border:1px solid var(--site-primary);outline:1px solid color-mix(in srgb,var(--site-primary) 40%,transparent);outline-offset:10px;padding:90px 48px;background:var(--site-surface)}
.c2 p{color:var(--site-muted);font-size:20px;margin:20px auto 40px;max-width:560px}
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
<header class="n2"><div class="wrap"><nav class="links" aria-label="أقسام الصفحة"><a href="#about">مين إحنا</a><a href="#services">خدماتنا</a><a href="#gallery">صور</a><a href="#reviews">آراء</a></nav><a class="brand" href="#top">{{ $project->name }}</a><a class="btn" href="{{ $D->href('contact_link') }}"@if ($D->external('contact_link')) target="_blank" rel="noopener"@endif>احجزي</a></div></header>
<main id="page-top">
<section id="hero" class="hv2"><div class="wrap"><div class="center"><span class="kicker">جلدية وتجميل وليزر</span><h1 data-slot="hero_title">{!! $D->text('hero_title') !!}</h1><p class="sub" data-slot="hero_subtitle">{!! $D->text('hero_subtitle') !!}</p><div class="actions" style="justify-content:center"><a class="btn" data-slot="hero_cta" href="{{ $D->href('hero_cta') }}"@if ($D->external('hero_cta')) target="_blank" rel="noopener"@endif>احجزي استشارة</a><a class="btn alt" href="#services">خدماتنا</a></div></div><div class="stage"><div class="frame wide"><img class="ph " data-slot="hero_image" src="{{ $D->image('hero_image') }}" alt="{{ $project->name }}" loading="eager" fetchpriority="high"></div><div class="sp"><div class="scene" style="width:290px;height:290px"><div data-scene style="width:262px;height:262px">@include('site.partials.depth.scene', ['sceneKey' => ($sceneOverride ?? null) ?: 'drop'])</div></div></div></div></div></section>
@if ($D->has('about'))<section class="sec a2" id="about"><div class="wrap center"><div class="orn" aria-hidden="true"><i></i></div><h2 class="" data-slot="about_title">{!! $D->text('about_title') !!}</h2><p data-slot="about_body">{!! $D->text('about_body') !!}</p></div></section>@endif
@if ($D->has('services'))<section class="sec s2" id="services"><div class="wrap"><div class="shead center"><h2 class="" data-slot="services_title">{!! $D->text('services_title') !!}</h2></div><ul class="svc cl2" data-slot="services_list" style="--cols:3">@foreach ($D->services() as $e)<li><h3>{{ $e['title'] }}</h3>@if ($e['desc'])<p>{{ $e['desc'] }}</p>@endif</li>@endforeach</ul></div></section>@endif
@if ($D->has('gallery'))<section class="sec g2" id="gallery"><div class="wrap"><div class="ghead center"><h2 class="" data-slot="gallery_title">{!! $D->text('gallery_title') !!}</h2></div><div class="grid cl"><img class="ph " data-slot="gallery_image_1" src="{{ $D->image('gallery_image_1') }}" alt="{{ $project->name }}" loading="lazy"><img class="ph " data-slot="gallery_image_2" src="{{ $D->image('gallery_image_2') }}" alt="{{ $project->name }}" loading="lazy"><img class="ph " data-slot="gallery_image_3" src="{{ $D->image('gallery_image_3') }}" alt="{{ $project->name }}" loading="lazy"></div></div>@php $more = $D->extraGallery(['gallery_image_1', 'gallery_image_2', 'gallery_image_3']); @endphp@if ($more)<div class="wrap bq-more-gallery">@foreach ($more as $k => $src)<img class="ph" data-slot="{{ $k }}" src="{{ $src }}" alt="{{ $project->name }}" loading="lazy">@endforeach</div>@endif</section>@endif
@if ($D->has('testimonials'))<section class="sec r5" id="reviews"><div class="wrap"><div class="rhead "><h2 class="" data-slot="testimonials_title">{!! $D->text('testimonials_title') !!}</h2>@if ($D->rating())@include('site.partials.rating-badge', ['badge' => ['rating' => $D->rating()]])@endif</div><div class="rv list cl">@foreach ($D->testimonials() as $t)<figure data-slot="testimonials_list">@if ($t['stars'])<span class="bq-stars" role="img" aria-label="تقييم {{ $t['stars'] }} من 5">{{ str_repeat('★', $t['stars']) }}</span>@endif<blockquote class="bq-quote">{{ $t['quote'] }}</blockquote><figcaption>{{ $t['name'] }}</figcaption></figure>@endforeach</div></div></section>@endif
@if ($D->has('contact'))<section class="c2" id="contact"><div class="wrap"><div class="box center"><h2 class="" data-slot="contact_title">{!! $D->text('contact_title') !!}</h2><p data-slot="contact_note">{!! $D->text('contact_note') !!}</p><a class="btn big" data-slot="contact_link" href="{{ $D->href('contact_link') }}"@if ($D->external('contact_link')) target="_blank" rel="noopener"@endif>ابعتي على واتساب</a></div></div></section>@endif
</main>
<footer class="f2"><b>{{ $project->name }}</b><span>كل الحقوق محفوظة</span></footer>
