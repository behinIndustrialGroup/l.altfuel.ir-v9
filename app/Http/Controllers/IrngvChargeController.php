<?php

namespace App\Http\Controllers;

use App\CustomClasses\Access;
use App\CustomClasses\IrngvUserInfoFilterBy;
use App\CustomClasses\zibal;
use App\Enums\EnumsEntity;
use App\Http\Validations\IrngvUsersInfoValidation;
use App\Models\IrngvCharge;
use App\Models\IrngvPollAnswer;
use App\Models\IrngvUsersInfo;
use Hekmatinasser\Verta\Facades\Verta;
use Illuminate\Http\Request;

class IrngvChargeController extends Controller
{
    
    public function index(Request $request)
    {
        $orderId = $request->get('order_id') ?? time();
        $amount = $request->get('amount') ?? 10000;
        $description = $request->get('description') ?? 'شارژ اولیه';
        $mobile = $request->get('mobile') ?? '09376922176';
        $irngvCallbackUrl = $request->get('callback_url') ?? url('');
        $callbackUrl = url('irngv/charge/verify');
        $authority = zibal::getAuthority($amount,$description,$mobile,$callbackUrl);
        $status = 'pending';

        IrngvCharge::create([
            'order_id' => $orderId,
            'amount' => $amount,
            'description' => $description,
            'mobile' => $mobile,
            'callback_url' => $irngvCallbackUrl,
            'authority' => $authority,
            'status' => $status,
        ]);

        return redirect(config('zibal.pay_url') . $authority);
    }
}
