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
     * پردازش یک مرکز
     */
    private function processAgency($agency)
    {
        try {
            $mobile = $this->cleanMobile($agency['mobile'] ?? '');
            
            if (!$mobile) {
                return [
                    'agency_id' => $agency['parent_id'],
                    'name' => $agency['name'],
                    'success' => false,
                    'message' => 'شماره موبایل وجود ندارد'
                ];
            }

            // پیدا کردن یا ایجاد Contact
            $contactId = $this->getOrCreateContact($agency, $mobile);
            
            if (!$contactId) {
                return [
                    'agency_id' => $agency['parent_id'],
                    'name' => $agency['name'],
                    'success' => false,
                    'message' => 'خطا در ایجاد Contact'
                ];
            }

            // ایجاد Service Center
            $serviceCenterId = $this->createServiceCenter($agency, $contactId);
            
            if ($serviceCenterId) {
                return [
                    'agency_id' => $agency['parent_id'],
                    'name' => $agency['name'],
                    'success' => true,
                    'message' => 'مرکز با موفقیت ایجاد شد',
                    'contact_id' => $contactId,
                    'service_center_id' => $serviceCenterId
                ];
            } else {
                return [
                    'agency_id' => $agency['parent_id'],
                    'name' => $agency['name'],
                    'success' => false,
                    'message' => 'خطا در ایجاد مرکز خدمات'
                ];
            }

        } catch (\Exception $e) {
            Log::error("Exception processing agency", [
                'agency_id' => $agency['parent_id'] ?? 'unknown',
                'error' => $e->getMessage()
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
        // جستجو برای Contact موجود
        $contactId = $this->findContactByMobile($mobile);
        
        if ($contactId) {
            return $contactId;
        }

        // ایجاد Contact جدید
        return $this->createContact($agency, $mobile);
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
                'fullname' => $agency['name'],
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
                return $this->extractEntityId($response);
            }

            Log::error("Failed to create contact", [
                'agency_id' => $agency['parent_id'],
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Exception creating contact", [
                'agency_id' => $agency['parent_id'],
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * ایجاد Service Center
     */
    private function createServiceCenter($agency, $contactId)
    {
        try {
            $serviceCenterData = [
                'rhs_row' => $agency['customer_type'] ?? '',
                'rhs_fullname' => $agency['firstname'] ?? '',
                'rhs_lastname' => $agency['lastname'] ?? '',
                'rhs_yearofreceivingthecode' => $agency['recieving_code_year'] ?? '',
                'rhs_nationalcode' => $this->cleanMobile($agency['national_id'] ?? ''),
                'rhs_servicecenterid' => $agency['agency_code'] ?? '',
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
                'rhs_contact@odata.bind' => "/contacts($contactId)"
            ];

            // حذف فیلدهای خالی
            $serviceCenterData = array_filter($serviceCenterData, function($value) {
                return $value !== '' && $value !== null;
            });

            $response = $this->crmClient->request("rhs_servicecenters", "POST", $serviceCenterData);
            
            if ($response->successful()) {
                return $this->extractEntityId($response);
            }

            Log::error("Failed to create service center", [
                'agency_id' => $agency['parent_id'],
                'contact_id' => $contactId,
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Exception creating service center", [
                'agency_id' => $agency['parent_id'],
                'contact_id' => $contactId,
                'error' => $e->getMessage()
            ]);
            return null;
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