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
     * ارسال اطلاعات بدهی مراکز به CRM (با پردازش chunk به chunk)
     */
    public function sendDebtDataToCrm()
    {
        try {
            set_time_limit(0);
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', 0);
            @apache_setenv('no-gzip', 1);
            @ini_set('implicit_flush', 1);
            ob_implicit_flush(1);
            
            if (ob_get_level() == 0) ob_start();
            
            $chunkSize = 10; // تعداد مراکز در هر chunk
            $offset = request()->get('offset', 0);
            
            $agencies = $this->getAgenciesWithCrmId();
            $totalCount = count($agencies);
            
            if ($totalCount == 0) {
                echo "<p style='color: red;'>هیچ مرکزی با CRM ID یافت نشد</p>";
                return;
            }

            // محاسبه chunk فعلی
            $currentChunk = array_slice($agencies, $offset, $chunkSize);
            $remainingCount = $totalCount - $offset - count($currentChunk);
            
            echo "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body>";
            echo "<h2>پردازش بدهی مراکز - Chunk " . (floor($offset / $chunkSize) + 1) . "</h2>";
            echo "<p><strong>مراکز کل: $totalCount | پردازش شده: $offset | باقیمانده: " . ($remainingCount + count($currentChunk)) . "</strong></p>";
            echo "<hr>";
            
            $successCount = 0;
            $errorCount = 0;
            $skippedCount = 0;

            foreach ($currentChunk as $agency) {
                echo "<h3>پردازش: {$agency['name']}</h3>";
                echo "<ul>";
                ob_flush();
                flush();

                $debtData = $this->getDebtDataForAgency($agency['parent_id']);
                
                if (empty($debtData)) {
                    echo "<li style='color: orange;'>⚠ بدون بدهی</li>";
                    $skippedCount++;
                } else {
                    foreach ($debtData as $debt) {
                        $existingRecord = $this->checkExistingDebtRecord($debt, $agency['crm_service_center_id']);
                        
                        if ($existingRecord) {
                            $skippedCount++;
                            echo "<li style='color: orange;'>⚠ {$debt['display_name']} - تکراری</li>";
                        } else {
                            $result = $this->createDebtRecord($debt, $agency['crm_service_center_id'], false);
                            
                            if ($result['success']) {
                                $successCount++;
                                echo "<li style='color: green;'>✓ {$debt['display_name']}</li>";
                            } else {
                                $errorCount++;
                                echo "<li style='color: red;'>✗ {$debt['display_name']} - {$result['message']}</li>";
                            }
                        }
                        ob_flush();
                        flush();
                    }
                }

                echo "</ul>";
                ob_flush();
                flush();
                usleep(300000);
            }

            echo "<hr>";
            echo "<p><strong>این Chunk: موفق: $successCount | خطا: $errorCount | رد شده: $skippedCount</strong></p>";
            
            // اگر مراکز بیشتری باقی مانده، دکمه ادامه نمایش بده
            if ($remainingCount > 0) {
                $nextOffset = $offset + $chunkSize;
                echo "<br><a href='?offset=$nextOffset' style='display:inline-block;padding:10px 20px;background:#4CAF50;color:white;text-decoration:none;border-radius:5px;'>ادامه پردازش ($remainingCount مرکز باقیمانده)</a>";
                echo "<script>setTimeout(function(){ window.location.href='?offset=$nextOffset'; }, 2000);</script>";
            } else {
                echo "<h2 style='color: green;'>✓ پردازش تمام مراکز تکمیل شد</h2>";
            }
            
            echo "</body></html>";
            ob_end_flush();

        } catch (\Exception $e) {
            echo "<h2 style='color: red;'>خطا: " . $e->getMessage() . "</h2>";
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
     * چک کردن وجود رکورد بدهی قبلی در CRM (بهبود یافته)
     */
    private function checkExistingDebtRecord($debt, $serviceCenterId)
    {
        try {
            // جستجوی دقیق‌تر با escape کردن single quote
            $debtName = str_replace("'", "''", $debt['display_name']);
            $amount = floatval($debt['amount']);
            
            // فیلتر بر اساس نام و service center (بدون مبلغ برای جلوگیری از مشکلات float)
            $filter = "rhs_name eq '$debtName' and _rhs_servicecentercode_value eq '$serviceCenterId'";

            $response = $this->crmClient->request("rhs_debtinformations", "GET", [
                '$filter' => $filter,
                '$select' => 'rhs_debtinformationid,rhs_name,rhs_amountowed',
                '$top' => 5
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $records = $data['value'] ?? [];

                // چک دقیق‌تر روی مبلغ (با tolerance برای float)
                foreach ($records as $record) {
                    $existingAmount = floatval($record['rhs_amountowed'] ?? 0);
                    if (abs($existingAmount - $amount) < 0.01) {
                        return true;
                    }
                }
                
                return false;
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
    private function createDebtRecord($debt, $serviceCenterId, $verbose = false)
    {
        try {
            $debtData = [
                'rhs_name' => $debt['display_name'],
                'rhs_amountowed' => floatval($debt['amount']),
                'rhs_ServiceCenterCode@odata.bind' => "/rhs_servicecenters($serviceCenterId)"
            ];

            if ($debt['pay_date']) {
                $debtData['rhs_debtpaymentdate'] = $this->formatDate($debt['pay_date']);
            }

            if ($debt['ref_id']) {
                $debtData['rhs_paymentid'] = $debt['ref_id'];
            }

            $debtData = array_filter($debtData, function($value) {
                return $value !== '' && $value !== null;
            });

            if ($verbose) {
                echo "<h4>ارسال به CRM:</h4>";
                echo "<pre>" . json_encode($debtData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</pre>";
            }

            $response = $this->crmClient->request("rhs_debtinformations", "POST", $debtData);
            
            if ($verbose) {
                echo "<h4>پاسخ CRM:</h4>";
                echo "<p>Status: " . $response->status() . "</p>";
                echo "<pre>" . $response->body() . "</pre>";
            }
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'موفق'
                ];
            }

            $statusCode = $response->status();
            $errorMessage = "Status: $statusCode";
            
            if ($statusCode == 400) {
                $errorMessage = "Bad Request";
            } elseif ($statusCode == 404) {
                $errorMessage = "Not Found";
            } elseif ($statusCode == 401) {
                $errorMessage = "Unauthorized";
            }

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
            if ($verbose) {
                echo "<h4 style='color: red;'>Exception:</h4>";
                echo "<p>" . $e->getMessage() . "</p>";
            }
            
            Log::error("Exception creating debt record", [
                'debt' => $debt,
                'service_center_id' => $serviceCenterId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
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