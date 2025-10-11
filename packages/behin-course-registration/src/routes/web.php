<?php

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
