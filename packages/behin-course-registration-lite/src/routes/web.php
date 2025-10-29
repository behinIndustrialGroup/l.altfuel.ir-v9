<?php

use CourseRegistrationLite\Controllers\WorkshopRegistrationAdminController;
use CourseRegistrationLite\Controllers\WorkshopRegistrationController;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])
    ->name('workshop-registration.')
    ->prefix(config('course-registration-lite.route_prefix', 'workshops'))
    ->group(function () {
        // Route::get('/register', [WorkshopRegistrationController::class, 'showForm'])->name('form');
        Route::post('/register', [WorkshopRegistrationController::class, 'submitForm'])->name('submit');
        Route::get('/verify', [WorkshopRegistrationController::class, 'verify'])->name('verify');
    });

Route::middleware(['web', 'auth', 'verified', 'access'])
    ->name('admin.workshop-registrations.')
    ->prefix(config('course-registration-lite.admin_route_prefix', 'admin/workshop-registrations'))
    ->group(function () {
        Route::get('/', [WorkshopRegistrationAdminController::class, 'index'])->name('index');
        Route::get('/export', [WorkshopRegistrationAdminController::class, 'export'])->name('export');
    });
