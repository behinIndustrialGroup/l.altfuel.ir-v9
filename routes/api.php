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
    $crmClient->save('rhs_complaintsprocesses', [
        "statecode" => 0,
        "statuscode" => 1,
        "rhs_complainttype" => false,
        "rhs_nationalcode" => "2700181859",
        "createdon" => "2025-07-15T13:33:01Z",
        "rhs_requirebachelordegree" => false,
        "rhs_mobile" => "09224261029",
        "rhs_tradeunioncode" => "12001",
        "modifiedon" => "2025-07-15T13:33:01Z",
        "rhs_centerstatus" => false,
        "rhs_description" => "توضیحات شکایت",
        "rhs_dateofreference" => "2025-07-17T07:00:00Z",
        "rhs_isthecomplaintwithinthejurisdictionoftheu" => true,
        "rhs_thesubjectcomplaint" => 130770000,
        "rhs_name" => "خودرو",
        "rhs_centertype" => 130770000,
        // "rhs_complaintsprocessid": "425ef06b-8061-f011-ae70-010101010000",
        "rhs_expertofcomdescription" => null,
        "rhs_address" => "آدرس شکایت",
        "rhs_commissiondescription" => null,
        "rhs_result" => null,
        "rhs_theexpertdescription" => null,
        "rhs_thenameoftheunionmanager" => "مدیر مرکز تستی",
        "rhs_province" => "تهران",
        "rhs_city" => "تهران",
        "rhs_tradeunitname" => "مرکز تستی"
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
