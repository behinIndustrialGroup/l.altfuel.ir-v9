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
use Illuminate\Support\Facades\Http;

class IrngvChargeController extends Controller
{

    public function index(Request $request)
    {
        $orderId = preg_replace('/=$/', '', $request->getQueryString());
        echo $orderId . '<br>';
        $response = Http::post(
            'https://irngv.mimt.gov.ir/api/PaymentServices/OrderInfo',
            [
                'orderCode' => $orderId,
            ]
        );

        if ($response->successful()) {
            $data = $response->json();
            return $data;
        } else {
            $error = $response->body();
        }
        $amount = $request->get('amount') ?? 10000;
        $description = $request->get('description') ?? 'شارژ اولیه';
        $mobile = $request->get('mobile') ?? '09376922176';
        $irngvCallbackUrl = $request->get('callback_url') ?? url('');
        $callbackUrl = url('irngv/charge/verify');
        $authority = zibal::getAuthority($amount, $description, $mobile, $callbackUrl);
        $status = 'pending';

        return [
            'order_id' => $orderId,
            'amount' => $amount,
            'description' => $description,
            'mobile' => $mobile,
            'callback_url' => $irngvCallbackUrl,
            'authority' => $authority,
            'status' => $status,
        ];

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

    public function verify(Request $request)
    {
        $authority = isset($request->Authority) ? $request->Authority : $request->trackId;
        $irngvCharge = IrngvCharge::where('authority', $authority)->firstOrFail();
        $result = zibal::verify($request, $irngvCharge->amount);
        if (!$result) {
            $irngvCharge->update([
                'status' => 'failed',
            ]);
        } else {
            $irngvCharge->update([
                'ref_id' => $result,
                'status' => 'success',
            ]);
        }

        return redirect($irngvCharge->callback_url);
    }

    public function status(Request $request)
    {
        $orderId = $request->order_id;
        $irngvCharge = IrngvCharge::where('order_id', $orderId)->first();
        $result = zibal::verify2($orderId, $irngvCharge->authority, $irngvCharge->amount);
        if ($result) {
            $irngvCharge->update([
                'ref_id' => $result,
                'status' => 'success',
            ]);
            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => 'شارژ موفقیت آمیز بود',
                'data' => [
                    'ref_id' => $result,
                    'amount' => $irngvCharge->amount,
                    'description' => $irngvCharge->description,
                    'mobile' => $irngvCharge->mobile,
                ],
            ]);
        } else {
            $irngvCharge->update([
                'status' => 'failed',
            ]);
            return response()->json([
                'code' => 400,
                'status' => 'failed',
                'message' => 'شارژ با شکست مواجه شد',
            ]);
        }
    }
}
