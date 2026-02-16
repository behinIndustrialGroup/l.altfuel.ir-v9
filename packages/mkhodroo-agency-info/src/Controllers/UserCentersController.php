<?php

namespace Mkhodroo\AgencyInfo\Controllers;

use App\CustomClasses\zibalTest;
use App\Http\Controllers\Controller;
use App\Models\User;
use Behin\CrmClient\CrmClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
     * شروع فرآیند پرداخت بدهی
     */
    public function payDebt(string $debtId, Request $request)
    {
        /** @var User $user */
        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        // نرمال‌سازی GUID
        $normalizedId = strtolower(trim($debtId, '{}'));

        // دریافت اطلاعات بدهی از CRM
        $response = $this->crmClient->request("rhs_debtinformations($normalizedId)", "GET", [
            '$select' => 'rhs_debtinformationid,rhs_name,rhs_amountowed,rhs_debtpaymentdate,rhs_paymentid,_rhs_servicecentercode_value',
        ]);

        if (! $response->successful()) {
            abort(404, 'بدهی مورد نظر یافت نشد');
        }

        $debt = $response->json();

        // بررسی اینکه بدهی قبلاً پرداخت نشده باشد
        if (! empty($debt['rhs_debtpaymentdate']) || ! empty($debt['rhs_paymentid'])) {
            return redirect()->back()->with('error', 'این بدهی قبلاً پرداخت شده است');
        }

        $amount = $debt['rhs_amountowed'] ?? 0;

        if ($amount <= 0) {
            return redirect()->back()->with('error', 'مبلغ بدهی نامعتبر است');
        }

        // آماده‌سازی درگاه پرداخت
        $callbackUrl = route('agencyInfo.user-centers.verify-debt-payment');
        $description = sprintf(
            'پرداخت بدهی %s به مبلغ %s تومان',
            $debt['rhs_name'] ?? 'بدون عنوان',
            number_format($amount)
        );

        $authorityCode = zibalTest::getAuthority($amount, $description, $user->email, $callbackUrl);
        if (! $authorityCode) {
            return redirect()->back()->with('error', 'خطا در اتصال به درگاه پرداخت');
        }

        // ذخیره اطلاعات پرداخت در Cache برای 15 دقیقه
        Cache::put("debt_payment_{$authorityCode}", [
            'debt_id' => $normalizedId,
            'user_id' => $user->id,
            'amount' => $amount,
            'debt_name' => $debt['rhs_name'] ?? 'بدون عنوان',
            'service_center_id' => $debt['_rhs_servicecentercode_value'] ?? null,
        ], now()->addMinutes(15));

        // هدایت به درگاه پرداخت
        return redirect(config('zibal.pay_url') . $authorityCode);
    }

    /**
     * تایید پرداخت بدهی و ثبت در CRM
     */
    public function verifyDebtPayment(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        $authority = $request->Authority ?? $request->trackId;

        if (! $authority) {
            return view('AgencyView::debt-payment-result', [
                'success' => false,
                'message' => 'کد پیگیری نامعتبر است',
            ]);
        }

        // دریافت اطلاعات پرداخت از Cache
        $paymentData = Cache::get("debt_payment_{$authority}");

        if (! $paymentData) {
            return view('AgencyView::debt-payment-result', [
                'success' => false,
                'message' => 'اطلاعات پرداخت یافت نشد یا منقضی شده است',
            ]);
        }

        // تایید پرداخت با درگاه
        $refId = zibalTest::verify($request, $paymentData['amount']);

        if (! $refId) {
            return view('AgencyView::debt-payment-result', [
                'success' => false,
                'message' => 'پرداخت ناموفق بود',
                'debt_name' => $paymentData['debt_name'],
                'amount' => $paymentData['amount'],
            ]);
        }

        // استفاده از متد کمکی که فرمت‌های مختلف رو امتحان می‌کنه
        $updateResult = $this->tryUpdateDebt($paymentData['debt_id'], (string) $refId);

        // حذف اطلاعات از Cache
        Cache::forget("debt_payment_{$authority}");

        if (! $updateResult['success']) {
            // پرداخت موفق بود اما ثبت در CRM ناموفق
            return view('AgencyView::debt-payment-result', [
                'success' => true,
                'warning' => true,
                'message' => 'پرداخت با موفقیت انجام شد اما خطا در ثبت اطلاعات در سیستم رخ داد. لطفاً با پشتیبانی تماس بگیرید.',
                'ref_id' => $refId,
                'debt_name' => $paymentData['debt_name'],
                'amount' => $paymentData['amount'],
            ]);
        }

        return view('AgencyView::debt-payment-result', [
            'success' => true,
            'message' => 'پرداخت با موفقیت انجام شد',
            'ref_id' => $refId,
            'debt_name' => $paymentData['debt_name'],
            'amount' => $paymentData['amount'],
        ]);
    }

    /**
     * تلاش برای آپدیت بدهی با فرمت‌های مختلف
     */
    protected function tryUpdateDebt(string $debtId, string $refId): array
    {
        // فرمت‌های مختلف تاریخ که CRM ممکنه بپذیره
        $dateFormats = [
            now()->format('Y-m-d\TH:i:s\Z'),           // ISO 8601 UTC
            now()->format('Y-m-d\TH:i:sP'),            // ISO 8601 with timezone
            now()->format('Y-m-d'),                     // Simple date
            now()->toIso8601String(),                   // Laravel ISO 8601
            now()->toDateTimeString(),                  // Y-m-d H:i:s
        ];

        $lastError = null;

        foreach ($dateFormats as $index => $dateFormat) {
            $payload = [
                'rhs_paymentid' => (string) $refId,
                'rhs_debtpaymentdate' => $dateFormat,
            ];

            Log::info("Attempt #{$index} to update debt", [
                'debt_id' => $debtId,
                'date_format' => $dateFormat,
                'payload' => $payload,
            ]);

            $response = $this->crmClient->request(
                "rhs_debtinformations({$debtId})",
                "PATCH",
                [],
                $payload
            );

            if ($response->successful()) {
                Log::info("Successfully updated debt with format #{$index}", [
                    'date_format' => $dateFormat,
                ]);

                return [
                    'success' => true,
                    'format_used' => $dateFormat,
                ];
            }

            $lastError = [
                'status' => $response->status(),
                'body' => $response->body(),
                'format' => $dateFormat,
            ];

            Log::warning("Attempt #{$index} failed", $lastError);
        }

        Log::error('All update attempts failed', [
            'last_error' => $lastError,
        ]);

        return [
            'success' => false,
            'last_error' => $lastError,
        ];
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
