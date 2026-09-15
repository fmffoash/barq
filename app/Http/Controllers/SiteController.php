<?php

namespace App\Http\Controllers;

use App\Models\GeneratedSite;
use App\Services\SiteRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// عرض الموقع الناتج فعلياً للزائر — بيتفعّل بعد ما DetectSite middleware يتأكد إن السب دومين
// بيمثّل موقع موجود ويحمّله جوّه attributes الطلب. القوالب من نوع "ووردبريس" مالهاش رندر عندنا
// خالص — الموقع الحقيقي بتاعها شغّال على شبكة WordPress Multisite منفصلة (Phase 5)، فبمجرد
// ما يتعمل ليها site فعلي (isWordPressProvisioned) بنحوّل الزائر هناك على طول. لحد ما ده
// يحصل، بنعرض صفحة "قريباً" بدل ما نحاول نبني بيها صفحة عادية. بناء الأقسام والألوان لصفحات
// الهبوط بقى في SiteRenderer المشتركة (نفس المنطق مستخدم في التصدير الثابت).
class SiteController extends Controller
{
    public function __construct(private readonly SiteRenderer $renderer)
    {
    }

    public function show(Request $request): View|RedirectResponse
    {
        /** @var GeneratedSite $site */
        $site = $request->attributes->get('site');

        $project = $site->project;
        $template = $project->template;

        if ($template->kind === 'wordpress') {
            if ($site->isWordPressProvisioned()) {
                return redirect()->away($site->wp_site_url);
            }

            return view('site.coming-soon', ['project' => $project]);
        }

        return view('site.show', $this->renderer->render($site));
    }
}
