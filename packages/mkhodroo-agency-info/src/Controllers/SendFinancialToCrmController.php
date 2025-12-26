<?php

namespace Mkhodroo\AgencyInfo\Controllers;

use App\Http\Controllers\Controller;
use Behin\CrmClient\CrmClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendFinancialToCrmController extends Controller
{
    protected $crmClient;

    public function __construct(CrmClient $crmClient)
    {
        $this->crmClient = $crmClient;
    }

    /**
     * ارسال اطلاعات مالی مراکز به CRM
     */
    public function sendFinancialDataToCrm()
    {
        try {
            set_time_limit(0);
            
            $agencies = $this->getAgenciesWithCrmId();
            $totalCount = count($agencies);
            
            if ($totalCount == 0) {
                echo "<p style='color: red;'>هیچ مرکزی با CRM ID یافت نشد</p>";
                return;
            }

            echo "<h2>شروع ارسال اطلاعات مالی $totalCount مرکز به CRM</h2>";
            echo "<hr>";
            
            $successCount = 0;
            $errorCount = 0;
            $skippedCount = 0;
            $totalFinancialRecords = 0;

            foreach ($agencies as $agency) {
                echo "<h3>پردازش اطلاعات مالی: {$agency['name']}</h3>";
                echo "<ul>";

                // دریافت اطلاعات مالی این مرکز
                $financialData = $this->getFinancialDataForAgency($agency['parent_id']);
                
                if (empty($financialData)) {
                    echo "<li style='color: orange;'>⚠ هیچ اطلاعات مالی یافت نشد</li>";
                    $skippedCount++;
                } else {
                    foreach ($financialData as $financial) {
                        $result = $this->createFinancialRecord($financial, $agency['crm_service_center_id']);
                        $totalFinancialRecords++;
                        
                        if ($result['success']) {
                            $successCount++;
                            echo "<li style='color: green;'>✓ {$financial['name']} - موفق</li>";
                        } else {
                            $errorCount++;
                            echo "<li style='color: red;'>✗ {$financial['name']} - خطا: {$result['message']}</li>";
                        }
                    }
                }

                echo "</ul>";
                echo "<hr>";
                
                // استراحت کوتاه
                usleep(500000); // 0.5 ثانیه
            }

            echo "<h2>نتیجه نهایی</h2>";
            echo "<p><strong>مراکز پردازش شده: $totalCount</strong></p>";
            echo "<p><strong>رکوردهای مالی: $totalFinancialRecords</strong></p>";
            echo "<p><strong>موفق: $successCount - خطا: $errorCount - رد شده: $skippedCount</strong></p>";

        } catch (\Exception $e) {
            echo "<h2 style='color: red;'>خطا در پردازش:</h2>";
            echo "<p>" . $e->getMessage() . "</p>";
        }
    }

    /**
     * تست ارسال اطلاعات مالی
     */
    public function testFinancialData()
    {
        try {
            echo "<h2>تست ارسال اطلاعات مالی</h2>";
            
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
            
            // بررسی مراکز با CRM ID
            echo "<h3>2. بررسی مراکز با CRM ID:</h3>";
            $agencies = $this->getAgenciesWithCrmId();
            echo "<p>تعداد مراکز با CRM ID: " . count($agencies) . "</p>";
            
            if (empty($agencies)) {
                echo "<p style='color: red;'>هیچ مرکزی با CRM ID یافت نشد</p>";
                
                // بررسی اینکه آیا اصلاً رکوردی با key 'crm_service_center_id' وجود دارد
                $crmIdCount = DB::table('agency_info')
                    ->where('key', 'crm_service_center_id')
                    ->count();
                echo "<p>تعداد رکوردهای crm_service_center_id در دیتابیس: $crmIdCount</p>";
                
                if ($crmIdCount == 0) {
                    echo "<p style='color: orange;'>⚠ هیچ مرکزی در CRM ایجاد نشده است. ابتدا از روت /agency-info/send-to-crm استفاده کنید.</p>";
                }
                return;
            }
            
            $firstAgency = $agencies[0];
            
            echo "<h3>3. اطلاعات اولین مرکز:</h3>";
            echo "<pre>";
            print_r([
                'parent_id' => $firstAgency['parent_id'],
                'name' => $firstAgency['name'],
                'crm_service_center_id' => $firstAgency['crm_service_center_id']
            ]);
            echo "</pre>";
            
            // بررسی اطلاعات مالی
            echo "<h3>4. بررسی اطلاعات مالی:</h3>";
            $financialData = $this->getFinancialDataForAgency($firstAgency['parent_id']);
            echo "<p>تعداد اطلاعات مالی یافت شده: " . count($financialData) . "</p>";
            
            if (empty($financialData)) {
                echo "<p style='color: orange;'>این مرکز اطلاعات مالی ندارد</p>";
                
                // بررسی اینکه آیا اصلاً رکوردهای مالی وجود دارند
                $financialCount = DB::table('agency_info')
                    ->where('parent_id', $firstAgency['parent_id'])
                    ->whereIn('key', ['membership_96', 'debt1', 'irngv'])
                    ->count();
                echo "<p>تعداد رکوردهای مالی در دیتابیس برای این مرکز: $financialCount</p>";
                
                // نمایش تمام کلیدهای موجود برای این مرکز
                $allKeys = DB::table('agency_info')
                    ->where('parent_id', $firstAgency['parent_id'])
                    ->pluck('key')
                    ->toArray();
                echo "<p>کلیدهای موجود برای این مرکز:</p>";
                echo "<pre>" . implode(', ', $allKeys) . "</pre>";
                
                return;
            }
            
            echo "<h3>5. اطلاعات مالی یافت شده:</h3>";
            echo "<pre>";
            print_r($financialData);
            echo "</pre>";
            
            // تست ایجاد اولین رکورد مالی
            $firstFinancial = $financialData[0];
            echo "<h3>6. تست ایجاد رکورد مالی: {$firstFinancial['name']}</h3>";
            
            // نمایش داده‌های آماده برای ارسال
            $testData = [
                'rhs_name' => $firstFinancial['name'],
                'rhs_amount' => floatval($firstFinancial['amount']),
                'rhs_servicecenter@odata.bind' => "/rhs_servicecenters({$firstAgency['crm_service_center_id']})"
            ];
            
            if ($firstFinancial['pay_date']) {
                $testData['rhs_paymentdate'] = $this->formatDate($firstFinancial['pay_date']);
            }
            
            if ($firstFinancial['ref_id']) {
                $testData['rhs_trackingcode'] = $firstFinancial['ref_id'];
            }
            
            if ($firstFinancial['year']) {
                $testData['rhs_year'] = $firstFinancial['year'];
            }
            
            echo "<h4>داده‌های آماده برای ارسال:</h4>";
            echo "<pre>";
            print_r($testData);
            echo "</pre>";
            
            $result = $this->createFinancialRecord($firstFinancial, $firstAgency['crm_service_center_id']);
            
            if ($result['success']) {
                echo "<p style='color: green;'>✓ رکورد مالی با موفقیت ایجاد شد</p>";
            } else {
                echo "<p style='color: red;'>✗ خطا در ایجاد رکورد مالی: {$result['message']}</p>";
            }
            
        } catch (\Exception $e) {
            echo "<h2 style='color: red;'>خطا در تست:</h2>";
            echo "<p>" . $e->getMessage() . "</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
    }

    /**
     * دریافت مراکزی که CRM ID دارند
     */
    private function getAgenciesWithCrmId()
    {
        try {
            $agencies = [];
            
            // دریافت مراکزی که CRM ID دارند
            $crmIds = DB::table('agency_info')
                ->where('key', 'crm_service_center_id')
                ->get();
                
            foreach ($crmIds as $crmRecord) {
                // دریافت نام مرکز
                $nameRecords = DB::table('agency_info')
                    ->where('parent_id', $crmRecord->parent_id)
                    ->whereIn('key', ['firstname', 'lastname'])
                    ->get();
                    
                $firstname = '';
                $lastname = '';
                
                foreach ($nameRecords as $record) {
                    if ($record->key === 'firstname') {
                        $firstname = $record->value ?? '';
                    } elseif ($record->key === 'lastname') {
                        $lastname = $record->value ?? '';
                    }
                }
                
                $name = trim($firstname . ' ' . $lastname);
                
                $agencies[] = [
                    'parent_id' => $crmRecord->parent_id,
                    'name' => $name ?: 'نامشخص',
                    'crm_service_center_id' => $crmRecord->value
                ];
            }
            
            return $agencies;
        } catch (\Exception $e) {
            Log::error("Error getting agencies with CRM ID", [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * دریافت اطلاعات مالی یک مرکز
     */
    private function getFinancialDataForAgency($parentId)
    {
        try {
            $financialKeys = [
                'membership_96' => ['membership_96', 'membership_96_pay_date', 'membership_96_ref_id'],
                'membership_97' => ['membership_97', 'membership_97_pay_date', 'membership_97_ref_id'],
                'membership_98' => ['membership_98', 'membership_98_pay_date', 'membership_98_ref_id'],
                'membership_99' => ['membership_99', 'membership_99_pay_date', 'membership_99_ref_id'],
                'membership_00' => ['membership_00', 'membership_00_pay_date', 'membership_00_ref_id'],
                'membership_01' => ['membership_01', 'membership_01_pay_date', 'membership_01_ref_id'],
                'membership_02' => ['membership_02', 'membership_02_pay_date', 'membership_02_ref_id'],
                'membership_03' => ['membership_03', 'membership_03_pay_date', 'membership_03_ref_id'],
                'membership_04' => ['membership_04', 'membership_04_pay_date', 'membership_04_ref_id'],
                'irngv' => ['irngv', 'irngv_pay_date', 'irngv_ref_id'],
                'irngv_fee' => ['irngv_fee', 'irngv_fee_pay_date', 'irngv_fee_ref_id'],
                'lock_fee' => ['lock_fee', 'lock_fee_pay_date', 'lock_fee_ref_id'],
                'debt1' => ['debt1', 'debt1_pay_date', 'debt1_ref_id'],
                'debt2' => ['debt2', 'debt2_pay_date', 'debt2_ref_id'],
                'plate_reader' => ['plate_reader', 'plate_reader_pay_date', 'plate_reader_ref_id']
            ];

            $financialRecords = [];

            foreach ($financialKeys as $name => $keys) {
                $amountKey = $keys[0];
                $dateKey = $keys[1];
                $refKey = $keys[2];

                // دریافت مقادیر از دیتابیس
                $records = DB::table('agency_info')
                    ->where('parent_id', $parentId)
                    ->whereIn('key', $keys)
                    ->get()
                    ->keyBy('key');

                $amount = isset($records[$amountKey]) ? $records[$amountKey]->value : null;
                $payDate = isset($records[$dateKey]) ? $records[$dateKey]->value : null;
                $refId = isset($records[$refKey]) ? $records[$refKey]->value : null;

                // اگر حداقل مبلغ وجود داشت، رکورد را اضافه کن
                if ($amount && $amount !== '' && $amount !== '0') {
                    $financialRecords[] = [
                        'name' => $name,
                        'amount' => $amount,
                        'pay_date' => $payDate,
                        'ref_id' => $refId,
                        'year' => $this->extractYearFromName($name)
                    ];
                }
            }

            return $financialRecords;
        } catch (\Exception $e) {
            Log::error("Error getting financial data for agency", [
                'parent_id' => $parentId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * استخراج سال از نام پرداخت
     */
    private function extractYearFromName($name)
    {
        if (preg_match('/membership_(\d{2})/', $name, $matches)) {
            $year = intval($matches[1]);
            return $year >= 96 ? 1300 + $year : 1400 + $year; // تبدیل به سال شمسی کامل
        }
        
        return null; // برای سایر موارد مثل irngv، debt
    }

    /**
     * ایجاد رکورد مالی در CRM
     */
    private function createFinancialRecord($financial, $serviceCenterId)
    {
        try {
            $financialData = [
                'rhs_name' => $financial['name'],
                'rhs_amount' => floatval($financial['amount']),
                'rhs_servicecenter@odata.bind' => "/rhs_servicecenters($serviceCenterId)"
            ];

            // اضافه کردن تاریخ پرداخت اگر وجود داشت
            if ($financial['pay_date']) {
                $financialData['rhs_paymentdate'] = $this->formatDate($financial['pay_date']);
            }

            // اضافه کردن کد پیگیری اگر وجود داشت
            if ($financial['ref_id']) {
                $financialData['rhs_trackingcode'] = $financial['ref_id'];
            }

            // اضافه کردن سال اگر وجود داشت
            if ($financial['year']) {
                $financialData['rhs_year'] = $financial['year'];
            }

            // حذف فیلدهای خالی
            $financialData = array_filter($financialData, function($value) {
                return $value !== '' && $value !== null;
            });

            $response = $this->crmClient->request("rhs_financialinformationcenters", "POST", $financialData);
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'رکورد مالی ایجاد شد'
                ];
            }

            Log::error("Failed to create financial record", [
                'financial_data' => $financialData,
                'response_status' => $response->status(),
                'response_body' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ایجاد رکورد مالی: ' . $response->status()
            ];

        } catch (\Exception $e) {
            Log::error("Exception creating financial record", [
                'financial' => $financial,
                'service_center_id' => $serviceCenterId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Exception در ایجاد رکورد مالی: ' . $e->getMessage()
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