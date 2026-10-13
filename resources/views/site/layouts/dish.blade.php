{{-- تصميم Claude Design (2026-10-13) — 01-restaurants/02-restaurant.html. اتحوّل من HTML جاهز (convert98): الشكل ثابت والمحتوى من خانات
     الموقع عن طريق App\Support\DesignedPage، والرسمة 3D جوه [data-scene]. ماتعدّلش يدوي من غير ما تراجع الأصل. --}}
@php $D = new \App\Support\DesignedPage($sections, $contactAction ?? null); @endphp
<style>@verbatim

*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;background:var(--site-background);color:var(--site-text);font-family:'IBM Plex Sans Arabic',sans-serif;font-weight:300;font-size:17px;line-height:1.9;overflow-x:hidden}
h1,h2,h3{font-family:'Amiri',serif;font-weight:400;line-height:1.3;margin:0}
p{margin:0}
.wrap{max-width:1200px;margin:0 auto;padding:0 48px}
.ph{display:flex;align-items:center;justify-content:center;text-align:center;color:var(--site-muted);font-size:14px;letter-spacing:1px;background:radial-gradient(circle at 50% 40%,color-mix(in srgb,var(--site-primary) 14%,var(--site-surface)),var(--site-surface));border:1px solid color-mix(in srgb,var(--site-primary) 30%,transparent);overflow:hidden}
.rule{display:flex;align-items:center;justify-content:center;gap:16px;color:var(--site-primary);margin:0 auto 28px}
.rule::before,.rule::after{content:"";width:70px;height:1px;background:var(--site-primary)}
.rule i{width:8px;height:8px;rotate:45deg;border:1px solid var(--site-primary);display:block}
.sec-title{text-align:center;font-size:54px}

.nav{position:sticky;top:0;z-index:10;background:color-mix(in srgb,var(--site-background) 90%,transparent);backdrop-filter:blur(10px);border-bottom:1px solid color-mix(in srgb,var(--site-primary) 20%,transparent)}
.nav .wrap{display:grid;grid-template-columns:1fr auto 1fr;align-items:center;height:92px;gap:24px}
.links{display:flex;gap:30px;flex-wrap:wrap}
.links a{color:var(--site-muted);text-decoration:none;font-size:15px;letter-spacing:.5px}
.links a:hover{color:var(--site-primary)}
.brand{font-family:'Amiri',serif;font-size:32px;color:var(--site-primary);text-decoration:none;text-align:center}
.nav .btn{justify-self:end}
.btn{display:inline-flex;align-items:center;justify-content:center;min-height:50px;padding:0 32px;border:1px solid var(--site-primary);color:var(--site-primary);text-decoration:none;font-weight:600;letter-spacing:.5px;background:transparent;transition:background .4s,color .4s}
.btn:hover,.btn.solid{background:var(--site-primary);color:var(--site-background)}
.btn.solid:hover{background:transparent;color:var(--site-primary)}

.hero{padding:96px 0 0;text-align:center}
.hero h1{font-size:88px;max-width:900px;margin:0 auto}
.hero p{max-width:620px;margin:28px auto 0;font-size:20px;color:var(--site-muted)}
.hero .btn{margin-top:40px}
.hero-stage{position:relative;margin-top:80px;padding-bottom:150px}
.hero-img{width:100%;height:560px}
.frame{position:absolute;inset:-18px -18px 132px;border:1px solid color-mix(in srgb,var(--site-primary) 35%,transparent);pointer-events:none}
.scene-holder{position:absolute;left:50%;bottom:0;width:300px;height:300px;margin-left:-150px;border-radius:50%;background:var(--site-background);border:1px solid var(--site-primary);display:flex;align-items:center;justify-content:center}
.scene-holder::before{content:"";position:absolute;inset:-14px;border-radius:50%;border:1px dashed color-mix(in srgb,var(--site-primary) 45%,transparent);animation:spin 40s linear infinite}
[data-scene]{width:260px;height:260px;border-radius:50%}
@keyframes spin{to{rotate:360deg}}

.about{padding:120px 0}
.about .wrap{display:grid;grid-template-columns:1fr 1px 1.4fr;gap:72px;align-items:center}
.about .line{height:100%;min-height:200px;background:color-mix(in srgb,var(--site-primary) 40%,transparent)}
.about h2{font-size:56px;text-align:start}
.about p{font-size:20px;line-height:2.1;color:var(--site-muted)}

.services{padding:120px 0;background:var(--site-surface)}
.menu{list-style:none;margin:64px auto 0;padding:0;max-width:900px;display:grid;gap:36px}
.menu li{display:grid;grid-template-columns:auto 1fr;gap:0 24px;align-items:baseline}
.menu h3{font-size:30px;color:var(--site-text)}
.menu li::after{content:"";grid-column:2;grid-row:1;border-bottom:1px dotted color-mix(in srgb,var(--site-primary) 50%,transparent);translate:0 -8px}
.menu p{grid-column:1/-1;color:var(--site-muted);margin-top:6px}

.gallery{padding:120px 0}
.g-row{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:28px;margin-top:64px}
.g-row .ph{height:520px}
.g-row .ph:nth-child(2){margin-top:60px}

.reviews{padding:120px 0;border-top:1px solid color-mix(in srgb,var(--site-primary) 18%,transparent)}
.r-list{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));margin-top:64px}
.r-list figure{margin:0;padding:0 40px;text-align:center;border-inline-start:1px solid color-mix(in srgb,var(--site-primary) 25%,transparent)}
.r-list figure:first-child{border:0}
.r-list figure::before{content:"\201D";display:block;font-family:'Amiri',serif;font-size:90px;line-height:.6;color:var(--site-primary);margin-bottom:18px}
.r-list blockquote{margin:0 0 22px;font-family:'Amiri',serif;font-size:24px;line-height:1.7}
.r-list figcaption{color:var(--site-primary);font-size:15px;letter-spacing:1px}

.contact{padding:40px 0 140px}
.box{border:1px solid var(--site-primary);outline:1px solid color-mix(in srgb,var(--site-primary) 40%,transparent);outline-offset:10px;padding:90px 48px;text-align:center;background:var(--site-surface)}
.box h2{font-size:60px}
.box p{color:var(--site-muted);font-size:19px;margin:20px auto 40px;max-width:560px}

footer{padding:36px 0;text-align:center;color:var(--site-muted);font-size:14px;border-top:1px solid color-mix(in srgb,var(--site-primary) 18%,transparent)}
footer b{font-family:'Amiri',serif;color:var(--site-primary);font-size:22px;font-weight:400;display:block;margin-bottom:6px}

@media (max-width:1100px){
  .nav .wrap{grid-template-columns:1fr auto}
  .links{display:none}
  .about .wrap{grid-template-columns:1fr}
  .about .line{display:none}
  .g-row,.r-list{grid-template-columns:1fr}
  .r-list figure{border:0;padding:24px 0}
  .hero h1{font-size:60px}
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
    <nav class="links" aria-label="أقسام الصفحة">
      <a href="#about">عن المطعم</a>
      <a href="#services">المنيو</a>
      <a href="#gallery">الأجواء</a>
      <a href="#reviews">الضيوف</a>
    </nav>
    <a class="brand" href="#top">{{ $project->name }}</a>
    <a class="btn" href="{{ $D->href('contact_link') }}"@if ($D->external('contact_link')) target="_blank" rel="noopener"@endif>احجز ترابيزة</a>
  </div>
</header>

<main id="page-top">
  <section id="hero" class="hero">
    <div class="wrap">
      <div class="rule" aria-hidden="true"><i></i></div>
      <h1 data-slot="hero_title">{!! $D->text('hero_title') !!}</h1>
      <p data-slot="hero_subtitle">{!! $D->text('hero_subtitle') !!}</p>
      <a class="btn solid" data-slot="hero_cta" href="{{ $D->href('hero_cta') }}"@if ($D->external('hero_cta')) target="_blank" rel="noopener"@endif>احجز مكانك الليلة</a>
      <div class="hero-stage">
        <div class="frame" aria-hidden="true"></div>
        <img class="ph hero-img" data-slot="hero_image" src="{{ $D->image('hero_image') }}" alt="{{ $project->name }}" loading="eager" fetchpriority="high">
        <div class="scene-holder"><div data-scene>@include('site.partials.depth.scene', ['sceneKey' => ($sceneOverride ?? null) ?: 'dish'])</div></div>
      </div>
    </div>
  </section>

  @if ($D->has('about'))<section class="about" id="about">
    <div class="wrap">
      <h2 data-slot="about_title">{!! $D->text('about_title') !!}</h2>
      <div class="line" aria-hidden="true"></div>
      <p data-slot="about_body">{!! $D->text('about_body') !!}</p>
    </div>
  </section>@endif

  @if ($D->has('services'))<section class="services" id="services">
    <div class="wrap">
      <div class="rule" aria-hidden="true"><i></i></div>
      <h2 class="sec-title" data-slot="services_title">{!! $D->text('services_title') !!}</h2>
      <ul class="menu" data-slot="services_list">@foreach ($D->services() as $e)<li><h3>{{ $e['title'] }}</h3>@if ($e['desc'])<p>{{ $e['desc'] }}</p>@endif</li>@endforeach</ul>
    </div>
  </section>@endif

  @if ($D->has('gallery'))<section class="gallery" id="gallery">
    <div class="wrap">
      <div class="rule" aria-hidden="true"><i></i></div>
      <h2 class="sec-title" data-slot="gallery_title">{!! $D->text('gallery_title') !!}</h2>
      <div class="g-row">
        <img class="ph" data-slot="gallery_image_1" src="{{ $D->image('gallery_image_1') }}" alt="{{ $project->name }}" loading="lazy">
        <img class="ph" data-slot="gallery_image_2" src="{{ $D->image('gallery_image_2') }}" alt="{{ $project->name }}" loading="lazy">
        <img class="ph" data-slot="gallery_image_3" src="{{ $D->image('gallery_image_3') }}" alt="{{ $project->name }}" loading="lazy">
      </div>
    </div>
  @php $more = $D->extraGallery(['gallery_image_1', 'gallery_image_2', 'gallery_image_3']); @endphp@if ($more)<div class="wrap bq-more-gallery">@foreach ($more as $k => $src)<img class="ph" data-slot="{{ $k }}" src="{{ $src }}" alt="{{ $project->name }}" loading="lazy">@endforeach</div>@endif</section>@endif

  @if ($D->has('testimonials'))<section class="reviews" id="reviews">
    <div class="wrap">
      <h2 class="sec-title" data-slot="testimonials_title">{!! $D->text('testimonials_title') !!}</h2>@if ($D->rating())@include('site.partials.rating-badge', ['badge' => ['rating' => $D->rating()]])@endif
      <div class="r-list">@foreach ($D->testimonials() as $t)<figure data-slot="testimonials_list">@if ($t['stars'])<span class="bq-stars" role="img" aria-label="تقييم {{ $t['stars'] }} من 5">{{ str_repeat('★', $t['stars']) }}</span>@endif<blockquote class="bq-quote">{{ $t['quote'] }}</blockquote><figcaption>{{ $t['name'] }}</figcaption></figure>@endforeach
      </div>
    </div>
  </section>@endif

  @if ($D->has('contact'))<section class="contact" id="contact">
    <div class="wrap">
      <div class="box">
        <div class="rule" aria-hidden="true"><i></i></div>
        <h2 data-slot="contact_title">{!! $D->text('contact_title') !!}</h2>
        <p data-slot="contact_note">{!! $D->text('contact_note') !!}</p>
        <a class="btn solid" data-slot="contact_link" href="{{ $D->href('contact_link') }}"@if ($D->external('contact_link')) target="_blank" rel="noopener"@endif>اتصل بينا</a>
      </div>
    </div>
  </section>@endif
</main>

<footer>
  <b>{{ $project->name }}</b>
  كل الحقوق محفوظة
</footer>
