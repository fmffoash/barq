{{-- تصميم Claude Design (2026-10-13) — 01-restaurants/05-grills.html. اتحوّل من HTML جاهز (convert98): الشكل ثابت والمحتوى من خانات
     الموقع عن طريق App\Support\DesignedPage، والرسمة 3D جوه [data-scene]. ماتعدّلش يدوي من غير ما تراجع الأصل. --}}
@php $D = new \App\Support\DesignedPage($sections, $contactAction ?? null); @endphp
<style>@verbatim

*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;background:var(--site-background);color:var(--site-text);font-family:'Mada',sans-serif;font-size:19px;line-height:1.8;overflow-x:hidden}
h1,h2,h3{font-family:'Noto Kufi Arabic',sans-serif;line-height:1.3;margin:0}
p{margin:0}
.wrap{max-width:1240px;margin:0 auto;padding:0 48px}
.ph{display:flex;align-items:center;justify-content:center;text-align:center;color:var(--site-muted);font-weight:500;background:repeating-linear-gradient(90deg,var(--site-surface) 0 22px,color-mix(in srgb,var(--site-surface) 80%,var(--site-primary)) 22px 24px);overflow:hidden}
.label{display:inline-flex;align-items:center;gap:12px;color:var(--site-primary);font-weight:700;font-size:16px;letter-spacing:1px;margin-bottom:18px}
.label::before{content:"";width:36px;height:2px;background:var(--site-primary)}

.nav{position:absolute;top:0;inset-inline:0;z-index:10}
.nav .wrap{display:flex;align-items:center;gap:36px;height:96px}
.brand{font-family:'Noto Kufi Arabic',sans-serif;font-weight:900;font-size:26px;color:var(--site-text);text-decoration:none}
.brand span{color:var(--site-primary)}
.links{display:flex;gap:30px;margin-inline-start:auto;flex-wrap:wrap}
.links a{color:var(--site-text);text-decoration:none;font-weight:500;opacity:.85}
.links a:hover{color:var(--site-primary);opacity:1}
.btn{display:inline-flex;align-items:center;justify-content:center;min-height:52px;padding:0 30px;background:var(--site-primary);color:var(--site-background);font-weight:700;font-size:18px;text-decoration:none;clip-path:polygon(10px 0,100% 0,calc(100% - 10px) 100%,0 100%);transition:box-shadow .3s,filter .3s}
.btn:hover{filter:brightness(1.1)}
.btn.line{background:transparent;color:var(--site-text);box-shadow:inset 0 0 0 2px var(--site-text);clip-path:none}

.hero{position:relative;min-height:860px;display:flex;align-items:flex-end;padding:0 0 110px;overflow:hidden}
.hero-bg{position:absolute;inset:0;height:100%}
.hero::after{content:"";position:absolute;inset:0;background:linear-gradient(to top,var(--site-background) 8%,color-mix(in srgb,var(--site-background) 55%,transparent) 55%,color-mix(in srgb,var(--site-background) 30%,transparent));pointer-events:none}
.hero .wrap{position:relative;z-index:2;width:100%;display:grid;grid-template-columns:1.3fr 1fr;align-items:end;gap:40px}
.hero h1{font-size:84px;font-weight:900;line-height:1.15}
.hero h1 span{color:var(--site-primary)}
.hero p{font-size:22px;color:var(--site-muted);margin-top:22px;max-width:560px}
.hero .actions{display:flex;gap:16px;margin-top:38px;flex-wrap:wrap}
.ember{position:relative;justify-self:start;width:340px;height:340px}
.ember::before{content:"";position:absolute;inset:-30px;border-radius:50%;background:radial-gradient(circle,color-mix(in srgb,var(--site-primary) 45%,transparent),transparent 65%);animation:glow 3s ease-in-out infinite}
[data-scene]{position:relative;width:340px;height:340px}
.sparks{position:absolute;inset:0;pointer-events:none}
.sparks i{position:absolute;bottom:20%;width:5px;height:5px;border-radius:50%;background:var(--site-primary);box-shadow:0 0 8px var(--site-primary);animation:spark 4s linear infinite;opacity:0}
.sparks i:nth-child(1){inset-inline-start:20%;animation-delay:0s}
.sparks i:nth-child(2){inset-inline-start:45%;animation-delay:1.2s}
.sparks i:nth-child(3){inset-inline-start:70%;animation-delay:2.1s}
.sparks i:nth-child(4){inset-inline-start:33%;animation-delay:2.9s}
.sparks i:nth-child(5){inset-inline-start:60%;animation-delay:.6s}
@keyframes glow{0%,100%{opacity:.7;scale:1}50%{opacity:1;scale:1.08}}
@keyframes spark{0%{opacity:0;translate:0 0}15%{opacity:1}100%{opacity:0;translate:-20px -320px}}

.about{padding:120px 0}
.about .wrap{display:grid;grid-template-columns:1fr 1fr;gap:80px;align-items:start}
.about h2{font-size:96px;font-weight:900;color:transparent;-webkit-text-stroke:2px var(--site-primary);line-height:1.1}
.about p{font-size:22px;line-height:2;padding-top:20px;border-top:2px solid var(--site-primary)}

.services{padding:110px 0;background:var(--site-surface)}
.services h2,.gallery h2,.reviews h2{font-size:56px;font-weight:800}
.svc{list-style:none;margin:56px 0 0;padding:0}
.svc li{display:grid;grid-template-columns:80px 1fr 1.2fr;gap:24px;align-items:center;padding:30px 0;border-bottom:1px solid color-mix(in srgb,var(--site-text) 14%,transparent);counter-increment:s;transition:padding .3s,background .3s}
.svc li:hover{padding-inline-start:16px;background:color-mix(in srgb,var(--site-primary) 8%,transparent)}
.svc li::before{content:counter(s,arabic-indic);font-family:'Noto Kufi Arabic',sans-serif;font-size:34px;font-weight:800;color:var(--site-primary)}
.svc h3{font-size:30px;font-weight:800}
.svc p{color:var(--site-muted)}

.gallery{padding:110px 0}
.strip{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;margin-top:48px}
.strip .ph{height:420px}
.strip .ph:nth-child(even){margin-top:50px}

.reviews{padding:110px 0;border-top:1px solid color-mix(in srgb,var(--site-text) 12%,transparent)}
.r-list{margin-top:56px;display:grid;gap:56px}
.r-list figure{margin:0;max-width:820px}
.r-list figure:nth-child(2){margin-inline-start:auto;text-align:end}
.r-list blockquote{margin:0;font-family:'Noto Kufi Arabic',sans-serif;font-weight:500;font-size:30px;line-height:1.7}
.r-list figcaption{margin-top:14px;color:var(--site-primary);font-weight:700;font-size:18px}

.contact{padding:130px 0;text-align:center;position:relative;overflow:hidden}
.contact::before{content:"";position:absolute;left:50%;top:50%;width:700px;height:700px;margin:-350px 0 0 -350px;border-radius:50%;background:radial-gradient(circle,color-mix(in srgb,var(--site-primary) 25%,transparent),transparent 65%);animation:glow 4s ease-in-out infinite;pointer-events:none}
.contact .wrap{position:relative}
.contact h2{font-size:72px;font-weight:900}
.contact p{font-size:22px;color:var(--site-muted);margin:18px auto 42px;max-width:600px}
.contact .btn{min-height:64px;font-size:22px;padding:0 50px}

footer{padding:30px 0;color:var(--site-muted);font-size:15px;border-top:1px solid color-mix(in srgb,var(--site-text) 12%,transparent)}
footer .wrap{display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap}

@media (max-width:1100px){
  .hero .wrap,.about .wrap{grid-template-columns:1fr}
  .strip{grid-template-columns:repeat(3,minmax(0,1fr))}
  .svc li{grid-template-columns:60px 1fr}
  .svc li p{grid-column:2}
  .links{display:none}
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
    <a class="brand" href="#top">جمرة <span>المشويات</span></a>
    <nav class="links" aria-label="أقسام الصفحة">
      <a href="#about">عن جمرة</a>
      <a href="#services">المشويات</a>
      <a href="#gallery">صور</a>
      <a href="#reviews">الزباين</a>
    </nav>
    <a class="btn" href="{{ $D->href('contact_link') }}"@if ($D->external('contact_link')) target="_blank" rel="noopener"@endif>اطلب دلوقتي</a>
  </div>
</header>

<main id="page-top">
  <section id="hero" class="hero">
    <img class="ph hero-bg" data-slot="hero_image" src="{{ $D->image('hero_image') }}" alt="{{ $project->name }}" loading="eager" fetchpriority="high">
    <div class="wrap">
      <div>
        <span class="label">على الفحم من سنين</span>
        <h1 data-slot="hero_title">{!! $D->text('hero_title') !!}</h1>
        <p data-slot="hero_subtitle">{!! $D->text('hero_subtitle') !!}</p>
        <div class="actions">
          <a class="btn" data-slot="hero_cta" href="{{ $D->href('hero_cta') }}"@if ($D->external('hero_cta')) target="_blank" rel="noopener"@endif>اتصل واطلب</a>
          <a class="btn line" href="#services">شوف الأصناف</a>
        </div>
      </div>
      <div class="ember">
        <div data-scene>@include('site.partials.depth.scene', ['sceneKey' => ($sceneOverride ?? null) ?: 'skewer'])</div>
        <div class="sparks" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></div>
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
      <span class="label">المنيو</span>
      <h2 data-slot="services_title">{!! $D->text('services_title') !!}</h2>
      <ul class="svc" data-slot="services_list">@foreach ($D->services() as $e)<li><h3>{{ $e['title'] }}</h3>@if ($e['desc'])<p>{{ $e['desc'] }}</p>@endif</li>@endforeach</ul>
    </div>
  </section>@endif

  @if ($D->has('gallery'))<section class="gallery" id="gallery">
    <div class="wrap">
      <h2 data-slot="gallery_title">{!! $D->text('gallery_title') !!}</h2>
      <div class="strip">
        <img class="ph" data-slot="gallery_image_1" src="{{ $D->image('gallery_image_1') }}" alt="{{ $project->name }}" loading="lazy">
        <img class="ph" data-slot="gallery_image_2" src="{{ $D->image('gallery_image_2') }}" alt="{{ $project->name }}" loading="lazy">
        <img class="ph" data-slot="gallery_image_3" src="{{ $D->image('gallery_image_3') }}" alt="{{ $project->name }}" loading="lazy">
        <img class="ph" data-slot="gallery_image_4" src="{{ $D->image('gallery_image_4') }}" alt="{{ $project->name }}" loading="lazy">
        <img class="ph" data-slot="gallery_image_5" src="{{ $D->image('gallery_image_5') }}" alt="{{ $project->name }}" loading="lazy">
        <img class="ph" data-slot="gallery_image_6" src="{{ $D->image('gallery_image_6') }}" alt="{{ $project->name }}" loading="lazy">
      </div>
    </div>
  @php $more = $D->extraGallery(['gallery_image_1', 'gallery_image_2', 'gallery_image_3', 'gallery_image_4', 'gallery_image_5', 'gallery_image_6']); @endphp@if ($more)<div class="wrap bq-more-gallery">@foreach ($more as $k => $src)<img class="ph" data-slot="{{ $k }}" src="{{ $src }}" alt="{{ $project->name }}" loading="lazy">@endforeach</div>@endif</section>@endif

  @if ($D->has('testimonials'))<section class="reviews" id="reviews">
    <div class="wrap">
      <span class="label">الزباين</span>
      <h2 data-slot="testimonials_title">{!! $D->text('testimonials_title') !!}</h2>@if ($D->rating())@include('site.partials.rating-badge', ['badge' => ['rating' => $D->rating()]])@endif
      <div class="r-list">@foreach ($D->testimonials() as $t)<figure data-slot="testimonials_list">@if ($t['stars'])<span class="bq-stars" role="img" aria-label="تقييم {{ $t['stars'] }} من 5">{{ str_repeat('★', $t['stars']) }}</span>@endif<blockquote class="bq-quote">{{ $t['quote'] }}</blockquote><figcaption>{{ $t['name'] }}</figcaption></figure>@endforeach
      </div>
    </div>
  </section>@endif

  @if ($D->has('contact'))<section class="contact" id="contact">
    <div class="wrap">
      <h2 data-slot="contact_title">{!! $D->text('contact_title') !!}</h2>
      <p data-slot="contact_note">{!! $D->text('contact_note') !!}</p>
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
