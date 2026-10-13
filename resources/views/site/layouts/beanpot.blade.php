{{-- تصميم Claude Design (2026-10-13) — 01-restaurants/03-ful-taameya.html. اتحوّل من HTML جاهز (convert98): الشكل ثابت والمحتوى من خانات
     الموقع عن طريق App\Support\DesignedPage، والرسمة 3D جوه [data-scene]. ماتعدّلش يدوي من غير ما تراجع الأصل. --}}
@php $D = new \App\Support\DesignedPage($sections, $contactAction ?? null); @endphp
<style>@verbatim

*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;background:var(--site-background);color:var(--site-text);font-family:'Cairo',sans-serif;font-size:18px;line-height:1.75;overflow-x:hidden}
h1,h2,h3{font-family:'Lemonada',cursive;line-height:1.35;margin:0}
p{margin:0}
.wrap{max-width:1240px;margin:0 auto;padding:0 40px}
.ph{display:flex;align-items:center;justify-content:center;text-align:center;font-weight:800;color:var(--site-muted);background:radial-gradient(color-mix(in srgb,var(--site-primary) 22%,transparent) 2px,transparent 2.5px) 0 0/18px 18px,var(--site-surface);border:3px solid var(--site-text);overflow:hidden}
.card{border:3px solid var(--site-text);box-shadow:6px 6px 0 var(--site-text);background:var(--site-surface)}

.nav{position:sticky;top:0;z-index:10;background:var(--site-background);border-bottom:3px solid var(--site-text)}
.nav .wrap{display:flex;align-items:center;gap:32px;height:80px}
.brand{font-family:'Lemonada',cursive;font-size:26px;font-weight:700;color:var(--site-primary);text-decoration:none}
.links{display:flex;gap:24px;margin-inline-start:auto;flex-wrap:wrap}
.links a{text-decoration:none;font-weight:600}
.links a:hover{color:var(--site-primary);text-decoration:underline wavy;text-underline-offset:6px}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;min-height:52px;padding:0 26px;background:var(--site-primary);color:var(--site-surface);font-weight:800;font-size:18px;text-decoration:none;border:3px solid var(--site-text);box-shadow:4px 4px 0 var(--site-text);transition:translate .15s,box-shadow .15s}
.btn:hover{translate:2px 2px;box-shadow:2px 2px 0 var(--site-text)}

.hero{padding:70px 0 90px}
.hero .wrap{display:grid;grid-template-columns:1.1fr .9fr;gap:60px;align-items:center}
.tag{display:inline-block;background:var(--site-text);color:var(--site-background);font-weight:800;padding:4px 16px;rotate:-2deg;margin-bottom:22px}
.hero h1{font-size:68px}
.hero h1 span{color:var(--site-primary);display:inline-block;padding:0 10px;background:var(--site-surface);border:3px solid var(--site-text);rotate:1.5deg}
.hero p{font-size:21px;margin-top:24px;max-width:540px;color:var(--site-muted);font-weight:600}
.hero .btn{margin-top:36px}
.plate{position:relative;rotate:2deg}
.plate .ph{height:460px;box-shadow:10px 10px 0 var(--site-text)}
.sticker{position:absolute;top:-70px;inset-inline-start:-70px;width:220px;height:220px;border-radius:50%;background:var(--site-background);border:3px dashed var(--site-text);display:flex;align-items:center;justify-content:center;animation:wobble 4s ease-in-out infinite}
[data-scene]{width:190px;height:190px;border-radius:50%}
@keyframes wobble{0%,100%{rotate:-6deg}50%{rotate:6deg}}

.ticker{background:var(--site-primary);color:var(--site-surface);border-block:3px solid var(--site-text);overflow:hidden;white-space:nowrap}
.ticker div{display:inline-flex;gap:48px;padding:14px 0;font-family:'Lemonada',cursive;font-size:22px;animation:slide 22s linear infinite}
.ticker span::after{content:"✦";margin-inline-start:48px}
@keyframes slide{from{translate:0 0}to{translate:50% 0}}

.about{padding:100px 0}
.about .card{padding:56px 64px;display:grid;grid-template-columns:300px 1fr;gap:48px;align-items:center;rotate:-.6deg}
.about h2{font-size:44px;color:var(--site-primary)}
.about p{font-size:21px;font-weight:600}

.services{padding:30px 0 100px}
.services h2,.gallery h2,.reviews h2{font-size:48px;text-align:center;margin-bottom:56px}
.svc{list-style:none;margin:0;padding:0;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:28px}
.svc li{border:3px solid var(--site-text);box-shadow:6px 6px 0 var(--site-text);background:var(--site-surface);padding:30px;transition:translate .2s}
.svc li:hover{translate:0 -6px}
.svc li:nth-child(even){background:color-mix(in srgb,var(--site-primary) 12%,var(--site-surface))}
.svc h3{font-size:24px;margin-bottom:8px}
.svc p{color:var(--site-muted);font-weight:600}

.gallery{padding:100px 0;background:var(--site-surface);border-block:3px solid var(--site-text)}
.g-row{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:28px}
.g-row figure{margin:0;padding:12px 12px 16px;background:var(--site-surface);border:3px solid var(--site-text);box-shadow:5px 5px 0 var(--site-text)}
.g-row figure:nth-child(odd){rotate:-2deg}
.g-row figure:nth-child(even){rotate:2deg;margin-top:30px}
.g-row .ph{height:260px;border-width:2px}

.reviews{padding:100px 0}
.r-list{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:32px}
.r-list figure{margin:0}
.r-list blockquote{margin:0;position:relative;padding:28px;background:var(--site-surface);border:3px solid var(--site-text);border-radius:22px;font-weight:600;font-size:19px}
.r-list blockquote::after{content:"";position:absolute;bottom:-20px;inset-inline-start:40px;width:30px;height:30px;background:var(--site-surface);border-inline-end:3px solid var(--site-text);border-bottom:3px solid var(--site-text);rotate:45deg;translate:0 -6px}
.r-list figcaption{margin-top:26px;padding-inline-start:30px;font-family:'Lemonada',cursive;color:var(--site-primary)}

.contact{padding:20px 0 110px}
.contact .card{background:var(--site-primary);color:var(--site-surface);text-align:center;padding:80px 40px}
.contact h2{font-size:56px}
.contact p{font-size:21px;margin:18px auto 36px;max-width:600px;font-weight:600}
.contact .btn{background:var(--site-background);color:var(--site-text);font-size:21px;min-height:60px;padding:0 40px}

footer{border-top:3px solid var(--site-text);padding:26px 0;font-weight:600}
footer .wrap{display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap}

@media (max-width:1100px){
  .hero .wrap,.about .card{grid-template-columns:1fr}
  .svc,.r-list,.g-row{grid-template-columns:repeat(2,minmax(0,1fr))}
  .links{display:none}
  .sticker{inset-inline-start:-10px}
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
    <a class="brand" href="#top">{{ $project->name }}</a>
    <nav class="links" aria-label="أقسام الصفحة">
      <a href="#about">حكايتنا</a>
      <a href="#services">عندنا إيه</a>
      <a href="#gallery">صور</a>
      <a href="#reviews">زباينّا</a>
    </nav>
    <a class="btn" href="{{ $D->href('contact_link') }}"@if ($D->external('contact_link')) target="_blank" rel="noopener"@endif>اطلب دلوقتي</a>
  </div>
</header>

<main id="page-top">
  <section id="hero" class="hero">
    <div class="wrap">
      <div>
        <span class="tag">من ٦ الصبح</span>
        <h1 data-slot="hero_title">{!! $D->text('hero_title') !!}</h1>
        <p data-slot="hero_subtitle">{!! $D->text('hero_subtitle') !!}</p>
        <a class="btn" data-slot="hero_cta" href="{{ $D->href('hero_cta') }}"@if ($D->external('hero_cta')) target="_blank" rel="noopener"@endif>اطلب على واتساب</a>
      </div>
      <div class="plate">
        <img class="ph" data-slot="hero_image" src="{{ $D->image('hero_image') }}" alt="{{ $project->name }}" loading="eager" fetchpriority="high">
        <div class="sticker"><div data-scene>@include('site.partials.depth.scene', ['sceneKey' => ($sceneOverride ?? null) ?: 'beanpot'])</div></div>
      </div>
    </div>
  </section>

  <div class="ticker" aria-hidden="true">
    <div><span>فول بالزيت الحار</span><span>طعمية محشية</span><span>بطاطس شيبسي</span><span>بيض بالبسطرمة</span><span>مخلل بيتي</span><span>فول بالزيت الحار</span><span>طعمية محشية</span><span>بطاطس شيبسي</span><span>بيض بالبسطرمة</span><span>مخلل بيتي</span></div>
  </div>

  @if ($D->has('about'))<section class="about" id="about">
    <div class="wrap">
      <div class="card">
        <h2 data-slot="about_title">{!! $D->text('about_title') !!}</h2>
        <p data-slot="about_body">{!! $D->text('about_body') !!}</p>
      </div>
    </div>
  </section>@endif

  @if ($D->has('services'))<section class="services" id="services">
    <div class="wrap">
      <h2 data-slot="services_title">{!! $D->text('services_title') !!}</h2>
      <ul class="svc" data-slot="services_list">@foreach ($D->services() as $e)<li><h3>{{ $e['title'] }}</h3>@if ($e['desc'])<p>{{ $e['desc'] }}</p>@endif</li>@endforeach</ul>
    </div>
  </section>@endif

  @if ($D->has('gallery'))<section class="gallery" id="gallery">
    <div class="wrap">
      <h2 data-slot="gallery_title">{!! $D->text('gallery_title') !!}</h2>
      <div class="g-row">
        <figure><img class="ph" data-slot="gallery_image_1" src="{{ $D->image('gallery_image_1') }}" alt="{{ $project->name }}" loading="lazy"></figure>
        <figure><img class="ph" data-slot="gallery_image_2" src="{{ $D->image('gallery_image_2') }}" alt="{{ $project->name }}" loading="lazy"></figure>
        <figure><img class="ph" data-slot="gallery_image_3" src="{{ $D->image('gallery_image_3') }}" alt="{{ $project->name }}" loading="lazy"></figure>
        <figure><img class="ph" data-slot="gallery_image_4" src="{{ $D->image('gallery_image_4') }}" alt="{{ $project->name }}" loading="lazy"></figure>
      </div>
    </div>
  @php $more = $D->extraGallery(['gallery_image_1', 'gallery_image_2', 'gallery_image_3', 'gallery_image_4']); @endphp@if ($more)<div class="wrap bq-more-gallery">@foreach ($more as $k => $src)<img class="ph" data-slot="{{ $k }}" src="{{ $src }}" alt="{{ $project->name }}" loading="lazy">@endforeach</div>@endif</section>@endif

  @if ($D->has('testimonials'))<section class="reviews" id="reviews">
    <div class="wrap">
      <h2 data-slot="testimonials_title">{!! $D->text('testimonials_title') !!}</h2>@if ($D->rating())@include('site.partials.rating-badge', ['badge' => ['rating' => $D->rating()]])@endif
      <div class="r-list">@foreach ($D->testimonials() as $t)<figure data-slot="testimonials_list">@if ($t['stars'])<span class="bq-stars" role="img" aria-label="تقييم {{ $t['stars'] }} من 5">{{ str_repeat('★', $t['stars']) }}</span>@endif<blockquote class="bq-quote">{{ $t['quote'] }}</blockquote><figcaption>{{ $t['name'] }}</figcaption></figure>@endforeach
      </div>
    </div>
  </section>@endif

  @if ($D->has('contact'))<section class="contact" id="contact">
    <div class="wrap">
      <div class="card">
        <h2 data-slot="contact_title">{!! $D->text('contact_title') !!}</h2>
        <p data-slot="contact_note">{!! $D->text('contact_note') !!}</p>
        <a class="btn" data-slot="contact_link" href="{{ $D->href('contact_link') }}"@if ($D->external('contact_link')) target="_blank" rel="noopener"@endif>اطلب على واتساب</a>
      </div>
    </div>
  </section>@endif
</main>

<footer>
  <div class="wrap">
    <span>{{ $project->name }}</span>
    <span>كل الحقوق محفوظة</span>
  </div>
</footer>
