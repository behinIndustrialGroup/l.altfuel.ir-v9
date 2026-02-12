<?php

namespace Mkhodroo\AgencyInfo\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Behin\CrmClient\CrmClient;

class UserCentersController extends Controller
{
    protected CrmClient $crmClient;

    public function __construct(CrmClient $crmClient)
    {
        $this->crmClient = $crmClient;
    }

    /**
     * نمایش مراکز متناظر کاربر لاگین‌شده بر اساس شماره تلفن
     *
     * طبق نیاز شما، فرض شده است که:
     * - شماره تلفن کاربر در ستون email جدول users ذخیره شده است
     * - شماره تلفن مراکز در CRM در فیلد rhs_mobile ذخیره شده است
     *
     * اگر الگوی نگهداری شماره‌ها (مثلاً 98+ / 0 اول شماره و ...) متفاوت باشد،
     * می‌توانید در همین متد قبل از join نرمال‌سازی لازم را اضافه کنید.
     */
    public function index()
    {
        /** @var User $user */
        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        $mobile = $user->email;

        $centers = [];

        if ($mobile) {
            $normalizedMobile = $this->normalizeMobile($mobile);

            $response = $this->crmClient->request("rhs_servicecenters", "GET", [
                '$select' => 'rhs_servicecenterid,rhs_name,rhs_centercode,rhs_mobile,rhs_phone',
                '$filter' => "rhs_mobile eq '$normalizedMobile'",
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $centers = $data['value'] ?? [];
            }
        }

        return view('AgencyView::user-centers', [
            'user' => $user,
            'centers' => $centers,
        ]);
    }

    /**
     * نرمال‌سازی شماره موبایل برای تطبیق با CRM
     *
     * در صورت نیاز می‌توانید منطق را با فرمت واقعی داده‌های خود تنظیم کنید.
     */
    protected function normalizeMobile(string $mobile): string
    {
        $mobile = trim($mobile);

        // تبدیل اعداد فارسی به انگلیسی
        $persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $english = ['0','1','2','3','4','5','6','7','8','9'];
        $mobile = str_replace($persian, $english, $mobile);

        // اگر با +98 شروع شود → 0
        if (str_starts_with($mobile, '+98')) {
            $mobile = '0' . substr($mobile, 3);
        }

        // اگر با 98 شروع شود → 0
        if (str_starts_with($mobile, '98') && strlen($mobile) === 12) {
            $mobile = '0' . substr($mobile, 2);
        }

        return $mobile;
    }
}

