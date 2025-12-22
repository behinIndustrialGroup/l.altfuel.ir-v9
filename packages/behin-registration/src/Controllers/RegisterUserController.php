<?php

namespace Registration\Controllers;

use App\CustomClasses\zarinPal;
use App\CustomClasses\zibal;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Registration\Jobs\SendVerifyRegisterSmsJob;
use Registration\Models\RegisterUser;

class RegisterUserController extends Controller
{
    public function showForm()
    {
        return view('RegistrationViews::index');
    }

    public function submitForm(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'national_id' => 'required|numeric|digits:10',
            'mobile' => 'required|numeric|digits:11',
            'price' => 'required|string',
        ]);

        $price = config('registration.price')[$request->price];

        $registerUser = RegisterUser::create([
            'name' => $request->input('name'),
            'national_id' => $request->input('national_id'),
            'mobile' => $request->input('mobile'),
            'price' => $price,
        ]);

        $callbackUrl = route('registration.verify');
        $des = "پرداخت هزینه آزمون: $price تومانی $registerUser->name با کدملی: $registerUser->national_id";
        $authorityCode = zarinPal::getAuthority($price, $des, $registerUser->mobile, $callbackUrl);
        $registerUser->update([
            'authority' => $authorityCode,
            'status' => 'pending'
        ]);

        return redirect(config('zarinpal.pay_url') . $authorityCode);
    }

    public function verify(Request $request){

        $registerUser = RegisterUser::where('authority', $request->Authority)->first();
        $result = zarinPal::verify($request, $registerUser->price);

        if(!$result){
            $registerUser->update([
                'status' => 'failed'
            ]);
        }else{
            SendVerifyRegisterSmsJob::dispatch($registerUser->mobile, $registerUser->name, $registerUser->price);
            $registerUser->update([
                'ref_id' => $result,
                'status' => 'success'
            ]);
        }
        return view('RegistrationViews::verify', ['refId' => $result]);

    }
}

