<?php

namespace Mkhodroo\AgencyInfo\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Behin\CrmClient\CrmClient;
use Illuminate\Http\Request;

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
    public function index(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        // اگر موبایل به‌عنوان تست ارسال شده باشد، از آن استفاده می‌کنیم
        $mobile = $request->get('mobile', $user->email);

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
     * نمایش بدهی‌های یک مرکز خاص از CRM
     */
    public function debts(string $serviceCenterId, Request $request)
    {
        /** @var User $user */
        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        // اطلاعات نمایشی مرکز (اختیاری، اگر از لیست ارسال شده باشد)
        $centerName = $request->get('name');
        $centerCode = $request->get('code');

        $debts = [];

        // سعی ۱: بر اساس نام lookup که در SendDebtToCrmController استفاده شده (_rhs_servicecentercode_value)
        $responses = [];
        $filters = [
            "_rhs_servicecentercode_value eq '$serviceCenterId'",
            "_rhs_servicecenter_value eq '$serviceCenterId'",
        ];

        foreach ($filters as $filter) {
            $response = $this->crmClient->request("rhs_debtinformations", "GET", [
                '$select' => 'rhs_debtinformationid,rhs_name,rhs_amountowed,rhs_debtpaymentdate,rhs_paymentid',
                '$filter' => $filter,
            ]);

            if (! $response->successful()) {
                continue;
            }

            $data = $response->json();
            $items = $data['value'] ?? [];

            if (! empty($items)) {
                $debts = collect($items)
                    ->map(function (array $debt) {
                        $isPaid = ! empty($debt['rhs_debtpaymentdate'] ?? null) || ! empty($debt['rhs_paymentid'] ?? null);

                        $debt['is_paid'] = $isPaid;

                        return $debt;
                    })
                    ->toArray();

                break;
            }
        }

        return view('AgencyView::user-debts', [
            'user' => $user,
            'serviceCenterId' => $serviceCenterId,
            'centerName' => $centerName,
            'centerCode' => $centerCode,
            'debts' => $debts,
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

