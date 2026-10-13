{{-- تصميم Claude Design (2026-10-13) — 01-restaurants/01-cafe.html. اتحوّل من HTML جاهز (convert98): الشكل ثابت والمحتوى من خانات
     الموقع عن طريق App\Support\DesignedPage، والرسمة 3D جوه [data-scene]. ماتعدّلش يدوي من غير ما تراجع الأصل. --}}
@php $D = new \App\Support\DesignedPage($sections, $contactAction ?? null); @endphp
<style>@verbatim

*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;background:var(--site-background);color:var(--site-text);font-family:'Tajawal',sans-serif;font-size:18px;line-height:1.8;overflow-x:hidden}
h1,h2,h3{font-family:'Reem Kufi',sans-serif;line-height:1.25;margin:0}
p{margin:0}
a{color:inherit}
.wrap{max-width:1240px;margin:0 auto;padding:0 48px}
.ph{display:flex;align-items:center;justify-content:center;text-align:center;color:var(--site-muted);font-size:15px;background:repeating-linear-gradient(135deg,color-mix(in srgb,var(--site-primary) 10%,var(--site-surface)) 0 14px,color-mix(in srgb,var(--site-primary) 16%,var(--site-surface)) 14px 28px);overflow:hidden}

/* nav */
.nav{position:sticky;top:0;z-index:10;background:color-mix(in srgb,var(--site-background) 92%,transparent);backdrop-filter:blur(8px);border-bottom:1px solid color-mix(in srgb,var(--site-text) 10%,transparent)}
.nav .wrap{display:flex;align-items:center;gap:40px;height:84px}
.brand{font-family:'Reem Kufi',sans-serif;font-size:28px;font-weight:700;text-decoration:none;color:var(--site-primary)}
.links{display:flex;gap:28px;margin-inline-start:auto;flex-wrap:wrap}
.links a{text-decoration:none;color:var(--site-muted);font-weight:500}
.links a:hover{color:var(--site-primary)}
.btn{display:inline-flex;align-items:center;justify-content:center;min-height:48px;padding:0 28px;border-radius:999px;background:var(--site-primary);color:var(--site-surface);text-decoration:none;font-weight:700;transition:box-shadow .3s,translate .3s}
.btn:hover{translate:0 -2px;box-shadow:0 10px 24px rgba(0,0,0,.15)}
.btn.ghost{background:transparent;color:var(--site-primary);border:2px solid var(--site-primary)}

/* hero */
.hero{padding:72px 0 110px}
.hero .wrap{display:grid;grid-template-columns:1.05fr .95fr;gap:72px;align-items:center}
.eyebrow{display:inline-block;font-size:15px;letter-spacing:.5px;color:var(--site-primary);font-weight:700;margin-bottom:20px}
.hero h1{font-size:76px;font-weight:700}
.hero h1 em{font-style:normal;color:var(--site-primary)}
.hero p{margin-top:24px;font-size:21px;color:var(--site-muted);max-width:520px}
.hero .actions{margin-top:40px;display:flex;gap:16px;flex-wrap:wrap}
.visual{position:relative;justify-self:center;width:100%;max-width:500px}
.arch{width:100%;aspect-ratio:500/620;border-radius:250px 250px 24px 24px;box-shadow:0 30px 60px rgba(0,0,0,.12)}
.scene-spot{position:absolute;inset-inline-start:-90px;bottom:-60px;width:260px;height:260px}
.scene-spot::before{content:"";position:absolute;inset:-16px;border-radius:50%;background:var(--site-surface);box-shadow:0 18px 40px rgba(0,0,0,.12)}
[data-scene]{position:relative;width:260px;height:260px;border-radius:50%}
.steam{position:absolute;top:-70px;inset-inline-end:80px;display:flex;gap:14px}
.steam span{display:block;width:6px;height:60px;border-radius:6px;background:color-mix(in srgb,var(--site-primary) 35%,transparent);animation:rise 3.2s ease-in-out infinite;opacity:0}
.steam span:nth-child(2){animation-delay:.8s;height:80px}
.steam span:nth-child(3){animation-delay:1.6s}
@keyframes rise{0%{opacity:0;translate:0 20px;scale:1 .6}40%{opacity:1}100%{opacity:0;translate:0 -40px;scale:1 1.2}}

/* about */
.about{padding:110px 0;background:var(--site-surface)}
.about .wrap{display:grid;grid-template-columns:320px 1fr;gap:80px;align-items:start}
.about h2{font-size:48px}
.about h2::after{content:"";display:block;width:64px;height:4px;border-radius:4px;background:var(--site-primary);margin-top:22px}
.about p{font-size:24px;line-height:2;color:var(--site-text)}

/* services */
.services{padding:110px 0}
.sec-head{display:flex;align-items:end;justify-content:space-between;gap:24px;margin-bottom:56px}
.sec-head h2{font-size:52px}
.sec-head span{color:var(--site-muted)}
.svc{list-style:none;margin:0;padding:0;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:0;border-top:1px solid color-mix(in srgb,var(--site-text) 15%,transparent)}
.svc li{padding:40px 32px 40px 0;border-bottom:1px solid color-mix(in srgb,var(--site-text) 15%,transparent);counter-increment:s;transition:background .3s}
.svc li:hover{background:color-mix(in srgb,var(--site-primary) 6%,transparent)}
.svc li::before{content:"0" counter(s);display:block;font-family:'Reem Kufi',sans-serif;font-size:18px;color:var(--site-primary);margin-bottom:14px}
.svc h3{font-size:26px;margin-bottom:8px}
.svc p{color:var(--site-muted)}

/* gallery */
.gallery{padding:0 0 110px}
.gallery h2{font-size:52px;margin-bottom:48px;text-align:center}
.g-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));grid-auto-rows:220px;gap:20px}
.g-grid .ph{border-radius:20px}
.g-grid .ph:nth-child(1){grid-column:span 2;grid-row:span 2}
.g-grid .ph:nth-child(4){grid-row:span 2}

/* testimonials */
.reviews{padding:110px 0;background:color-mix(in srgb,var(--site-primary) 9%,var(--site-background))}
.reviews h2{font-size:52px;margin-bottom:56px;text-align:center}
.r-list{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:32px}
.r-list figure{margin:0;background:var(--site-surface);padding:40px 36px;border-radius:4px;box-shadow:0 14px 30px rgba(0,0,0,.08);position:relative}
.r-list figure:nth-child(1){rotate:-1.5deg}
.r-list figure:nth-child(3){rotate:1.5deg}
.r-list figure::before{content:"";position:absolute;top:-12px;inset-inline-start:50%;width:90px;height:24px;margin-inline-start:-45px;background:color-mix(in srgb,var(--site-primary) 25%,transparent)}
.r-list blockquote{margin:0 0 24px;font-size:19px}
.r-list figcaption{font-weight:700;color:var(--site-primary)}

/* contact */
.contact{padding:130px 0;text-align:center}
.contact h2{font-size:64px}
.contact p{font-size:21px;color:var(--site-muted);margin:20px auto 40px;max-width:560px}
.contact .btn{min-height:60px;padding:0 44px;font-size:20px}

footer{border-top:1px solid color-mix(in srgb,var(--site-text) 12%,transparent);padding:32px 0;color:var(--site-muted);font-size:15px}
footer .wrap{display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap}

@media (max-width:1100px){
  .hero .wrap,.about .wrap{grid-template-columns:1fr}
  .scene-spot{inset-inline-start:-20px}
  .svc,.r-list{grid-template-columns:repeat(2,minmax(0,1fr))}
  .links{display:none}
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
<header class="nav">
  <div class="wrap">
    <a class="brand" href="#top">ركنة</a>
    <nav class="links" aria-label="أقسام الصفحة">
      <a href="#about">مين إحنا</a>
      <a href="#services">بنقدم إيه</a>
      <a href="#gallery">صور</a>
      <a href="#reviews">آراء</a>
    </nav>
    <a class="btn" href="{{ $D->href('contact_link') }}"@if ($D->external('contact_link')) target="_blank" rel="noopener"@endif>احجز ترابيزتك</a>
  </div>
</header>

<main id="page-top">
  <section id="hero" class="hero">
    <div class="wrap">
      <div>
        <span class="eyebrow">قهوة مختصة وحلويات بيتي</span>
        <h1 data-slot="hero_title">{!! $D->text('hero_title') !!}</h1>
        <p data-slot="hero_subtitle">{!! $D->text('hero_subtitle') !!}</p>
        <div class="actions">
          <a class="btn" data-slot="hero_cta" href="{{ $D->href('hero_cta') }}"@if ($D->external('hero_cta')) target="_blank" rel="noopener"@endif>كلّمنا على واتساب</a>
          <a class="btn ghost" href="#services">شوف المنيو</a>
        </div>
      </div>
      <div class="visual">
        <div class="steam" aria-hidden="true"><span></span><span></span><span></span></div>
        <img class="ph arch" data-slot="hero_image" src="{{ $D->image('hero_image') }}" alt="{{ $project->name }}" loading="eager" fetchpriority="high">
        <div class="scene-spot"><div data-scene>@include('site.partials.depth.scene', ['sceneKey' => ($sceneOverride ?? null) ?: 'brew'])</div></div>
      </div>
    </div>
  </section>

  @if ($D->has('about'))<section class="about" id="about">
    <div class="wrap">
      <h2 data-slot="about_title">{!! $D->text('about_title') !!}</h2>
      <p data-slot="about_body">{!! $D->text('about_body') !!}</p>
    </div>
  </section>@endif

  @if ($D->has('services'))<section class="services" id="services">
    <div class="wrap">
      <div class="sec-head">
        <h2 data-slot="services_title">{!! $D->text('services_title') !!}</h2>
        <span>المنيو بيتغير مع المواسم</span>
      </div>
      <ul class="svc" data-slot="services_list">@foreach ($D->services() as $e)<li><h3>{{ $e['title'] }}</h3>@if ($e['desc'])<p>{{ $e['desc'] }}</p>@endif</li>@endforeach</ul>
    </div>
  </section>@endif

  @if ($D->has('gallery'))<section class="gallery" id="gallery">
    <div class="wrap">
      <h2 data-slot="gallery_title">{!! $D->text('gallery_title') !!}</h2>
      <div class="g-grid">
        <img class="ph" data-slot="gallery_image_1" src="{{ $D->image('gallery_image_1') }}" alt="{{ $project->name }}" loading="lazy">
        <img class="ph" data-slot="gallery_image_2" src="{{ $D->image('gallery_image_2') }}" alt="{{ $project->name }}" loading="lazy">
        <img class="ph" data-slot="gallery_image_3" src="{{ $D->image('gallery_image_3') }}" alt="{{ $project->name }}" loading="lazy">
        <img class="ph" data-slot="gallery_image_4" src="{{ $D->image('gallery_image_4') }}" alt="{{ $project->name }}" loading="lazy">
        <img class="ph" data-slot="gallery_image_5" src="{{ $D->image('gallery_image_5') }}" alt="{{ $project->name }}" loading="lazy">
      </div>
    </div>
  @php $more = $D->extraGallery(['gallery_image_1', 'gallery_image_2', 'gallery_image_3', 'gallery_image_4', 'gallery_image_5']); @endphp@if ($more)<div class="wrap bq-more-gallery">@foreach ($more as $k => $src)<img class="ph" data-slot="{{ $k }}" src="{{ $src }}" alt="{{ $project->name }}" loading="lazy">@endforeach</div>@endif</section>@endif

  @if ($D->has('testimonials'))<section class="reviews" id="reviews">
    <div class="wrap">
      <h2 data-slot="testimonials_title">{!! $D->text('testimonials_title') !!}</h2>@if ($D->rating())@include('site.partials.rating-badge', ['badge' => ['rating' => $D->rating()]])@endif
      <div class="r-list">@foreach ($D->testimonials() as $t)<figure data-slot="testimonials_list">@if ($t['stars'])<span class="bq-stars" role="img" aria-label="تقييم {{ $t['stars'] }} من 5">{{ str_repeat('★', $t['stars']) }}</span>@endif<blockquote class="bq-quote">{{ $t['quote'] }}</blockquote><figcaption>{{ $t['name'] }}</figcaption></figure>@endforeach
      </div>
    </div>
  </section>@endif

  @if ($D->has('contact'))<section class="contact" id="contact">
    <div class="wrap">
      <h2 data-slot="contact_title">{!! $D->text('contact_title') !!}</h2>
      <p data-slot="contact_note">{!! $D->text('contact_note') !!}</p>
      <a class="btn" data-slot="contact_link" href="{{ $D->href('contact_link') }}"@if ($D->external('contact_link')) target="_blank" rel="noopener"@endif>ابعت على واتساب</a>
    </div>
  </section>@endif
</main>

<footer>
  <div class="wrap">
    <span>{{ $project->name }}</span>
    <span>كل الحقوق محفوظة</span>
  </div>
</footer>
