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
     * منبع اصلی تطبیق فقط CRM است:
     * - mobile کاربر بر اساس فیلد email گرفته می‌شود (یا ورودی تست)
     * - مراکز کاربر مستقیماً از جدول rhs_servicecenters در CRM با فیلتر روی rhs_mobile پیدا می‌شوند
     * - هیچ وابستگی‌ای به جدول agency_info نداریم
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

            // برای پوشش فرمت‌های مختلف شماره، چند فیلتر مختلف امتحان می‌کنیم
            $mobilesToTry = [$normalizedMobile];

            // اگر با 0 شروع می‌شود، نسخه‌های بین‌المللی را هم تست کن
            if (str_starts_with($normalizedMobile, '0') && strlen($normalizedMobile) === 11) {
                $withoutZero = substr($normalizedMobile, 1); // 912...
                $mobilesToTry[] = '98' . $withoutZero;       // 98912...
                $mobilesToTry[] = '+98' . $withoutZero;      // +98912...
            }

            $seenIds = [];

            foreach ($mobilesToTry as $m) {
                $response = $this->crmClient->request("rhs_servicecenters", "GET", [
                    '$select' => 'rhs_servicecenterid,rhs_name,rhs_centercode,rhs_mobile,rhs_phone',
                    '$filter' => "rhs_mobile eq '$m'",
                ]);

                if (! $response->successful()) {
                    continue;
                }

                $data = $response->json();
                $items = $data['value'] ?? [];

                foreach ($items as $item) {
                    $id = $item['rhs_servicecenterid'] ?? null;
                    if (! $id || isset($seenIds[$id])) {
                        continue;
                    }
                    $seenIds[$id] = true;

                    $centers[] = [
                        'service_center_id' => $id,
                        'name' => $item['rhs_name'] ?? '-',
                        'code' => $item['rhs_centercode'] ?? null,
                        'mobile' => $item['rhs_mobile'] ?? $m,
                        'phone' => $item['rhs_phone'] ?? null,
                    ];
                }
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

        // نرمال‌سازی GUID (حذف براکت و تبدیل به lowercase)
        $normalizedId = strtolower(trim($serviceCenterId, '{}'));

        // جستجوی بدهی‌ها بر اساس lookup field
        // توجه: GUID در OData باید بدون کوتیشن باشد
        $response = $this->crmClient->request("rhs_debtinformations", "GET", [
            '$select' => 'rhs_debtinformationid,rhs_name,rhs_amountowed,rhs_debtpaymentdate,rhs_paymentid',
            '$filter' => "_rhs_servicecentercode_value eq $normalizedId",
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $items = $data['value'] ?? [];

            $debts = collect($items)
                ->map(function (array $debt) {
                    $isPaid = ! empty($debt['rhs_debtpaymentdate'] ?? null) || ! empty($debt['rhs_paymentid'] ?? null);

                    $debt['is_paid'] = $isPaid;

                    return $debt;
                })
                ->toArray();
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

