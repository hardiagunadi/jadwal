<?php

namespace App\Providers;
use Carbon\Carbon; // <-- INI YANG WAJIB ADA, BUKAN App\Providers\Carbon

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
        //
		Carbon::setLocale('id');
    }
}
