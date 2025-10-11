<?php

namespace CourseRegistration;

use Illuminate\Support\ServiceProvider;

class CourseRegistrationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/course-registration.php', 'course-registration');
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/course-registration.php' => config_path('course-registration.php')
        ], 'course-registration');

        $this->loadMigrationsFrom(__DIR__ . '/migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/views', 'CourseRegistrationViews');
        $this->loadJsonTranslationsFrom(__DIR__ . '/Lang');
    }
}
