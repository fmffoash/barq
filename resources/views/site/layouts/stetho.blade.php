{{-- تصميم Claude Design (2026-10-13) — 02-medical/03-internal-medicine.html. اتحوّل من HTML جاهز (convert98): الشكل ثابت والمحتوى من خانات
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

body{--hf:var(--site-font-heading);--hw:800;--on:var(--site-surface);--rad:4px;--bw:1px;--bc:color-mix(in srgb,var(--site-text) 14%,transparent);--shd:none;--h1:60px;--h2:44px;}

.btn{border-radius:4px;background:var(--site-primary);color:var(--on);transition:background .3s,color .3s}
.btn:hover{background:var(--site-text);color:var(--site-background)}
.btn.alt{background:transparent;color:var(--site-text);box-shadow:inset 0 0 0 1px var(--site-text)}
.hl{color:var(--site-primary)}
.kicker{color:var(--site-primary);font-weight:600}
.kicker::before{content:"";width:28px;height:2px;background:var(--site-primary)}
.ph{background:linear-gradient(color-mix(in srgb,var(--site-text) 6%,transparent) 1px,transparent 1px) 0 0/32px 32px,linear-gradient(90deg,color-mix(in srgb,var(--site-text) 6%,transparent) 1px,transparent 1px) 0 0/32px 32px,var(--site-surface);outline:1px solid var(--bc);outline-offset:-1px}
.scene{border-radius:4px;background:var(--site-surface);border:1px solid var(--bc)}
.scene::before{content:"";position:absolute;inset:10px;border:1px dashed color-mix(in srgb,var(--site-primary) 35%,transparent);animation:fade 4s ease-in-out infinite}

.shead{margin-bottom:56px}.shead.split{display:flex;align-items:end;justify-content:space-between;gap:24px;flex-wrap:wrap}.shead.split p{max-width:380px}.shead.center p{margin-top:12px}
.ghead{margin-bottom:52px}
.rhead{margin-bottom:56px}
.n5{position:sticky;top:0;z-index:20}
.n5 .top{background:var(--site-primary);color:var(--on);font-size:14px}.n5 .top .wrap{display:flex;justify-content:space-between;gap:20px;height:40px;align-items:center}
.n5 .main{background:var(--site-surface);border-bottom:1px solid var(--bc)}.n5 .main .wrap{display:flex;align-items:center;gap:36px;height:80px}.n5 .links{margin-inline-start:auto}
.hv7{padding:80px 0 130px}.hv7 .wrap{display:grid;grid-template-columns:1fr 1.05fr;gap:64px;align-items:center}
.hv7 .vis{position:relative}.hv7 .outline{position:absolute;top:0;right:0;width:calc(100% - 40px);height:540px;border:2px solid var(--site-primary);border-radius:var(--rad)}
.hv7 .pic{position:relative;margin-top:40px;width:calc(100% - 40px);height:540px}.hv7 .sp{position:absolute;bottom:-70px;right:-10px;padding:14px}
.a2 .wrap{max-width:900px}.a2 p{font-size:22px;line-height:2.1;margin-top:28px;color:var(--site-muted)}
.a2 .orn{display:flex;align-items:center;justify-content:center;gap:16px;margin-bottom:26px}.a2 .orn::before,.a2 .orn::after{content:"";width:80px;height:1px;background:var(--site-primary)}
.a2 .orn i{width:10px;height:10px;rotate:45deg;border:1px solid var(--site-primary);display:block}
.s5 li{display:grid;grid-template-columns:90px 1fr 1.2fr;gap:24px;align-items:center;padding:30px 0;border-bottom:1px solid color-mix(in srgb,var(--site-text) 14%,transparent);counter-increment:s;transition:padding .3s,background .3s}
.s5 .svc{border-top:1px solid color-mix(in srgb,var(--site-text) 14%,transparent)}.s5 li:hover{padding-inline-start:16px;background:color-mix(in srgb,var(--site-primary) 7%,transparent)}
.s5 li::before{content:counter(s,arabic-indic);font-family:var(--hf);font-size:36px;font-weight:var(--hw);color:var(--site-primary)}.s5 h3{font-size:29px}.s5 p{color:var(--site-muted)}
@media (max-width:1100px){.s5 li{grid-template-columns:60px 1fr}.s5 li p{grid-column:2}}
.g7 .grid{display:grid;grid-template-columns:2fr 1fr;grid-template-rows:260px 260px;gap:20px}.g7 .ph{border-radius:var(--rad)}.g7 .ph:first-child{grid-row:span 2}
.r2{border-top:1px solid var(--bc)}.r2 .grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr))}
.r2 figure{padding:0 40px;text-align:center;border-inline-start:1px solid var(--bc)}.r2 figure:first-child{border:0}
.r2 figure::before{content:"\201D";display:block;font-family:var(--hf);font-size:90px;line-height:.6;color:var(--site-primary);margin-bottom:18px}
.r2 blockquote{font-family:var(--hf);font-size:24px;line-height:1.7;margin-bottom:20px}.r2 figcaption{color:var(--site-primary);font-size:15px;letter-spacing:1px}
.c2{padding:60px 0 140px}.c2 .box{border:1px solid var(--site-primary);outline:1px solid color-mix(in srgb,var(--site-primary) 40%,transparent);outline-offset:10px;padding:90px 48px;background:var(--site-surface)}
.c2 p{color:var(--site-muted);font-size:20px;margin:20px auto 40px;max-width:560px}
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
<header class="n5"><div class="top"><div class="wrap"><span>استشاري أمراض الباطنة</span><span>اتصل بينا في أي وقت</span></div></div><div class="main"><div class="wrap"><a class="brand" href="#top">{{ $project->name }}</a><nav class="links" aria-label="أقسام الصفحة"><a href="#about">مين إحنا</a><a href="#services">خدماتنا</a><a href="#gallery">صور</a><a href="#reviews">آراء</a></nav><a class="btn" href="{{ $D->href('contact_link') }}"@if ($D->external('contact_link')) target="_blank" rel="noopener"@endif>احجز موعد</a></div></div></header>
<main id="page-top">
<section id="hero" class="hv7"><div class="wrap cl"><div><span class="kicker">استشاري أمراض الباطنة</span><h1 data-slot="hero_title">{!! $D->text('hero_title') !!}</h1><p class="sub" data-slot="hero_subtitle">{!! $D->text('hero_subtitle') !!}</p><div class="actions"><a class="btn" data-slot="hero_cta" href="{{ $D->href('hero_cta') }}"@if ($D->external('hero_cta')) target="_blank" rel="noopener"@endif>احجز كشف</a><a class="btn alt" href="#services">مجالات العلاج</a></div></div><div class="vis"><div class="outline" aria-hidden="true"></div><div class="frame pic"><img class="ph " data-slot="hero_image" src="{{ $D->image('hero_image') }}" alt="{{ $project->name }}" loading="eager" fetchpriority="high"></div><div class="sp card"><div class="scene" style="width:200px;height:200px"><div data-scene style="width:172px;height:172px">@include('site.partials.depth.scene', ['sceneKey' => ($sceneOverride ?? null) ?: 'stetho'])</div></div></div></div></div></section>
@if ($D->has('about'))<section class="sec a2" id="about"><div class="wrap center"><div class="orn" aria-hidden="true"><i></i></div><h2 class="" data-slot="about_title">{!! $D->text('about_title') !!}</h2><p data-slot="about_body">{!! $D->text('about_body') !!}</p></div></section>@endif
@if ($D->has('services'))<section class="sec s5" id="services"><div class="wrap"><div class="shead split"><h2 class="" data-slot="services_title">{!! $D->text('services_title') !!}</h2></div><ul class="svc cl2" data-slot="services_list" style="--cols:3">@foreach ($D->services() as $e)<li><h3>{{ $e['title'] }}</h3>@if ($e['desc'])<p>{{ $e['desc'] }}</p>@endif</li>@endforeach</ul></div></section>@endif
@if ($D->has('gallery'))<section class="sec g7" id="gallery"><div class="wrap"><div class="ghead "><h2 class="" data-slot="gallery_title">{!! $D->text('gallery_title') !!}</h2></div><div class="grid cl"><img class="ph " data-slot="gallery_image_1" src="{{ $D->image('gallery_image_1') }}" alt="{{ $project->name }}" loading="lazy"><img class="ph " data-slot="gallery_image_2" src="{{ $D->image('gallery_image_2') }}" alt="{{ $project->name }}" loading="lazy"><img class="ph " data-slot="gallery_image_3" src="{{ $D->image('gallery_image_3') }}" alt="{{ $project->name }}" loading="lazy"></div></div>@php $more = $D->extraGallery(['gallery_image_1', 'gallery_image_2', 'gallery_image_3']); @endphp@if ($more)<div class="wrap bq-more-gallery">@foreach ($more as $k => $src)<img class="ph" data-slot="{{ $k }}" src="{{ $src }}" alt="{{ $project->name }}" loading="lazy">@endforeach</div>@endif</section>@endif
@if ($D->has('testimonials'))<section class="sec r2" id="reviews"><div class="wrap"><div class="rhead center"><h2 class="" data-slot="testimonials_title">{!! $D->text('testimonials_title') !!}</h2>@if ($D->rating())@include('site.partials.rating-badge', ['badge' => ['rating' => $D->rating()]])@endif</div><div class="rv grid cl">@foreach ($D->testimonials() as $t)<figure data-slot="testimonials_list">@if ($t['stars'])<span class="bq-stars" role="img" aria-label="تقييم {{ $t['stars'] }} من 5">{{ str_repeat('★', $t['stars']) }}</span>@endif<blockquote class="bq-quote">{{ $t['quote'] }}</blockquote><figcaption>{{ $t['name'] }}</figcaption></figure>@endforeach</div></div></section>@endif
@if ($D->has('contact'))<section class="c2" id="contact"><div class="wrap"><div class="box center"><h2 class="" data-slot="contact_title">{!! $D->text('contact_title') !!}</h2><p data-slot="contact_note">{!! $D->text('contact_note') !!}</p><a class="btn big" data-slot="contact_link" href="{{ $D->href('contact_link') }}"@if ($D->external('contact_link')) target="_blank" rel="noopener"@endif>اتصل بالعيادة</a></div></div></section>@endif
</main>
<footer class="f3"><div class="wrap"><div class="big" aria-hidden="true">{{ $project->name }}</div><div class="row"><span>{{ $project->name }}</span><span>كل الحقوق محفوظة</span></div></div></footer>
