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
        $response = Http::asMultipart()->post(
            'https://irngv.mimt.gov.ir/api/PaymentServices/OrderInfo',
            [
                [
                    'name'     => 'orderCode',
                    'contents' => $orderId,
                ],
            ]
        );

        if ($response->successful()) {
            $data = $response->json();
            $amount = $data['amount'];
            $description = 'تست';
            $mobile = $data['mobile'];
            $irngvCallbackUrl = $request->get('callback_url') ?? url('');
            $callbackUrl = url('irngv/charge/verify');
            return view('irngv.charge', compact('orderId','amount', 'description', 'mobile', 'callbackUrl', 'irngvCallbackUrl'));

        } else {
            $error = $response->body();
            return $error;
        }
    }

    public static function pay(Request $request)
    {
        $orderId = $request->orderId;
        $amount = $request->amount;
        $description = $request->description;
        $mobile = $request->mobile;
        $callbackUrl = route('irngv.charge.verify');
        $irngvCallbackUrl = $request->irngvCallbackUrl ?? url('');
        $authority = zibal::getAuthority($amount, $description, $mobile, $callbackUrl);
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
