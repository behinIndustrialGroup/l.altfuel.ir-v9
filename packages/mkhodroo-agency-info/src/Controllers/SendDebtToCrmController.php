<?php

namespace Mkhodroo\AgencyInfo\Controllers;

use App\Http\Controllers\Controller;
use Behin\CrmClient\CrmClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendDebtToCrmController extends Controller
{
    protected $crmClient;

    public function __construct(CrmClient $crmClient)
    {
        $this->crmClient = $crmClient;
    }

    /**
     * ارسال اطلاعات بدهی مراکز به CRM
     */
    public function sendDebtDataToCrm()
    {
        set_time_limit(0);

        echo "<h2>شروع بررسی اطلاعات بدهی مراکز</h2><hr>";

        $totalCenters = 0;
        $totalDebtRecords = 0;
        $columns = [
            'crm_service_center_id',
            'firstname',
            'lastname',
            'debt1',
            'debt1_pay_date',
            'debt1_ref_id',
            'debt2',
            'debt2_pay_date',
            'debt2_ref_id',
        ];

        $debtCols = [
            'debt1',
            'debt2',
        ];

        $selects = ['parent_id'];

        foreach ($columns as $col) {
            $selects[] = DB::raw("MAX(CASE WHEN `key` = '$col' THEN value END) AS `$col`");
        }

        $data = DB::table('agency_info')
            ->select($selects)
            ->havingNotNull('crm_service_center_id')
            ->groupBy('parent_id')
            ->orderBy('parent_id')
            ->chunk(20, function ($agencies) use (&$totalCenters, &$totalDebtRecords, $debtCols) {
                echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr style='background-color: #f0f0f0;'>";
                echo "<th>نام مرکز</th>";
                echo "<th>بدهی اول</th>";
                echo "<th>بدهی دوم</th>";
                echo "<th>وضعیت</th>";
                echo "</tr>";

                foreach ($agencies as $agency) {
                    $totalCenters++;
                    echo "<tr>";
                    echo "<td>{$agency->firstname} {$agency->lastname}</td>";
                    echo "<td>{$agency->debt1}</td>";
                    echo "<td>{$agency->debt2}</td>";
                    echo "<td>";

                    foreach ($debtCols as $col) {
                        $paydate = $col . '_pay_date';
                        $refId = $col . '_ref_id';
                        $debt['name'] = $col;
                        $debt['display_name'] = $col === 'debt1' ? 'بدهی اول' : 'بدهی دوم';
                        $debt['amount'] = $agency->$col;
                        $debt['pay_date'] = $agency->$paydate;
                        $debt['ref_id'] = $agency->$refId;

                        if ($debt['amount'] && $debt['amount'] !== '' && $debt['amount'] !== '0') {
                            $totalDebtRecords++;
                            $response = $this->createDebtRecord($debt, $agency->crm_service_center_id);
                            echo $response['message'] . " ";
                        }
                    }

                    echo "</td>";
                    echo "</tr>";

                    flush();
                }
                echo "</table>";
            });

        echo "<h2>پایان پردازش</h2>";
        echo "<p>مراکز: $totalCenters</p>";
        echo "<p>رکوردهای بدهی: $totalDebtRecords</p>";
    }

    /**
     * تست ارسال اطلاعات بدهی
     */
    public function testDebtData()
    {
        try {
            echo "<h2>تست ارسال اطلاعات بدهی</h2>";

            // تست اتصال به CRM
            echo "<h3>1. تست اتصال به CRM:</h3>";
            try {
                $testResponse = $this->crmClient->request("rhs_servicecenters", "GET", [
                    '$select' => 'rhs_servicecenterid,rhs_name',
                    '$top' => 1
                ]);

                if ($testResponse->successful()) {
                    echo "<p style='color: green;'>✓ اتصال به CRM موفق</p>";
                } else {
                    echo "<p style='color: red;'>✗ خطا در اتصال به CRM: " . $testResponse->status() . "</p>";
                    echo "<pre>" . $testResponse->body() . "</pre>";
                    return;
                }
            } catch (\Exception $e) {
                echo "<p style='color: red;'>✗ Exception در اتصال به CRM: " . $e->getMessage() . "</p>";
                return;
            }

            // تست دسترسی به جدول بدهی
            echo "<h3>2. تست دسترسی به جدول rhs_debtinformations:</h3>";
            try {
                $debtTestResponse = $this->crmClient->request("rhs_debtinformations", "GET", [
                    '$select' => 'rhs_debtinformationid,rhs_name',
                    '$top' => 1
                ]);

                if ($debtTestResponse->successful()) {
                    echo "<p style='color: green;'>✓ دسترسی به جدول بدهی موفق</p>";
                    $debtData = $debtTestResponse->json();
                    echo "<p>تعداد رکوردهای بدهی موجود: " . count($debtData['value'] ?? []) . "</p>";
                } else {
                    echo "<p style='color: red;'>✗ خطا در دسترسی به جدول بدهی: " . $debtTestResponse->status() . "</p>";
                    echo "<pre>" . $debtTestResponse->body() . "</pre>";

                    if ($debtTestResponse->status() == 404) {
                        echo "<p style='color: orange;'>⚠ احتمالاً جدول rhs_debtinformation در CRM وجود ندارد یا نام آن متفاوت است</p>";
                    }
                    return;
                }
            } catch (\Exception $e) {
                echo "<p style='color: red;'>✗ Exception در دسترسی به جدول بدهی: " . $e->getMessage() . "</p>";
                return;
            }

            echo "<p style='color: green;'>✓ تست‌ها موفق بود. می‌توانید از متد sendDebtDataToCrm استفاده کنید.</p>";

        } catch (\Exception $e) {
            echo "<h2 style='color: red;'>خطا در تست:</h2>";
            echo "<p>" . $e->getMessage() . "</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
    }

    /**
     * چک کردن وجود رکورد بدهی قبلی در CRM
     */
    private function checkExistingDebtRecord($debt, $serviceCenterId)
    {
        try {
            $debtName = str_replace("'", "''", $debt['display_name']);

            $filter = "rhs_name eq '$debtName' and rhs_amountowed eq " . floatval($debt['amount']) . " and _rhs_servicecentercode_value eq '$serviceCenterId'";

            $response = $this->crmClient->request("rhs_debtinformations", "GET", [
                '$filter' => $filter,
                '$select' => 'rhs_debtinformationid,rhs_name,rhs_amountowed',
                '$top' => 1
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $records = $data['value'] ?? [];

                return count($records) > 0;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Error checking existing debt record", [
                'debt' => $debt,
                'service_center_id' => $serviceCenterId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }





    /**
     * ایجاد رکورد بدهی در CRM
     */
    private function createDebtRecord($debt, $serviceCenterId)
    {
        try {
            $debtData = [
                'rhs_name' => $debt['display_name'],
                'rhs_ServiceCenterCode@odata.bind' => "/rhs_servicecenters($serviceCenterId)"
            ];

            if (isset($debt['amount']) && $debt['amount'] !== '' && $debt['amount'] !== '0' && $debt['amount'] !== 0) {
                $debtData['rhs_amountowed'] = floatval($debt['amount']);
            }

            if (isset($debt['pay_date']) && $debt['pay_date']) {
                $debtData['rhs_debtpaymentdate'] = $this->formatDate($debt['pay_date']);
            }

            if (isset($debt['ref_id']) && $debt['ref_id']) {
                $debtData['rhs_paymentid'] = $debt['ref_id'];
            }

            $debtData = array_filter($debtData, function ($value) {
                return $value !== '' && $value !== null;
            });

            $response = $this->crmClient->request("rhs_debtinformations", "POST", $debtData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => '✓'
                ];
            }

            $statusCode = $response->status();
            $errorMessage = "✗ ($statusCode)";

            Log::error("Failed to create debt record", [
                'debt_data' => $debtData,
                'response_status' => $statusCode,
                'response_body' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => $errorMessage
            ];

        } catch (\Exception $e) {
            Log::error("Exception creating debt record", [
                'debt' => $debt,
                'service_center_id' => $serviceCenterId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => '✗ Exception'
            ];
        }
    }

    /**
     * فرمت کردن تاریخ برای CRM
     */
    private function formatDate($date)
    {
        if (!$date) return null;
        
        try {
            return \Carbon\Carbon::parse($date)->toIso8601String();
        } catch (\Exception $e) {
            return null;
        }
    }
}