<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

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
        // Nama toko + logo tersedia di semua Blade (header customer, <title>,
        // dan halaman struk) tanpa perlu dioper satu per satu dari controller.
        View::composer('*', function ($view) {
            $view->with('branding', \App\Support\Branding::get());
        });
    }
}
