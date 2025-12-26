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
                echo "<p style='color: orange;'>⚠ ابتدا از روت /agency-info/send-to-crm برای ایجاد مراکز در CRM استفاده کنید.</p>";
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
            
            // تست دسترسی به جدول مالی
            echo "<h3>2. تست دسترسی به جداول مالی مختلف:</h3>";
            
            $possibleTableNames = [
                'rhs_financialinformationcenters',
                'rhs_financialinformationcenter', 
                'new_financialinformation',
                'rhs_paymentinfo',
                'rhs_payment'
            ];
            
            $workingTable = null;
            
            foreach ($possibleTableNames as $tableName) {
                try {
                    echo "<h4>تست جدول: $tableName</h4>";
                    $financialTestResponse = $this->crmClient->request($tableName, "GET", [
                        '$select' => 'createdon',
                        '$top' => 1
                    ]);
                    
                    if ($financialTestResponse->successful()) {
                        echo "<p style='color: green;'>✓ دسترسی به جدول $tableName موفق</p>";
                        $financialData = $financialTestResponse->json();
                        echo "<p>تعداد رکوردهای موجود: " . count($financialData['value'] ?? []) . "</p>";
                        
                        // اگر رکوردی وجود داشت، فیلدهای آن را نمایش بده
                        if (!empty($financialData['value'])) {
                            echo "<h5>فیلدهای موجود در اولین رکورد:</h5>";
                            echo "<pre>";
                            print_r(array_keys($financialData['value'][0]));
                            echo "</pre>";
                        }
                        
                        $workingTable = $tableName;
                        break; // اولین جدول کاری را پیدا کردیم
                        
                    } else {
                        echo "<p style='color: red;'>✗ خطا در دسترسی به جدول $tableName: " . $financialTestResponse->status() . "</p>";
                        if ($financialTestResponse->status() != 404) {
                            echo "<pre>" . substr($financialTestResponse->body(), 0, 200) . "...</pre>";
                        }
                    }
                } catch (\Exception $e) {
                    echo "<p style='color: red;'>✗ Exception در دسترسی به جدول $tableName: " . $e->getMessage() . "</p>";
                }
            }
            
            if (!$workingTable) {
                echo "<p style='color: red;'>⚠ هیچ جدول مالی قابل دسترسی یافت نشد</p>";
                return;
            }
            
            echo "<p style='color: blue;'><strong>جدول کاری انتخاب شده: $workingTable</strong></p>";
            
            // بررسی مراکز با CRM ID
            echo "<h3>3. بررسی مراکز با CRM ID:</h3>";
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
            
            echo "<h3>4. اطلاعات اولین مرکز:</h3>";
            echo "<pre>";
            print_r([
                'parent_id' => $firstAgency['parent_id'],
                'name' => $firstAgency['name'],
                'crm_service_center_id' => $firstAgency['crm_service_center_id']
            ]);
            echo "</pre>";
            
            // بررسی اطلاعات مالی
            echo "<h3>5. بررسی اطلاعات مالی:</h3>";
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
            
            echo "<h3>6. اطلاعات مالی یافت شده:</h3>";
            echo "<pre>";
            print_r($financialData);
            echo "</pre>";
            
            // تست ایجاد اولین رکورد مالی
            $firstFinancial = $financialData[0];
            echo "<h3>7. تست ایجاد رکورد مالی: {$firstFinancial['name']}</h3>";
            
            // نمایش داده‌های آماده برای ارسال
            $testData = [
                'rhs_name' => $firstFinancial['name'],
                'rhs_amount' => floatval($firstFinancial['amount'])
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
            
            echo "<p><strong>استفاده از جدول: $workingTable</strong></p>";
            
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
            // تست با نام‌های مختلف جدول مالی
            $possibleTableNames = [
                'rhs_financialinformationcenters',
                'rhs_financialinformationcenter', 
                'new_financialinformation',
                'rhs_paymentinfo',
                'rhs_payment'
            ];
            
            // تست با نام‌های مختلف فیلد lookup
            $possibleLookupFields = [
                'rhs_servicecenter@odata.bind',
                'new_servicecenter@odata.bind', 
                'rhs_servicecenterlookup@odata.bind',
                'rhs_servicecenterid@odata.bind',
                '_rhs_servicecenter_value@odata.bind'
            ];
            
            echo "<h4>تست نام‌های مختلف جدول و فیلد lookup:</h4>";
            
            foreach ($possibleTableNames as $tableName) {
                echo "<h5>تست جدول: $tableName</h5>";
                
                // ابتدا تست دسترسی به جدول
                $testTableResponse = $this->crmClient->request($tableName, "GET", ['$top' => 1]);
                
                if (!$testTableResponse->successful()) {
                    echo "<p style='color: gray;'>- جدول $tableName در دسترس نیست (Status: " . $testTableResponse->status() . ")</p>";
                    continue;
                }
                
                echo "<p style='color: green;'>✓ جدول $tableName در دسترس است</p>";
                
                // تست با فیلدهای lookup مختلف
                foreach ($possibleLookupFields as $lookupField) {
                    echo "<h6>تست با فیلد lookup: $lookupField</h6>";
                    
                    $financialData = [
                        'rhs_name' => $financial['name'],
                        'rhs_amount' => floatval($financial['amount']),
                        $lookupField => "/rhs_servicecenters($serviceCenterId)"
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

                    echo "<p>درحال ارسال به $tableName با فیلد $lookupField:</p>";
                    echo "<pre>";
                    print_r($financialData);
                    echo "</pre>";

                    $response = $this->crmClient->request($tableName, "POST", $financialData);
                    
                    echo "<p><strong>Status Code:</strong> " . $response->status() . "</p>";
                    
                    if ($response->successful()) {
                        echo "<p style='color: green;'>✓ موفق با جدول $tableName و فیلد: $lookupField</p>";
                        return [
                            'success' => true,
                            'message' => "رکورد مالی با جدول $tableName و فیلد $lookupField ایجاد شد"
                        ];
                    } else {
                        echo "<p style='color: red;'>✗ ناموفق با فیلد: $lookupField</p>";
                        echo "<p><strong>خطا:</strong> " . substr($response->body(), 0, 200) . "...</p>";
                    }
                    
                    echo "<hr>";
                }
                
                // اگر هیچ lookup کار نکرد، تست بدون lookup برای این جدول
                echo "<h6>تست بدون فیلد lookup برای جدول $tableName:</h6>";
                
                $financialDataWithoutLookup = [
                    'rhs_name' => $financial['name'],
                    'rhs_amount' => floatval($financial['amount'])
                ];

                // اضافه کردن سایر فیلدها
                if ($financial['pay_date']) {
                    $financialDataWithoutLookup['rhs_paymentdate'] = $this->formatDate($financial['pay_date']);
                }

                if ($financial['ref_id']) {
                    $financialDataWithoutLookup['rhs_trackingcode'] = $financial['ref_id'];
                }

                if ($financial['year']) {
                    $financialDataWithoutLookup['rhs_year'] = $financial['year'];
                }

                echo "<p>درحال ارسال به $tableName بدون lookup:</p>";
                echo "<pre>";
                print_r($financialDataWithoutLookup);
                echo "</pre>";

                $response = $this->crmClient->request($tableName, "POST", $financialDataWithoutLookup);
                
                echo "<p><strong>Status Code:</strong> " . $response->status() . "</p>";
                
                if ($response->successful()) {
                    echo "<p style='color: green;'>✓ موفق با جدول $tableName بدون lookup</p>";
                    return [
                        'success' => true,
                        'message' => "رکورد مالی با جدول $tableName بدون lookup ایجاد شد"
                    ];
                } else {
                    echo "<p style='color: red;'>✗ ناموفق با جدول $tableName بدون lookup</p>";
                    echo "<p><strong>خطا:</strong> " . substr($response->body(), 0, 200) . "...</p>";
                }
                
                echo "<hr>";
            }

            // تحلیل خطاهای رایج
            $errorMessage = "خطا در ایجاد رکورد مالی - هیچ ترکیب جدول/فیلدی کار نکرد";

            Log::error("Failed to create financial record with all combinations", [
                'financial_data' => $financial,
                'service_center_id' => $serviceCenterId,
                'tested_tables' => $possibleTableNames,
                'tested_lookup_fields' => $possibleLookupFields
            ]);

            return [
                'success' => false,
                'message' => $errorMessage
            ];

        } catch (\Exception $e) {
            echo "<h4 style='color: red;'>Exception رخ داد:</h4>";
            echo "<p><strong>پیام خطا:</strong> " . $e->getMessage() . "</p>";
            echo "<p><strong>فایل:</strong> " . $e->getFile() . "</p>";
            echo "<p><strong>خط:</strong> " . $e->getLine() . "</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
            
            Log::error("Exception creating financial record", [
                'financial' => $financial,
                'service_center_id' => $serviceCenterId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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

    /**
     * فرمت کردن fin_green برای statuscode
     */
    private function formatFinGreen($finGreen)
    {
        return match (strtolower(trim($finGreen ?? ''))) {
            '1', 1, 'true', true, 'ok' => 1, // فعال = 1 در CRM
            '0', 0, 'false', false, 'not ok', 'notok', 'not_ok' => 2, // غیرفعال = 2 در CRM
            default => 1 // پیش‌فرض فعال
        };
    }
}