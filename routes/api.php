<?php
namespace App\Http\Controllers;

use Behin\CrmClient\CrmClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/hidro/get', [HidroController::class, 'createApi']);
Route::get('/agencies/get', [MarakezController::class, 'createApi']);

Route::get('/crm/contacts/sync', function(CrmClient $crmClient){
    $crmClient->save('contacts', [
        'firstname' => 'John',
        'lastname' => 'Doe',
        'telephone1' => '1234567890',
        'mobilephone' => '1234567890',
        'emailaddress1' => 'john.doe@example.com',
    ]);
})->name('crm.contacts.sync');

Route::prefix('/hamayesh/')->group(function(){
    Route::post('workshop', [HamayeshController::class, 'register_workshop'])->name('register_workshop');
});

Route::prefix('irngv')->group(function(){
    Route::post('get-token', [IrngvApiController::class, 'get_token'])->middleware('api_auth');
    Route::post('poll-link', [IrngvApiController::class, 'send_sms'])->middleware('api_access');
});

Route::name('blog.')->prefix('blog')->group(function(){
    Route::get('/get', []);
    Route::get('/get-by-catagory/{catagory}', [BlogController::class, 'getByCatagory']);
    Route::get('get-by-id/{id}', [BlogController::class, 'getById'])->name('getById');
});

// -------- langflow --------

Route::post('/test-langflow', function () {
    return response()->json([
        'message' => 'Hello World!'
    ]);
});

Route::post('/check-national-id', function (Request $request) {
    $nationalId = $request->input('national_id');
    return response()->json([
        'status' => 'success',
        'received_national_id' => $nationalId,
        'message' => "کد ملی $nationalId دریافت شد."
    ]);
});
