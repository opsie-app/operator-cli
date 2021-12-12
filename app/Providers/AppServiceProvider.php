<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Intonate\TinkerZero\TinkerZeroServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->environment('development') && class_exists(TinkerZeroServiceProvider::class)) {
            $this->app->register(TinkerZeroServiceProvider::class);
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
