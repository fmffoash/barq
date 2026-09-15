<?php

namespace App\Http\Controllers;

use App\Models\GeneratedSite;
use App\Models\Project;
use App\Models\Template;
use Illuminate\View\View;

// الصفحة الرئيسية بعد تسجيل الدخول — نظرة سريعة على عدد القوالب/المشاريع/المواقع المنشورة،
// وآخر خمس مشاريع اتعملت.
class DashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboard', [
            'templatesCount' => Template::count(),
            'projectsCount' => Project::count(),
            'publishedSitesCount' => GeneratedSite::where('status', 'published')->count(),
            'recentProjects' => Project::with(['template', 'variant', 'site'])
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }
}
