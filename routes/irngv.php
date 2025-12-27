<?php

use App\Http\Controllers\IrngvChargeController;
use App\Http\Controllers\IrngvPollAnswerController;
use App\Http\Controllers\IrngvUsersInfoController;
use Illuminate\Support\Facades\Route;


Route::prefix('irngv')->group(function(){
    Route::get('poll/{link}', [IrngvUsersInfoController::class, 'show_poll']);
    Route::post('register-answer', [IrngvPollAnswerController::class, 'register_answer'])->name('register-poll-answers');

    //شارژ
    Route::get('charge', [IrngvChargeController::class, 'index']);
    Route::any('charge/verify', [IrngvChargeController::class, 'verify']);
    Route::post('charge/status', [IrngvChargeController::class, 'status']);
});
