<?php

namespace App\CustomClasses;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SoapClient;

class zibal
{
    protected static $merchantId = "693d3b04666ab9002003d5dd";

    protected $callbackUrl;


    public static function postToZibal($path, $parameters)
    {
        $url = 'https://gateway.zibal.ir/v1/' . $path;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($parameters));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response  = curl_exec($ch);
        curl_close($ch);
        return json_decode($response);
    }

    public static function getAuthority($amount,$description , $mobile, $callbackUrl)
    {
        $parameters = array(
            "merchant" => self::$merchantId, //required
            "callbackUrl" => $callbackUrl, //required
            "amount" => $amount, //required
            "description" => $description,
            "orderId" => time(), //optional
            "mobile" => $mobile, //optional for mpg
        );

        $response = self::postToZibal('request', $parameters);
        var_dump($response);
        if ($response->result == 100) {
            return $response->trackId;
            $startGateWayUrl = "https://gateway.zibal.ir/start/" . $response->trackId;
            header('location: ' . $startGateWayUrl);
        } else {
            echo "errorCode: " . $response->result . "<br>";
            echo "message: " . $response->message;
        }
    }

    public static function pay($request)
    {
        $trackId = self::getAuthority($request->amount, $request->mobile, $request->callbackUrl, $request->description);
        $startGateWayUrl = "https://gateway.zibal.ir/start/" . $trackId;
        return redirect($startGateWayUrl);
    }

    public static function verify(Request $request, $price)
    {
        if ($request->success == 1) {
            echo "شناسه سفارش: " . $request->orderId . "<br>";

            //start verfication
            $parameters = array(
                "merchant" => self::$merchantId, //required
                "trackId" => $request->trackId, //required

            );

            $response = self::postToZibal('verify', $parameters);

            if ($response->result == 100) {
                return $response->refNumber;
                echo "<pre>"; //for pretty view :)
                var_dump($response);
                //update database or something else
            } else {
                return 0;
                echo "result: " . $response->result . "<br>";
                echo "message: " . $response->message;
            }
        } else {
            return 0;
            echo "پرداخت با شکست مواجه شد.";
        }
    }
}
