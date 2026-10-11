<?php

namespace App\Providers;

use App\Models\AiRun;
use App\Models\Project;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // (2026-10-11) يوزرات متعددة: أي رابط فيه {project} أو {run} بيتفحص هنا مركزياً — مشروع أو طلب ذكاء
        // اصطناعي مش بتاع اليوزر (وهو مش أدمن) = 404، فمفيش كنترولر يقدر ينسى الفحص.
        Route::bind('project', fn ($value) => Project::visibleTo(auth()->user())->whereKey($value)->firstOrFail());

        Route::bind('run', function ($value) {
            $user = auth()->user();
            $run = AiRun::whereKey($value)->firstOrFail();
            abort_unless($user && ($user->is_admin || (int) $run->user_id === (int) $user->id), 404);

            return $run;
        });
    }
}
