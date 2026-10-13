{{-- تصميم Claude Design (2026-10-13) — 01-restaurants/06-juice.html. اتحوّل من HTML جاهز (convert98): الشكل ثابت والمحتوى من خانات
     الموقع عن طريق App\Support\DesignedPage، والرسمة 3D جوه [data-scene]. ماتعدّلش يدوي من غير ما تراجع الأصل. --}}
@php $D = new \App\Support\DesignedPage($sections, $contactAction ?? null); @endphp
<style>@verbatim

*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;background:var(--site-background);color:var(--site-text);font-family:'Almarai',sans-serif;font-size:18px;line-height:1.8;overflow-x:hidden}
h1,h2,h3{font-family:'Alexandria',sans-serif;line-height:1.3;margin:0}
p{margin:0}
.wrap{max-width:1220px;margin:0 auto;padding:0 44px}
.ph{display:flex;align-items:center;justify-content:center;text-align:center;color:var(--site-muted);font-weight:700;width:100%;height:100%;background:radial-gradient(circle at 30% 30%,var(--site-surface),color-mix(in srgb,var(--site-primary) 18%,var(--site-background)))}
.center{text-align:center}
.sec-title{font-size:52px;font-weight:800}
.sec-title::after{content:"";display:block;width:56px;height:56px;margin:14px auto 0;background:radial-gradient(circle,var(--site-primary) 6px,transparent 7px) 0 0/28px 28px}

.nav{position:sticky;top:16px;z-index:10;margin:16px auto 0;max-width:1180px;padding:0 16px}
.nav .bar{display:flex;align-items:center;gap:28px;height:72px;padding:0 14px 0 28px;border-radius:999px;background:var(--site-surface);box-shadow:0 10px 30px rgba(0,0,0,.08)}
.brand{font-family:'Alexandria',sans-serif;font-weight:800;font-size:24px;color:var(--site-primary);text-decoration:none}
.links{display:flex;gap:24px;margin-inline-start:auto;flex-wrap:wrap}
.links a{text-decoration:none;font-weight:700;color:var(--site-muted)}
.links a:hover{color:var(--site-primary)}
.btn{display:inline-flex;align-items:center;justify-content:center;min-height:50px;padding:0 28px;border-radius:999px;background:var(--site-primary);color:var(--site-surface);font-family:'Alexandria',sans-serif;font-weight:700;text-decoration:none;transition:scale .25s}
.btn:hover{scale:1.06}

.hero{padding:70px 0 110px;position:relative;overflow:hidden}
.hero .wrap{display:grid;grid-template-columns:1fr 1fr;gap:56px;align-items:center}
.hero h1{font-size:72px;font-weight:800}
.hero h1 span{position:relative;color:var(--site-primary);white-space:nowrap}
.hero h1 span::after{content:"";position:absolute;inset-inline:0;bottom:6px;height:14px;border-radius:14px;background:color-mix(in srgb,var(--site-primary) 18%,transparent);z-index:-1}
.hero p{font-size:21px;color:var(--site-muted);margin-top:22px;max-width:500px}
.hero .btn{margin-top:36px;min-height:58px;padding:0 36px;font-size:19px}
.blob-zone{position:relative;height:560px}
.blob{position:absolute;inset:20px 40px 40px 20px;overflow:hidden;border-radius:58% 42% 50% 50% / 45% 55% 45% 55%;animation:morph 12s ease-in-out infinite;box-shadow:0 30px 60px rgba(0,0,0,.12)}
.scene-bubble{position:absolute;bottom:-10px;inset-inline-end:-10px;width:240px;height:240px;border-radius:50%;background:var(--site-surface);box-shadow:0 20px 40px rgba(0,0,0,.12);display:flex;align-items:center;justify-content:center;animation:float 6s ease-in-out infinite}
[data-scene]{width:210px;height:210px;border-radius:50%}
.bubbles span{position:absolute;border-radius:50%;background:color-mix(in srgb,var(--site-primary) 22%,transparent);animation:float 7s ease-in-out infinite}
.bubbles span:nth-child(1){width:70px;height:70px;top:10px;inset-inline-end:10px}
.bubbles span:nth-child(2){width:34px;height:34px;top:120px;inset-inline-start:0;animation-delay:-2s}
.bubbles span:nth-child(3){width:22px;height:22px;bottom:80px;inset-inline-start:60px;animation-delay:-4s}
@keyframes morph{0%,100%{border-radius:58% 42% 50% 50% / 45% 55% 45% 55%}50%{border-radius:42% 58% 45% 55% / 55% 45% 55% 45%}}
@keyframes float{0%,100%{translate:0 0}50%{translate:0 -16px}}

.about{padding:20px 0 110px}
.about .card{background:var(--site-surface);border-radius:48px;padding:72px 80px;display:grid;grid-template-columns:auto 1fr;gap:56px;align-items:center;box-shadow:0 20px 50px rgba(0,0,0,.06)}
.about h2{font-size:46px;font-weight:800;color:var(--site-primary)}
.about p{font-size:21px}

.services{padding:0 0 110px}
.svc{list-style:none;margin:56px 0 0;padding:0;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:28px}
.svc li{background:var(--site-surface);border-radius:36px;padding:36px 32px;text-align:center;transition:translate .3s,box-shadow .3s}
.svc li:hover{translate:0 -8px;box-shadow:0 20px 40px rgba(0,0,0,.08)}
.svc li::before{content:"";display:block;width:84px;height:84px;margin:0 auto 22px;border-radius:50%;background:radial-gradient(circle at 35% 35%,var(--site-surface) 0 12%,var(--site-primary) 13% 100%)}
.svc li:nth-child(even)::before{background:radial-gradient(circle at 35% 35%,var(--site-surface) 0 12%,color-mix(in srgb,var(--site-primary) 55%,var(--site-background)) 13% 100%)}
.svc h3{font-size:24px;font-weight:700;margin-bottom:6px}
.svc p{color:var(--site-muted)}

.gallery{padding:110px 0;background:color-mix(in srgb,var(--site-primary) 9%,var(--site-background));border-radius:80px 80px 0 0}
.circles{display:flex;justify-content:center;gap:40px;margin-top:56px;flex-wrap:wrap}
.circles .c{width:320px;height:320px;border-radius:50%;overflow:hidden;border:10px solid var(--site-surface);box-shadow:0 20px 40px rgba(0,0,0,.1)}
.circles .c:nth-child(2){margin-top:60px}

.reviews{padding:110px 0;background:color-mix(in srgb,var(--site-primary) 9%,var(--site-background))}
.r-list{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:28px;margin-top:56px}
.r-list figure{margin:0;background:var(--site-surface);border-radius:32px 32px 32px 6px;padding:36px}
.r-list blockquote{margin:0 0 20px;font-size:19px}
.r-list figcaption{display:flex;align-items:center;gap:12px;font-family:'Alexandria',sans-serif;font-weight:700}
.r-list figcaption::before{content:"";width:40px;height:40px;border-radius:50%;background:color-mix(in srgb,var(--site-primary) 25%,var(--site-background))}

.contact{padding:110px 0}
.contact .card{position:relative;overflow:hidden;background:var(--site-primary);color:var(--site-surface);border-radius:56px;padding:90px 40px;text-align:center}
.contact .card::before,.contact .card::after{content:"";position:absolute;border-radius:50%;background:color-mix(in srgb,var(--site-surface) 14%,transparent);animation:float 8s ease-in-out infinite}
.contact .card::before{width:260px;height:260px;top:-80px;inset-inline-start:-60px}
.contact .card::after{width:160px;height:160px;bottom:-50px;inset-inline-end:80px;animation-delay:-3s}
.contact h2{font-size:60px;font-weight:800;position:relative}
.contact p{font-size:21px;margin:16px auto 36px;max-width:560px;position:relative}
.contact .btn{background:var(--site-surface);color:var(--site-primary);min-height:60px;padding:0 44px;font-size:20px;position:relative}

footer{padding:0 0 40px;text-align:center;color:var(--site-muted);font-size:15px}
footer b{font-family:'Alexandria',sans-serif;color:var(--site-primary);margin-inline-end:10px}

@media (max-width:1100px){
  .hero .wrap,.about .card{grid-template-columns:1fr}
  .svc,.r-list{grid-template-columns:repeat(2,minmax(0,1fr))}
  .links{display:none}
  .circles .c{width:240px;height:240px}
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
  <div class="bar">
    <a class="brand" href="#top">{{ $project->name }}</a>
    <nav class="links" aria-label="أقسام الصفحة">
      <a href="#about">عننا</a>
      <a href="#services">العصاير</a>
      <a href="#gallery">صور</a>
      <a href="#reviews">آراء</a>
    </nav>
    <a class="btn" href="{{ $D->href('contact_link') }}"@if ($D->external('contact_link')) target="_blank" rel="noopener"@endif>اطلب</a>
  </div>
</header>

<main id="page-top">
  <section id="hero" class="hero">
    <div class="wrap">
      <div>
        <h1 data-slot="hero_title">{!! $D->text('hero_title') !!}</h1>
        <p data-slot="hero_subtitle">{!! $D->text('hero_subtitle') !!}</p>
        <a class="btn" data-slot="hero_cta" href="{{ $D->href('hero_cta') }}"@if ($D->external('hero_cta')) target="_blank" rel="noopener"@endif>اطلب على واتساب</a>
      </div>
      <div class="blob-zone">
        <div class="bubbles" aria-hidden="true"><span></span><span></span><span></span></div>
        <div class="blob"><img class="ph" data-slot="hero_image" src="{{ $D->image('hero_image') }}" alt="{{ $project->name }}" loading="eager" fetchpriority="high"></div>
        <div class="scene-bubble"><div data-scene>@include('site.partials.depth.scene', ['sceneKey' => ($sceneOverride ?? null) ?: 'juice'])</div></div>
      </div>
    </div>
  </section>

  @if ($D->has('about'))<section class="about" id="about">
    <div class="wrap">
      <div class="card">
        <h2 data-slot="about_title">{!! $D->text('about_title') !!}</h2>
        <p data-slot="about_body">{!! $D->text('about_body') !!}</p>
      </div>
    </div>
  </section>@endif

  @if ($D->has('services'))<section class="services" id="services">
    <div class="wrap center">
      <h2 class="sec-title" data-slot="services_title">{!! $D->text('services_title') !!}</h2>
      <ul class="svc" data-slot="services_list">@foreach ($D->services() as $e)<li><h3>{{ $e['title'] }}</h3>@if ($e['desc'])<p>{{ $e['desc'] }}</p>@endif</li>@endforeach</ul>
    </div>
  </section>@endif

  @if ($D->has('gallery'))<section class="gallery" id="gallery">
    <div class="wrap center">
      <h2 class="sec-title" data-slot="gallery_title">{!! $D->text('gallery_title') !!}</h2>
      <div class="circles">
        <div class="c"><img class="ph" data-slot="gallery_image_1" src="{{ $D->image('gallery_image_1') }}" alt="{{ $project->name }}" loading="lazy"></div>
        <div class="c"><img class="ph" data-slot="gallery_image_2" src="{{ $D->image('gallery_image_2') }}" alt="{{ $project->name }}" loading="lazy"></div>
        <div class="c"><img class="ph" data-slot="gallery_image_3" src="{{ $D->image('gallery_image_3') }}" alt="{{ $project->name }}" loading="lazy"></div>
      </div>
    </div>
  @php $more = $D->extraGallery(['gallery_image_1', 'gallery_image_2', 'gallery_image_3']); @endphp@if ($more)<div class="wrap bq-more-gallery">@foreach ($more as $k => $src)<img class="ph" data-slot="{{ $k }}" src="{{ $src }}" alt="{{ $project->name }}" loading="lazy">@endforeach</div>@endif</section>@endif

  @if ($D->has('testimonials'))<section class="reviews" id="reviews">
    <div class="wrap center">
      <h2 class="sec-title" data-slot="testimonials_title">{!! $D->text('testimonials_title') !!}</h2>@if ($D->rating())@include('site.partials.rating-badge', ['badge' => ['rating' => $D->rating()]])@endif
      <div class="r-list" style="text-align:start">@foreach ($D->testimonials() as $t)<figure data-slot="testimonials_list">@if ($t['stars'])<span class="bq-stars" role="img" aria-label="تقييم {{ $t['stars'] }} من 5">{{ str_repeat('★', $t['stars']) }}</span>@endif<blockquote class="bq-quote">{{ $t['quote'] }}</blockquote><figcaption>{{ $t['name'] }}</figcaption></figure>@endforeach
      </div>
    </div>
  </section>@endif

  @if ($D->has('contact'))<section class="contact" id="contact">
    <div class="wrap">
      <div class="card">
        <h2 data-slot="contact_title">{!! $D->text('contact_title') !!}</h2>
        <p data-slot="contact_note">{!! $D->text('contact_note') !!}</p>
        <a class="btn" data-slot="contact_link" href="{{ $D->href('contact_link') }}"@if ($D->external('contact_link')) target="_blank" rel="noopener"@endif>ابعت على واتساب</a>
      </div>
    </div>
  </section>@endif
</main>

<footer>
  <b>{{ $project->name }}</b> كل الحقوق محفوظة
</footer>
