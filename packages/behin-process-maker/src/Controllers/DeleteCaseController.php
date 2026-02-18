<?php 

namespace BehinProcessMaker\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use SoapClient;

class DeleteCaseController extends Controller
{

    public static function byCaseId($caseId) {
        $accessToken = AuthController::getAccessToken();
        return CurlRequestController::delete(
            $accessToken, 
            "/api/1.0/workflow/cases/$caseId"
        );
    }
}

