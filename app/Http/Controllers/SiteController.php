<?php

namespace App\Http\Controllers;

use App\Models\GeneratedSite;
use App\Services\SiteRenderer;
use Illuminate\Http\Request;
use Illuminate\View\View;

// عرض الموقع الناتج فعلياً للزائر — بيتفعّل بعد ما DetectSite middleware يتأكد إن السب دومين
// بيمثّل موقع موجود ويحمّله جوّه attributes الطلب. القوالب من نوع "ووردبريس" لسه مالهاش رندر
// حقيقي (مرحلة قادمة في الخطة)، فبنعرضلها صفحة "قريباً" بدل ما نحاول نبني بيها صفحة عادية.
// بناء الأقسام والألوان بقى في SiteRenderer المشتركة (نفس المنطق مستخدم في التصدير الثابت).
class SiteController extends Controller
{
    public function __construct(private readonly SiteRenderer $renderer)
    {
    }

    public function show(Request $request): View
    {
        /** @var GeneratedSite $site */
        $site = $request->attributes->get('site');

        $project = $site->project;
        $template = $project->template;

        if ($template->kind === 'wordpress') {
            return view('site.coming-soon', ['project' => $project]);
        }

        return view('site.show', $this->renderer->render($site));
    }
}
