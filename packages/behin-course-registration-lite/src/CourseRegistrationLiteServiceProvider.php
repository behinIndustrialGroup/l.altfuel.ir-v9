<?php

namespace CourseRegistrationLite;

use Illuminate\Support\ServiceProvider;

class CourseRegistrationLiteServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/course-registration-lite.php', 'course-registration-lite');
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/course-registration-lite.php' => config_path('course-registration-lite.php')
        ], 'course-registration-lite');

        $this->loadMigrationsFrom(__DIR__ . '/migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/views', 'CourseRegistrationLiteViews');
        $this->loadJsonTranslationsFrom(__DIR__ . '/Lang');
    }
}
