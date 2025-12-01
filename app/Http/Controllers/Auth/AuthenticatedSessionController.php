<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UserProfile\Controllers\UserAgentController;
use Behin\CrmClient\CrmClient;


class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request, CrmClient $crmClient)
    {
        $request->authenticate();

        $request->session()->regenerate();
        UserAgentController::set();
        $user = User::where('email', $request->email)->first();
        if (!$user->crm_contact_id) {
            $mobile = $user->email;
            $response = $crmClient->request("contacts", "GET", [
                '$select' => 'contactid,fullname,mobilephone',
                '$filter' => "mobilephone eq '$mobile'"
            ]);
            if ($response->successful()) {
                $body = $response->json();
                if (!empty($body['value'])) {
                    // مخاطب موجود است
                    $contactId = $body['value'][0]['contactid'];
                } else {
                    // مخاطب وجود ندارد → ایجاد جدید
                    $response = $crmClient->save('contacts', [
                        "createdon" => now(),
                        "telephone1" => $mobile,
                        "mobilephone" => $mobile,
                        "firstname" => $mobile,
                    ]);

                    if ($response->successful()) {
                        $entityIdHeader = $response->header('OData-EntityId');

                        if ($entityIdHeader) {
                            // استخراج GUID از داخل پرانتز
                            preg_match('/\(([^)]+)\)/', $entityIdHeader, $matches);
                            $contactId = $matches[1] ?? null;
                        }
                    } else {
                        Log::error('Failed to create contact in CRM', [
                            'mobile' => $mobile,
                            'response' => $response->body(),
                            'status' => $response->status()
                        ]);
                    }
                }
            }
            if($contactId){
                $user->crm_contact_id = $contactId;
                $user->save();
            }
        }
        return response(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
