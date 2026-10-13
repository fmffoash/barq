{{-- تصميم Claude Design (2026-10-13) — 01-restaurants/04-pizza.html. اتحوّل من HTML جاهز (convert98): الشكل ثابت والمحتوى من خانات
     الموقع عن طريق App\Support\DesignedPage، والرسمة 3D جوه [data-scene]. ماتعدّلش يدوي من غير ما تراجع الأصل. --}}
@php $D = new \App\Support\DesignedPage($sections, $contactAction ?? null); @endphp
<style>@verbatim

*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;background:var(--site-background);color:var(--site-text);font-family:'Readex Pro',sans-serif;font-size:17px;line-height:1.75;overflow-x:hidden}
h1,h2,h3{font-family:'Changa',sans-serif;line-height:1.1;margin:0}
p{margin:0}
.wrap{max-width:1260px;margin:0 auto;padding:0 44px}
.ph{display:flex;align-items:center;justify-content:center;text-align:center;color:var(--site-muted);font-weight:600;background:conic-gradient(from 45deg,var(--site-surface) 25%,color-mix(in srgb,var(--site-primary) 10%,var(--site-surface)) 0 50%,var(--site-surface) 0 75%,color-mix(in srgb,var(--site-primary) 10%,var(--site-surface)) 0) 0 0/40px 40px;overflow:hidden}

.nav{position:sticky;top:0;z-index:10;background:var(--site-background);box-shadow:0 1px 0 color-mix(in srgb,var(--site-text) 10%,transparent)}
.nav .wrap{display:flex;align-items:center;gap:36px;height:84px}
.brand{font-family:'Changa',sans-serif;font-weight:800;font-size:30px;text-decoration:none;color:var(--site-text)}
.brand::before{content:"";display:inline-block;width:14px;height:14px;border-radius:50%;background:var(--site-primary);margin-inline-end:10px}
.links{display:flex;gap:28px;margin-inline-start:auto;flex-wrap:wrap}
.links a{text-decoration:none;font-weight:500}
.links a:hover{color:var(--site-primary)}
.btn{display:inline-flex;align-items:center;justify-content:center;min-height:52px;padding:0 30px;border-radius:12px;background:var(--site-primary);color:var(--site-background);font-family:'Changa',sans-serif;font-weight:700;font-size:19px;text-decoration:none;transition:scale .2s,box-shadow .2s}
.btn:hover{scale:1.04;box-shadow:0 12px 24px rgba(0,0,0,.18)}
.btn.dark{background:var(--site-text)}

.hero{padding:56px 0 100px;position:relative}
.hero .wrap{display:grid;grid-template-columns:1fr 560px;gap:40px;align-items:center}
.hero h1{font-size:112px;font-weight:800;letter-spacing:-1px}
.hero h1 b{color:var(--site-primary);font-weight:800;display:block}
.hero p{font-size:20px;color:var(--site-muted);margin-top:24px;max-width:500px}
.hero .row{display:flex;gap:16px;align-items:center;margin-top:40px;flex-wrap:wrap}
.hero .row small{color:var(--site-muted);font-weight:500}
.disc{position:relative;width:560px;max-width:100%;aspect-ratio:1}
.disc .ring{position:absolute;inset:0;border-radius:50%;border:3px dashed var(--site-primary);animation:spin 30s linear infinite}
.disc .ph{position:absolute;inset:28px;border-radius:50%}
.scene-badge{position:absolute;top:-10px;inset-inline-start:-40px;width:230px;height:230px;border-radius:50%;background:var(--site-primary);box-shadow:0 20px 40px rgba(0,0,0,.2);display:flex;align-items:center;justify-content:center;animation:bob 5s ease-in-out infinite}
[data-scene]{width:200px;height:200px;border-radius:50%}
@keyframes spin{to{rotate:360deg}}
@keyframes bob{0%,100%{translate:0 0}50%{translate:0 -14px}}

.band{background:var(--site-primary);color:var(--site-background);padding:120px 0;clip-path:polygon(0 8%,100% 0,100% 92%,0 100%)}
.band .wrap{display:grid;grid-template-columns:1fr 1.3fr;gap:64px;align-items:center}
.band h2{font-size:80px;font-weight:800}
.band p{font-size:22px;line-height:1.9}

.services{padding:110px 0}
.head{display:flex;justify-content:space-between;align-items:end;gap:24px;margin-bottom:52px;flex-wrap:wrap}
.head h2{font-size:68px;font-weight:800}
.head p{color:var(--site-muted);max-width:360px}
.svc{list-style:none;margin:0;padding:0;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px}
.svc li{background:var(--site-surface);border-radius:20px;padding:32px 26px 36px;position:relative;overflow:hidden;transition:background .25s,color .25s}
.svc li::before{content:"";display:block;width:48px;height:48px;border-radius:50%;border:8px solid var(--site-primary);margin-bottom:28px}
.svc li:hover{background:var(--site-text);color:var(--site-background)}
.svc li:hover p{color:color-mix(in srgb,var(--site-background) 75%,transparent)}
.svc h3{font-size:28px;margin-bottom:10px}
.svc p{color:var(--site-muted)}

.gallery{padding:0 0 110px}
.gallery h2{font-size:68px;font-weight:800;margin-bottom:48px}
.bento{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));grid-template-rows:260px 260px;gap:18px}
.bento .ph{border-radius:20px}
.bento .ph:nth-child(1){grid-column:span 2;grid-row:span 2}
.bento .ph:nth-child(4){grid-column:span 1}

.reviews{padding:110px 0;background:var(--site-text);color:var(--site-background)}
.reviews h2{font-size:68px;font-weight:800;margin-bottom:56px}
.reviews h2 span{color:var(--site-primary)}
.r-list{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px}
.r-list figure{margin:0;padding:36px;border-radius:20px;border:2px solid color-mix(in srgb,var(--site-background) 18%,transparent);display:flex;flex-direction:column;justify-content:space-between;gap:28px}
.r-list blockquote{margin:0;font-size:20px}
.r-list figcaption{font-family:'Changa',sans-serif;font-weight:700;font-size:20px;color:var(--site-background)}
.r-list figcaption::before{content:"";display:inline-block;width:28px;height:4px;background:var(--site-primary);margin-inline-end:12px;vertical-align:middle}

.contact{padding:110px 0}
.contact .wrap{display:flex;align-items:center;justify-content:space-between;gap:40px;flex-wrap:wrap}
.contact h2{font-size:84px;font-weight:800}
.contact p{font-size:20px;color:var(--site-muted);margin-top:12px}
.contact .btn{min-height:68px;font-size:24px;padding:0 48px}

footer{background:var(--site-surface);padding:28px 0;font-size:15px;color:var(--site-muted)}
footer .wrap{display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap}

@media (max-width:1150px){
  .hero .wrap,.band .wrap{grid-template-columns:1fr}
  .hero h1{font-size:80px}
  .disc{justify-self:center}
  .svc{grid-template-columns:repeat(2,minmax(0,1fr))}
  .r-list{grid-template-columns:1fr}
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
    <a class="brand" href="#top">{{ $project->name }}</a>
    <nav class="links" aria-label="أقسام الصفحة">
      <a href="#about">إحنا مين</a>
      <a href="#services">المنيو</a>
      <a href="#gallery">صور</a>
      <a href="#reviews">آراء</a>
    </nav>
    <a class="btn" href="{{ $D->href('contact_link') }}"@if ($D->external('contact_link')) target="_blank" rel="noopener"@endif>اطلب دلوقتي</a>
  </div>
</header>

<main id="page-top">
  <section id="hero" class="hero">
    <div class="wrap">
      <div>
        <h1 data-slot="hero_title">{!! $D->text('hero_title') !!}</h1>
        <p data-slot="hero_subtitle">{!! $D->text('hero_subtitle') !!}</p>
        <div class="row">
          <a class="btn" data-slot="hero_cta" href="{{ $D->href('hero_cta') }}"@if ($D->external('hero_cta')) target="_blank" rel="noopener"@endif>اتصل واطلب</a>
          <small>دليفري في المنطقة كلها</small>
        </div>
      </div>
      <div class="disc">
        <div class="ring" aria-hidden="true"></div>
        <img class="ph" data-slot="hero_image" src="{{ $D->image('hero_image') }}" alt="{{ $project->name }}" loading="eager" fetchpriority="high">
        <div class="scene-badge"><div data-scene>@include('site.partials.depth.scene', ['sceneKey' => ($sceneOverride ?? null) ?: 'pizza'])</div></div>
      </div>
    </div>
  </section>

  @if ($D->has('about'))<section class="band" id="about">
    <div class="wrap">
      <h2 data-slot="about_title">{!! $D->text('about_title') !!}</h2>
      <p data-slot="about_body">{!! $D->text('about_body') !!}</p>
    </div>
  </section>@endif

  @if ($D->has('services'))<section class="services" id="services">
    <div class="wrap">
      <div class="head">
        <h2 data-slot="services_title">{!! $D->text('services_title') !!}</h2>
        <p>كل البيتزا بتتعمل عند الطلب، مفيش حاجة متخزنة.</p>
      </div>
      <ul class="svc" data-slot="services_list">@foreach ($D->services() as $e)<li><h3>{{ $e['title'] }}</h3>@if ($e['desc'])<p>{{ $e['desc'] }}</p>@endif</li>@endforeach</ul>
    </div>
  </section>@endif

  @if ($D->has('gallery'))<section class="gallery" id="gallery">
    <div class="wrap">
      <h2 data-slot="gallery_title">{!! $D->text('gallery_title') !!}</h2>
      <div class="bento">
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
      <div>
        <h2 data-slot="contact_title">{!! $D->text('contact_title') !!}</h2>
        <p data-slot="contact_note">{!! $D->text('contact_note') !!}</p>
      </div>
      <a class="btn" data-slot="contact_link" href="{{ $D->href('contact_link') }}"@if ($D->external('contact_link')) target="_blank" rel="noopener"@endif>اتصل بينا</a>
    </div>
  </section>@endif
</main>

<footer>
  <div class="wrap">
    <span>{{ $project->name }}</span>
    <span>كل الحقوق محفوظة</span>
  </div>
</footer>
