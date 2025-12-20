<?php

namespace Mkhodroo\AgencyInfo\Controllers;

use App\Http\Controllers\Controller;
use Behin\CrmClient\CrmClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendToCrmController extends Controller
{
    protected $crmClient;

    public function __construct(CrmClient $crmClient)
    {
        $this->crmClient = $crmClient;
    }

    /**
     * ارسال اطلاعات مراکز به CRM به صورت chunk
     */
    public function sendToCrm()
    {
        try {
            set_time_limit(0); // بدون محدودیت زمان
            
            $agencies = $this->getAgencyData();
            $totalCount = count($agencies);
            
            if ($totalCount == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'هیچ مرکزی برای ارسال یافت نشد'
                ]);
            }

            $chunkSize = 10; // تعداد رکورد در هر chunk
            $chunks = array_chunk($agencies, $chunkSize);
            $totalChunks = count($chunks);
            
            $successCount = 0;
            $errorCount = 0;
            $processedCount = 0;
            $allResults = [];

            echo "<h2>شروع ارسال $totalCount مرکز به CRM</h2>";
            echo "<p>تعداد chunk ها: $totalChunks</p>";
            echo "<hr>";
            
            // فلاش کردن خروجی
            if (ob_get_level()) {
                ob_end_flush();
            }
            ob_start();

            foreach ($chunks as $chunkIndex => $chunk) {
                $chunkNumber = $chunkIndex + 1;
                $chunkResults = [];
                
                echo "<h3>پردازش Chunk $chunkNumber از $totalChunks</h3>";
                echo "<ul>";
                
                foreach ($chunk as $agency) {
                    $processedCount++;
                    $result = $this->processAgency($agency);
                    $chunkResults[] = $result;
                    $allResults[] = $result;

                    if ($result['success']) {
                        $successCount++;
                        echo "<li style='color: green;'>✓ {$result['name']} - موفق</li>";
                    } else {
                        $errorCount++;
                        echo "<li style='color: red;'>✗ {$result['name']} - خطا: {$result['message']}</li>";
                    }
                    
                    // فلاش کردن خروجی برای نمایش فوری
                    ob_flush();
                    flush();
                    
                    // کمی استراحت
                    usleep(200000); // 0.2 ثانیه
                }
                
                echo "</ul>";
                echo "<p><strong>Chunk $chunkNumber کامل شد. موفق: " . 
                     count(array_filter($chunkResults, fn($r) => $r['success'])) . 
                     " - خطا: " . 
                     count(array_filter($chunkResults, fn($r) => !$r['success'])) . 
                     "</strong></p>";
                echo "<hr>";
                
                ob_flush();
                flush();
                
                // استراحت بین chunk ها
                sleep(1);
            }

            echo "<h2>نتیجه نهایی</h2>";
            echo "<p><strong>کل: $totalCount - موفق: $successCount - خطا: $errorCount</strong></p>";
            
            // لاگ نهایی
            Log::info("Agency CRM sync completed", [
                'total' => $totalCount,
                'success' => $successCount,
                'error' => $errorCount
            ]);

        } catch (\Exception $e) {
            Log::error("Exception while sending agencies to CRM", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            echo "<h2 style='color: red;'>خطا در پردازش</h2>";
            echo "<p>{$e->getMessage()}</p>";
        }
    }

    /**
     * تست و debug اطلاعات
     */
    public function debugData()
    {
        try {
            $agencies = $this->getAgencyData();
            
            echo "<h2>Debug اطلاعات مراکز</h2>";
            echo "<p>تعداد کل مراکز: " . count($agencies) . "</p>";
            
            if (count($agencies) > 0) {
                echo "<h3>نمونه اولین مرکز:</h3>";
                echo "<pre>";
                print_r($agencies[0]);
                echo "</pre>";
                
                // تست اتصال به CRM
                echo "<h3>تست اتصال به CRM:</h3>";
                try {
                    $testResponse = $this->crmClient->request("contacts", "GET", [
                        '$select' => 'contactid,fullname',
                        '$top' => 1
                    ]);
                    
                    if ($testResponse->successful()) {
                        echo "<p style='color: green;'>✓ اتصال به CRM موفق</p>";
                        echo "<pre>";
                        print_r($testResponse->json());
                        echo "</pre>";
                    } else {
                        echo "<p style='color: red;'>✗ خطا در اتصال به CRM</p>";
                        echo "<pre>";
                        echo $testResponse->body();
                        echo "</pre>";
                    }
                } catch (\Exception $e) {
                    echo "<p style='color: red;'>✗ Exception در اتصال به CRM: " . $e->getMessage() . "</p>";
                }
                
                // تست پردازش یک مرکز
                echo "<h3>تست پردازش اولین مرکز:</h3>";
                $result = $this->processAgency($agencies[0]);
                echo "<pre>";
                print_r($result);
                echo "</pre>";
            } else {
                echo "<p style='color: red;'>هیچ مرکزی یافت نشد!</p>";
            }
            
        } catch (\Exception $e) {
            echo "<h2 style='color: red;'>خطا در debug:</h2>";
            echo "<p>" . $e->getMessage() . "</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
    }
    /**
     * تست ساده با حداقل فیلدها
     */
    public function testMinimalServiceCenter()
    {
        try {
            echo "<h2>تست ایجاد Service Center با حداقل فیلدها</h2>";
            
            // تست 1: فقط با نام
            echo "<h3>تست 1: فقط با نام</h3>";
            $serviceCenterData1 = [
                'rhs_name' => 'تست مرکز خدمات'
            ];
            
            echo "<pre>";
            print_r($serviceCenterData1);
            echo "</pre>";
            
            $response1 = $this->crmClient->request("rhs_servicecenters", "POST", $serviceCenterData1);
            
            if ($response1->successful()) {
                echo "<p style='color: green;'>✓ تست 1 موفق</p>";
            } else {
                echo "<p style='color: red;'>✗ تست 1 ناموفق: " . $response1->status() . "</p>";
                echo "<pre>" . $response1->body() . "</pre>";
            }
            
            echo "<hr>";
            
            // تست 2: با نام و موبایل
            echo "<h3>تست 2: با نام و موبایل</h3>";
            $serviceCenterData2 = [
                'rhs_name' => 'تست مرکز خدمات 2',
                'rhs_mobile' => '09123456789'
            ];
            
            echo "<pre>";
            print_r($serviceCenterData2);
            echo "</pre>";
            
            $response2 = $this->crmClient->request("rhs_servicecenters", "POST", $serviceCenterData2);
            
            if ($response2->successful()) {
                echo "<p style='color: green;'>✓ تست 2 موفق</p>";
            } else {
                echo "<p style='color: red;'>✗ تست 2 ناموفق: " . $response2->status() . "</p>";
                echo "<pre>" . $response2->body() . "</pre>";
            }
            
            echo "<hr>";
            
            // تست 3: با فیلدهای text ساده
            echo "<h3>تست 3: با فیلدهای text ساده</h3>";
            $serviceCenterData3 = [
                'rhs_name' => 'تست مرکز خدمات 3',
                'rhs_fullname' => 'جهانگیر',
                'rhs_lastname' => 'قاسم وند',
                'rhs_mobile' => '09143553102',
                'rhs_agencycode' => '12003'
            ];
            
            echo "<pre>";
            print_r($serviceCenterData3);
            echo "</pre>";
            
            $response3 = $this->crmClient->request("rhs_servicecenters", "POST", $serviceCenterData3);
            
            if ($response3->successful()) {
                echo "<p style='color: green;'>✓ تست 3 موفق</p>";
                $serviceCenterId = $this->extractEntityId($response3);
                echo "<p>Service Center ID: $serviceCenterId</p>";
            } else {
                echo "<p style='color: red;'>✗ تست 3 ناموفق: " . $response3->status() . "</p>";
                echo "<pre>" . $response3->body() . "</pre>";
            }
            
        } catch (\Exception $e) {
            echo "<h2 style='color: red;'>خطا در تست:</h2>";
            echo "<p>" . $e->getMessage() . "</p>";
        }
    }
    public function testCreateServiceCenter()
    {
        try {
            // تست با یک contact موجود
            $testResponse = $this->crmClient->request("contacts", "GET", [
                '$select' => 'contactid,fullname',
                '$top' => 1
            ]);
            
            if (!$testResponse->successful()) {
                echo "<p style='color: red;'>خطا در دریافت contact برای تست</p>";
                return;
            }
            
            $contacts = $testResponse->json()['value'];
            if (empty($contacts)) {
                echo "<p style='color: red;'>هیچ contact برای تست یافت نشد</p>";
                return;
            }
            
            $contactId = $contacts[0]['contactid'];
            echo "<p>استفاده از Contact ID: $contactId</p>";
            
            // تست با داده‌های واقعی از اولین مرکز
            $agencies = $this->getAgencyData();
            if (empty($agencies)) {
                echo "<p style='color: red;'>هیچ مرکزی یافت نشد</p>";
                return;
            }
            
            $firstAgency = $agencies[0];
            
            // تست با فیلدهای محدود ابتدا
            $serviceCenterData = [
                'rhs_name' => $firstAgency['name'] ?? 'تست نام',
                'rhs_fullname' => $firstAgency['firstname'] ?? 'تست',
                'rhs_lastname' => $firstAgency['lastname'] ?? 'نام خانوادگی',
                'rhs_mobile' => $this->cleanMobile($firstAgency['mobile'] ?? '09123456789'),
                'rhs_agencycode' => $firstAgency['agency_code'] ?? '12003',
                'new_contact@odata.bind' => "/contacts($contactId)"
            ];

            echo "<h3>ارسال داده‌های تست:</h3>";
            echo "<pre>";
            print_r($serviceCenterData);
            echo "</pre>";

            $response = $this->crmClient->request("rhs_servicecenters", "POST", $serviceCenterData);
            
            if ($response->successful()) {
                echo "<p style='color: green;'>✓ Service Center تست با موفقیت ایجاد شد</p>";
                $serviceCenterId = $this->extractEntityId($response);
                echo "<p>Service Center ID: $serviceCenterId</p>";
            } else {
                echo "<p style='color: red;'>✗ خطا در ایجاد Service Center تست</p>";
                echo "<p>Status: " . $response->status() . "</p>";
                echo "<pre>";
                echo $response->body();
                echo "</pre>";
            }
            
        } catch (\Exception $e) {
            echo "<h2 style='color: red;'>خطا در تست:</h2>";
            echo "<p>" . $e->getMessage() . "</p>";
        }
    }
    private function processAgency($agency)
    {
        try {
            $mobile = $this->cleanMobile($agency['mobile'] ?? '');
            
            if (!$mobile) {
                return [
                    'agency_id' => $agency['parent_id'],
                    'name' => $agency['name'] ?? 'نامشخص',
                    'success' => false,
                    'message' => 'شماره موبایل وجود ندارد'
                ];
            }

            // پیدا کردن یا ایجاد Contact
            $contactResult = $this->getOrCreateContact($agency, $mobile);
            
            if (!$contactResult['success']) {
                return [
                    'agency_id' => $agency['parent_id'],
                    'name' => $agency['name'] ?? 'نامشخص',
                    'success' => false,
                    'message' => 'خطا در Contact: ' . $contactResult['message']
                ];
            }

            // ایجاد Service Center
            $serviceCenterResult = $this->createServiceCenter($agency, $contactResult['contact_id']);
            
            if ($serviceCenterResult['success']) {
                return [
                    'agency_id' => $agency['parent_id'],
                    'name' => $agency['name'] ?? 'نامشخص',
                    'success' => true,
                    'message' => 'مرکز با موفقیت ایجاد شد',
                    'contact_id' => $contactResult['contact_id'],
                    'service_center_id' => $serviceCenterResult['service_center_id']
                ];
            } else {
                return [
                    'agency_id' => $agency['parent_id'],
                    'name' => $agency['name'] ?? 'نامشخص',
                    'success' => false,
                    'message' => 'خطا در Service Center: ' . $serviceCenterResult['message']
                ];
            }

        } catch (\Exception $e) {
            Log::error("Exception processing agency", [
                'agency_id' => $agency['parent_id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'agency_id' => $agency['parent_id'] ?? 'unknown',
                'name' => $agency['name'] ?? 'نامشخص',
                'success' => false,
                'message' => 'خطا در پردازش: ' . $e->getMessage()
            ];
        }
    }

    /**
     * پیدا کردن یا ایجاد Contact
     */
    private function getOrCreateContact($agency, $mobile)
    {
        try {
            // جستجو برای Contact موجود
            $contactId = $this->findContactByMobile($mobile);
            
            if ($contactId) {
                return [
                    'success' => true,
                    'contact_id' => $contactId,
                    'message' => 'Contact موجود یافت شد'
                ];
            }

            // ایجاد Contact جدید
            return $this->createContact($agency, $mobile);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'خطا در getOrCreateContact: ' . $e->getMessage()
            ];
        }
    }

    /**
     * جستجو برای Contact بر اساس موبایل
     */
    private function findContactByMobile($mobile)
    {
        try {
            $response = $this->crmClient->request("contacts", "GET", [
                '$select' => 'contactid',
                '$filter' => "mobilephone eq '$mobile'"
            ]);

            if ($response->successful()) {
                $body = $response->json();
                if (!empty($body['value'])) {
                    return $body['value'][0]['contactid'];
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Error finding contact", ['mobile' => $mobile, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * ایجاد Contact جدید
     */
    private function createContact($agency, $mobile)
    {
        try {
            $contactData = [
                'firstname' => $agency['firstname'] ?? '',
                'lastname' => $agency['lastname'] ?? '',
                'fullname' => $agency['name'] ?? '',
                'mobilephone' => $mobile,
                'telephone1' => $this->cleanMobile($agency['phone'] ?? ''),
                'address1_line1' => $agency['address'] ?? '',
                'createdon' => now()->toIso8601String()
            ];

            // حذف فیلدهای خالی
            $contactData = array_filter($contactData, function($value) {
                return $value !== '' && $value !== null;
            });

            $response = $this->crmClient->request("contacts", "POST", $contactData);
            
            if ($response->successful()) {
                $contactId = $this->extractEntityId($response);
                return [
                    'success' => true,
                    'contact_id' => $contactId,
                    'message' => 'Contact جدید ایجاد شد'
                ];
            }

            Log::error("Failed to create contact", [
                'agency_id' => $agency['parent_id'],
                'response_status' => $response->status(),
                'response_body' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ایجاد Contact: ' . $response->status() . ' - ' . $response->body()
            ];
        } catch (\Exception $e) {
            Log::error("Exception creating contact", [
                'agency_id' => $agency['parent_id'],
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'Exception در ایجاد Contact: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ایجاد Service Center
     */
    private function createServiceCenter($agency, $contactId)
    {
        try {
            $serviceCenterData = [
                'rhs_name' => $agency['name'] ?? '', // اضافه کردن فیلد rhs_name
                'rhs_row' => $agency['customer_type'] ?? '',
                'rhs_fullname' => $agency['firstname'] ?? '',
                'rhs_lastname' => $agency['lastname'] ?? '',
                'rhs_yearofreceivingthecode' => $agency['recieving_code_year'] ?? '',
                'rhs_nationalcode' => $this->cleanMobile($agency['national_id'] ?? ''),
                'rhs_agencycode' => $agency['agency_code'] ?? '', // تغییر نام از rhs_servicecenterid به rhs_agencycode
                'rhs_address' => $agency['address'] ?? '',
                'rhs_guildnumber' => $agency['guild_number'] ?? '',
                'rhs_mobile' => $this->cleanMobile($agency['mobile'] ?? ''),
                'rhs_phone' => $this->cleanMobile($agency['phone'] ?? ''),
                'rhs_dateofissue' => $this->formatDate($agency['issued_date'] ?? ''),
                'rhs_expirydate' => $this->formatDate($agency['exp_date'] ?? ''),
                'rhs_description' => $agency['description'] ?? '',
                'rhs_province' => $agency['province'] ?? '',
                'rhs_city' => $agency['city'] ?? '',
                'rhs_postalcode' => $agency['postal_code'] ?? '',
                'statecode' => $this->formatEnable($agency['enable'] ?? ''),
                'rhs_location' => $agency['location'] ?? '',
                'createdon' => now()->toIso8601String(),
                'new_contact@odata.bind' => "/contacts($contactId)" // تغییر از rhs_contact به ne
            ];

            // حذف فیلدهای خالی
            $serviceCenterData = array_filter($serviceCenterData, function($value) {
                return $value !== '' && $value !== null;
            });

            $response = $this->crmClient->request("rhs_servicecenters", "POST", $serviceCenterData);
            
            if ($response->successful()) {
                $serviceCenterId = $this->extractEntityId($response);
                return [
                    'success' => true,
                    'service_center_id' => $serviceCenterId,
                    'message' => 'Service Center ایجاد شد'
                ];
            }

            Log::error("Failed to create service center", [
                'agency_id' => $agency['parent_id'],
                'contact_id' => $contactId,
                'response_status' => $response->status(),
                'response_body' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ایجاد Service Center: ' . $response->status() . ' - ' . $response->body()
            ];
        } catch (\Exception $e) {
            Log::error("Exception creating service center", [
                'agency_id' => $agency['parent_id'],
                'contact_id' => $contactId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'Exception در ایجاد Service Center: ' . $e->getMessage()
            ];
        }
    }

    /**
     * دریافت اطلاعات مراکز
     */
    private function getAgencyData()
    {
        $desiredKeys = [
            'customer_type',
            'firstname',
            'lastname',
            'recieving_code_year',
            'national_id',
            'agency_code',
            'address',
            'guild_number',
            'mobile',
            'phone',
            'issued_date',
            'exp_date',
            'description',
            'province',
            'city',
            'postal_code',
            'enable',
            'location'
        ];

        // گرفتن اطلاعات شهرها و استان‌ها
        $cities = DB::table('cities')->get()->keyBy('id');
        $provinces = DB::table('new_provinces')->get()->keyBy('id');

        // گرفتن اطلاعات key-value
        $rawData = DB::table('agency_info')
            ->whereIn('key', $desiredKeys)
            ->get();

        // گروه‌بندی بر اساس parent_id
        $grouped = $rawData->groupBy('parent_id');

        // ساختن خروجی نهایی
        $structured = $grouped->map(function ($items, $parentId) use ($desiredKeys, $cities, $provinces) {
            $row = ['parent_id' => $parentId];

            foreach ($desiredKeys as $key) {
                $value = $items->firstWhere('key', $key)->value ?? null;

                if ($key === 'city') {
                    $cityId = intval($value);
                    $value = $cities[$cityId]->city ?? 'نامشخص';
                }

                if ($key === 'province') {
                    $provinceId = intval($value);
                    $value = $provinces[$provinceId]->name ?? 'نامشخص';
                }

                $row[$key] = $value;
            }

            // ایجاد نام کامل
            $row['name'] = trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''));

            return $row;
        })->values();

        return $structured->toArray();
    }

    /**
     * تمیز کردن شماره موبایل
     */
    private function cleanMobile($mobile)
    {
        if (!$mobile) return '';
        
        // تبدیل اعداد فارسی به انگلیسی
        $persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        
        return str_replace($persianNumbers, $englishNumbers, $mobile);
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
     * فرمت کردن وضعیت enable برای statecode
     */
    private function formatEnable($enable)
    {
        return match ($enable) {
            '1', 1, 'true', true => 0, // فعال = 0 در CRM
            '0', 0, 'false', false => 1, // غیرفعال = 1 در CRM
            default => 0
        };
    }

    /**
     * استخراج Entity ID از response header
     */
    private function extractEntityId($response)
    {
        $entityIdHeader = $response->header('OData-EntityId');
        if ($entityIdHeader) {
            preg_match('/\(([^)]+)\)/', $entityIdHeader, $matches);
            return $matches[1] ?? null;
        }
        return null;
    }
}