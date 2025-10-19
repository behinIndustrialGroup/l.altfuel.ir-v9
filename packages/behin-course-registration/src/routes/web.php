<?php

use CourseRegistration\Controllers\CourseRegistrationAdminController;
use CourseRegistration\Controllers\CourseRegistrationController;
use Illuminate\Support\Facades\Route;

Route::name('course-registration.')
    ->prefix('courses')
    ->middleware(['web'])
    ->group(function () {
        Route::get('/register', [CourseRegistrationController::class, 'showForm'])->name('form');
        Route::post('/register', [CourseRegistrationController::class, 'submitForm'])->name('submit');
        Route::get('/verify', [CourseRegistrationController::class, 'verify'])->name('verify');
    });

Route::name('admin.course-registrations.')
    ->prefix('admin/course-registrations')
    ->middleware(['web', 'auth', 'verified', 'access'])
    ->group(function () {
        Route::get('/', [CourseRegistrationAdminController::class, 'index'])->name('index');
        Route::get('/export', [CourseRegistrationAdminController::class, 'export'])->name('export');
    });
