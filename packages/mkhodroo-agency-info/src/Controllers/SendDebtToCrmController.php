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
        try {
            set_time_limit(0);
            
            $agencies = $this->getAgenciesWithCrmId();
            $totalCount = count($agencies);
            
            if ($totalCount == 0) {
                echo "<p style='color: red;'>هیچ مرکزی با CRM ID یافت نشد</p>";
                return;
            }

            echo "<h2>شروع ارسال اطلاعات بدهی $totalCount مرکز به CRM</h2>";
            echo "<hr>";
            
            $successCount = 0;
            $errorCount = 0;
            $skippedCount = 0;
            $totalDebtRecords = 0;

            foreach ($agencies as $agency) {
                echo "<h3>پردازش اطلاعات بدهی: {$agency['name']}</h3>";
                echo "<ul>";

                // دریافت اطلاعات بدهی این مرکز
                $debtData = $this->getDebtDataForAgency($agency['parent_id']);
                
                if (empty($debtData)) {
                    echo "<li style='color: orange;'>⚠ هیچ اطلاعات بدهی یافت نشد</li>";
                    $skippedCount++;
                } else {
                    // پردازش اطلاعات بدهی
                    foreach ($debtData as $debt) {
                        $result = $this->createDebtRecord($debt, $agency['crm_service_center_id']);
                        $totalDebtRecords++;
                        
                        if ($result['success']) {
                            $successCount++;
                            echo "<li style='color: blue;'>✓ {$debt['display_name']} - موفق</li>";
                        } else {
                            $errorCount++;
                            echo "<li style='color: red;'>✗ {$debt['display_name']} - خطا: {$result['message']}</li>";
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
            echo "<p><strong>رکوردهای بدهی: $totalDebtRecords</strong></p>";
            echo "<p><strong>موفق: $successCount - خطا: $errorCount - رد شده: $skippedCount</strong></p>";

        } catch (\Exception $e) {
            echo "<h2 style='color: red;'>خطا در پردازش:</h2>";
            echo "<p>" . $e->getMessage() . "</p>";
        }
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
            echo "<h3>2. تست دسترسی به جدول rhs_debtinformation:</h3>";
            try {
                $debtTestResponse = $this->crmClient->request("rhs_debtinformation", "GET", [
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
            
            // بررسی اطلاعات بدهی
            echo "<h3>5. بررسی اطلاعات بدهی:</h3>";
            $debtData = $this->getDebtDataForAgency($firstAgency['parent_id']);
            echo "<p>تعداد اطلاعات بدهی یافت شده: " . count($debtData) . "</p>";
            
            if (empty($debtData)) {
                echo "<p style='color: orange;'>این مرکز اطلاعات بدهی ندارد</p>";
                
                // بررسی اینکه آیا اصلاً رکوردهای بدهی وجود دارند
                $debtCount = DB::table('agency_info')
                    ->where('parent_id', $firstAgency['parent_id'])
                    ->whereIn('key', ['debt1', 'debt2'])
                    ->count();
                echo "<p>تعداد رکوردهای بدهی در دیتابیس برای این مرکز: $debtCount</p>";
                
                // نمایش تمام کلیدهای موجود برای این مرکز
                $allKeys = DB::table('agency_info')
                    ->where('parent_id', $firstAgency['parent_id'])
                    ->pluck('key')
                    ->toArray();
                echo "<p>کلیدهای موجود برای این مرکز:</p>";
                echo "<pre>" . implode(', ', $allKeys) . "</pre>";
                
                return;
            }
            
            echo "<h3>6. اطلاعات بدهی یافت شده:</h3>";
            echo "<pre>";
            print_r($debtData);
            echo "</pre>";
            
            // تست ایجاد اولین رکورد بدهی
            $firstDebt = $debtData[0];
            echo "<h3>7. تست ایجاد رکورد بدهی: {$firstDebt['display_name']}</h3>";
            
            $result = $this->createDebtRecord($firstDebt, $firstAgency['crm_service_center_id']);
            
            if ($result['success']) {
                echo "<p style='color: green;'>✓ رکورد بدهی با موفقیت ایجاد شد</p>";
            } else {
                echo "<p style='color: red;'>✗ خطا در ایجاد رکورد بدهی: {$result['message']}</p>";
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
     * دریافت اطلاعات بدهی یک مرکز (فقط debt1 و debt2)
     */
    private function getDebtDataForAgency($parentId)
    {
        try {
            $debtKeys = [
                'debt1' => ['debt1', 'debt1_pay_date', 'debt1_ref_id', 'بدهی اول'],
                'debt2' => ['debt2', 'debt2_pay_date', 'debt2_ref_id', 'بدهی دوم']
            ];

            $debtRecords = [];

            foreach ($debtKeys as $name => $keys) {
                $amountKey = $keys[0];
                $dateKey = $keys[1];
                $refKey = $keys[2];
                $displayName = $keys[3];

                // دریافت مقادیر از دیتابیس
                $records = DB::table('agency_info')
                    ->where('parent_id', $parentId)
                    ->whereIn('key', [$amountKey, $dateKey, $refKey])
                    ->get()
                    ->keyBy('key');

                $amount = isset($records[$amountKey]) ? $records[$amountKey]->value : null;
                $payDate = isset($records[$dateKey]) ? $records[$dateKey]->value : null;
                $refId = isset($records[$refKey]) ? $records[$refKey]->value : null;

                // اگر حداقل مبلغ وجود داشت، رکورد را اضافه کن
                if ($amount && $amount !== '' && $amount !== '0') {
                    $debtRecords[] = [
                        'name' => $name,
                        'display_name' => $displayName,
                        'amount' => $amount,
                        'pay_date' => $payDate,
                        'ref_id' => $refId
                    ];
                }
            }

            return $debtRecords;
        } catch (\Exception $e) {
            Log::error("Error getting debt data for agency", [
                'parent_id' => $parentId,
                'error' => $e->getMessage()
            ]);
            return [];
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
                'rhs_amountowed' => floatval($debt['amount']),
                'rhs_servicecentercode' => $serviceCenterId
            ];

            // اضافه کردن تاریخ پرداخت اگر وجود داشت
            if ($debt['pay_date']) {
                $debtData['rhs_debtpaymentdate'] = $this->formatDate($debt['pay_date']);
            }

            // اضافه کردن کد پیگیری اگر وجود داشت
            if ($debt['ref_id']) {
                $debtData['rhs_paymentid'] = $debt['ref_id'];
            }

            // حذف فیلدهای خالی
            $debtData = array_filter($debtData, function($value) {
                return $value !== '' && $value !== null;
            });

            echo "<h4>درحال ارسال بدهی به CRM:</h4>";
            echo "<p><strong>نام انگلیسی:</strong> {$debt['name']}</p>";
            echo "<p><strong>نام فارسی:</strong> {$debt['display_name']}</p>";
            echo "<pre>";
            print_r($debtData);
            echo "</pre>";

            $response = $this->crmClient->request("rhs_debtinformation", "POST", $debtData);
            
            echo "<h4>پاسخ CRM برای بدهی:</h4>";
            echo "<p><strong>Status Code:</strong> " . $response->status() . "</p>";
            echo "<p><strong>Response Body:</strong></p>";
            echo "<pre>" . $response->body() . "</pre>";
            
            if ($response->successful()) {
                echo "<p style='color: green;'>✓ درخواست بدهی موفق بود</p>";
                return [
                    'success' => true,
                    'message' => 'رکورد بدهی ایجاد شد'
                ];
            }

            // تحلیل خطاهای رایج
            $errorBody = $response->body();
            $statusCode = $response->status();
            
            $errorMessage = "خطا در ایجاد رکورد بدهی - Status: $statusCode";
            
            if ($statusCode == 400) {
                $errorMessage .= " (Bad Request - احتمالاً فیلد اجباری خالی است یا فرمت داده اشتباه است)";
            } elseif ($statusCode == 404) {
                $errorMessage .= " (Not Found - احتمالاً Service Center ID اشتباه است یا جدول rhs_debtinformation وجود ندارد)";
            } elseif ($statusCode == 401) {
                $errorMessage .= " (Unauthorized - مشکل احراز هویت)";
            } elseif ($statusCode == 403) {
                $errorMessage .= " (Forbidden - عدم دسترسی)";
            }

            Log::error("Failed to create debt record", [
                'debt_data' => $debtData,
                'response_status' => $response->status(),
                'response_body' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => $errorMessage
            ];

        } catch (\Exception $e) {
            echo "<h4 style='color: red;'>Exception در ایجاد بدهی رخ داد:</h4>";
            echo "<p><strong>پیام خطا:</strong> " . $e->getMessage() . "</p>";
            echo "<p><strong>فایل:</strong> " . $e->getFile() . "</p>";
            echo "<p><strong>خط:</strong> " . $e->getLine() . "</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
            
            Log::error("Exception creating debt record", [
                'debt' => $debt,
                'service_center_id' => $serviceCenterId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Exception در ایجاد رکورد بدهی: ' . $e->getMessage()
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