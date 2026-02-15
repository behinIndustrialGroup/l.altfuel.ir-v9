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
use Illuminate\Support\Facades\Log;

class IrngvChargeController extends Controller
{

    public function index(Request $request)
    {
        $orderId = preg_replace('/=$/', '', $request->getQueryString());
        $referer = $request->headers->get('referer');
        $info = [
            'full_url'      => $request->fullUrl(),        // آدرس فعلی
            'previous_url'  => url()->previous(),
            'referer'       => $request->headers->get('referer'),
            'ip'            => $request->ip(),
            'user_agent'    => $request->userAgent(),
        ];

        Log::info($info);

        $response = Http::withOptions([
    'verify' => false,
])->asMultipart()->post(
            'https://irngv.mimt.gov.ir/api/PaymentServices/OrderInfo',
            [
                [
                    'name'     => 'orderCode',
                    'contents' => $orderId,
                ],
            ]
        );

        if ($response->successful()) {
            Log::info(json_encode($response->json()));
            $data = $response->json();
            // if (isset($data['amount']) and isset($data['mobile']) and isset($data['name'])) {
            $amount = $data['amount'] ?? '10000';
            $description = $data['desc'] ?? 'شارژ پنل irngv';
            // $description = 'شارژ پنل irngv : ' . $data['name'] . ' | به مبلغ: ' . $data['amount'];
            $mobile = $data['mobile'] ?? '09376922176';
            $irngvCallbackUrl = $referer;
            $callbackUrl = url('irngv/charge/verify');
            return view('irngv.charge', compact('orderId', 'amount', 'description', 'mobile', 'callbackUrl', 'irngvCallbackUrl'));
            // } else {
            //     // return redirect('https://irngv.mimt.gov.ir/dashboard/Recharge');
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'مبلغ یا موبایل یا نام خالیست'
            //     ], 400);
            // }
        } else {
            $error = $response->body();
            return redirect($referer);
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
        $irngvCallbackUrl = $request->irngvCallbackUrl;
        $authority = zibal::getAuthority($amount, $description, $mobile, $callbackUrl);
        $status = 'pending';
        IrngvCharge::create([
            'order_id' => $orderId,
            'amount' => $amount,
            'description' => $description,
            'mobile' => $mobile,
            'callback_url' => $callbackUrl,
            'irngv_callback_url' => $irngvCallbackUrl,
            'authority' => $authority,
            'status' => $status,
        ]);
        return redirect(config('zibal.pay_url') . $authority);
    }

    public function verify(Request $request)
    {
        $authority = isset($request->Authority) ? $request->Authority : $request->trackId;
        $irngvCharge = IrngvCharge::where('authority', $authority)->firstOrFail();
        $result = zibal::verify2($authority);
        if (!$result) {
            $irngvCharge->update([
                'status' => 'failed',
            ]);
        } else {
            $irngvCharge->update([
                'ref_id' => $result,
                'status' => 'success',
            ]);
            $response = Http::withOptions([
    'verify' => false,
])->asMultipart()->post(
                'https://irngv.mimt.gov.ir/api/PaymentServices/PaymentInfo',
                [
                    [
                        'name'     => 'orderCode',
                        'contents' => $irngvCharge->order_id,
                    ],
                    [
                        'name' => 'amount',
                        'contents' => $irngvCharge->amount,
                    ],
                    [
                        'name' => 'order_id',
                        'contents' => $irngvCharge->ref_id,
                    ],
                    [
                        'name' => 'payment_status',
                        'contents' => 200,
                    ]
                ]
            );
            return redirect("https://irngv.mimt.gov.ir/blog/paid/". $irngvCharge->order_id);
            return $response->json();
        }

        return redirect("https://irngv.mimt.gov.ir/blog/paid/". $irngvCharge->order_id);
    }

    public function status(Request $request)
    {
        $orderId = $request->order_id;
        $irngvCharge = IrngvCharge::where('order_id', $orderId)->first();
        $result = zibal::verify2($irngvCharge->authority);
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
