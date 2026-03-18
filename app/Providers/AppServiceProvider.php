<?php

namespace App\Providers;

use App\Models\DrawingSubmittal;
use App\Models\FabQueue;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
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
        Route::model('submittal', DrawingSubmittal::class);
        Route::model('fab_queue', FabQueue::class);

        if (config('app.force_https')) {
            URL::forceScheme('https');
        }

        Gate::define('admin-access', function (User $user): bool {
            return $user->isAdmin();
        });
    }
}
