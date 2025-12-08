<?php

namespace App\CustomClasses;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SoapClient;

class zarinPal
{
    public static function getAuthority($amount, $description, $mobile, $callbackUrl){
        $MerchantID = config('zarinpal.merchantId');
        $client = new SoapClient(config('zarinpal.payment_verification_url'), ['encoding' => 'UTF-8']);
        $result = $client->PaymentRequest([
            'MerchantID'     => $MerchantID,
            'Amount'         => $amount,
            'Description'    => $description,
            'Mobile'         => $mobile,
            'CallbackURL'    => $callbackUrl,
        ]);

        if ($result->Status == 100)
            return $result->Authority;
    }

    public static function pay($request)
    {
        $MerchantID = config('zarinpal.merchantId');
        $client = new SoapClient(config('zarinpal.payment_verification_url'), ['encoding' => 'UTF-8']);
        $result = $client->PaymentRequest([
            'MerchantID'     => $MerchantID,
            'Amount'         => $request['amount'],
            'Description'    => $request['description'],
            'Mobile'         => $request['mobile'],
            'CallbackURL'    => $request['callbackUrl'],
        ]);

        if ($result->Status == 100) {
            return redirect(config('zarinpal.pay_url').$result->Authority);
        } else {
            return null;
        }

    }

    public static function verify(Request $request, $price)
    {
        $MerchantID = config('zarinpal.merchantId');
        if ($request->Status == 'OK') {
            // URL also can be ir.zarinpal.com or de.zarinpal.com
            $client = new SoapClient(config('zarinpal.payment_verification_url'), ['encoding' => 'UTF-8']);

            $result = $client->PaymentVerification([
                'MerchantID'     => $MerchantID,
                'Authority'      => $request->Authority,
                'Amount'         => $price,
            ]);
            // Log::info('zarinpal', array($result));

            if ($result->Status == 100 or $result->Status == 101) {
                return $result->RefID;

            }else {
                return 0;
            }
        }else{
            return 1;
        }
    }
}
