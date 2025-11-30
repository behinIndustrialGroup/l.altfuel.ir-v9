<?php

namespace Mkhodroo\AltfuelTicket;

use Mkhodroo\AltfuelTicket\Controllers\LangflowController;
use Mkhodroo\AltfuelTicket\Controllers\CreateTicketController;
use Illuminate\Support\Facades\Route;
use Mkhodroo\AltfuelTicket\Controllers\AddTicketCommentAttachmentController;
use Mkhodroo\AltfuelTicket\Controllers\CommentAttachmentController;
use Mkhodroo\AltfuelTicket\Controllers\GetTicketController;
use Mkhodroo\AltfuelTicket\Controllers\ReportController;
use Mkhodroo\AltfuelTicket\Controllers\ShowTicketController;
use Mkhodroo\AltfuelTicket\Controllers\TicketAssignController;
use Mkhodroo\AltfuelTicket\Controllers\TicketCatagoryController;
use Mkhodroo\AltfuelTicket\Controllers\TicketConversionController;
use Mkhodroo\AltfuelTicket\Controllers\TicketFilterController;
use Mkhodroo\AltfuelTicket\Controllers\TicketStatusController;

Route::name('ATRoutes.')->prefix(config('ATConfig.route-prefix') . 'tickets')->middleware(['web','auth', 'access'])->group(function(){
    Route::get('', [CreateTicketController::class, 'index'])->name('index');
    Route::post('store', [CreateTicketController::class, 'store'])->name('store');
    Route::post('change-status', [TicketStatusController::class, 'changeStatus'])->name('changeStatus');
    Route::post('change-catagory', [TicketCatagoryController::class, 'changeCatagory'])->name('changeCatagory');
    Route::post('conversion-type', [TicketConversionController::class, 'update'])->name('conversionType.update');
    Route::post('set-score', [CreateTicketController::class, 'setScore'])->name('setScore');

    Route::name('show.')->prefix('show')->group(function(){
        Route::get('all', [ShowTicketController::class, 'list'])->name('listForm');
        Route::post('', [ShowTicketController::class, 'show'])->name('ticket');
    });
    Route::name('get.')->prefix('get')->group(function(){
        Route::get('all', [GetTicketController::class, 'getAll'])->name('getAll');
        Route::get('get-mine', [GetTicketController::class, 'getMyTickets'])->name('getMyTickets');
        Route::post('get-by-catagory', [GetTicketController::class, 'getByCatagory'])->name('getByCatagory');
        Route::post('old-get-by-catagory', [GetTicketController::class, 'oldGetByCatagory'])->name('oldGetByCatagory');
        Route::post('get-all-by-catagory', [GetTicketController::class, 'getAllByCatagory'])->name('getAllByCatagory');
        Route::get('status/{id}', [TicketStatusController::class, 'getStatus'])->name('status');
        Route::get('score/{id}', [CreateTicketController::class, 'score'])->name('score');
        Route::get('{id}', [GetTicketController::class, 'get'])->name('get');
    });

    Route::name('catagory.')->prefix('catagory')->group(function(){
        Route::get('modal', [TicketCatagoryController::class, 'modalCategory'])->name('modal');
        Route::get('for-actor', [TicketCatagoryController::class, 'categoryForActor'])->name('forActor');
        Route::get('all-parent', [TicketCatagoryController::class, 'getAllParent'])->name('getAllParent');
        Route::get('get-children/{parent_id?}/{count?}', [TicketCatagoryController::class, 'getChildrenByParentId'])->name('getChildrenByParentId');
        Route::get('get-actors/{cat_id?}', [TicketCatagoryController::class, 'getActorsByCatId'])->name('getActorsByCatId');
        Route::get('count/{id?}', [TicketCatagoryController::class, 'count'])->name('count');
        Route::post('conversion-settings', [TicketCatagoryController::class, 'updateConversionSettings'])->name('updateConversionSettings');
    });

    Route::name('report.')->prefix('report')->group(function(){
        Route::get('summary', [ReportController::class, 'summary'])->name('summary');
    });

    Route::post('assign', [TicketAssignController::class, 'assign'])->name('assign');

    Route::post('get-last-comment', [LangflowController::class, 'getLastComment'])->name('getLastComment');
    Route::post('langflow', [LangflowController::class, 'ticketLastCommentReply'])->name('langflow');
    Route::post('save-improved-answer', [LangflowController::class, 'saveImprovedResponse'])->name('saveImprovedResponse');
    Route::get('download-zip', [CommentAttachmentController::class, 'downloadZip'])->name('download.zip');
    Route::get('ticket-download-zip', [AddTicketCommentAttachmentController::class, 'downloadZip'])->name('ticket.download.zip');

    Route::post('filter', [TicketFilterController::class, 'filterByAgent'])->name('filterByAgent'); 
});

Route::get('/crm/categories/sync', [TicketCatagoryController::class, 'sync'])->name('crm.categories.sync');
Route::get('/crm/tickets/sync', [GetTicketController::class, 'syncTickets'])->name('crm.tickets.sync');