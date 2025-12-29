<?php

namespace App\Providers;

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
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                $settings = \App\Models\Setting::all()->pluck('value', 'key');

                if ($settings->has('app_name')) {
                    config(['app.name' => $settings['app_name']]);
                }

                view()->share('app_settings', $settings);
                $this->app->instance('app_settings', $settings);
            }
        } catch (\Exception $e) {
            //
        }
    }
}
