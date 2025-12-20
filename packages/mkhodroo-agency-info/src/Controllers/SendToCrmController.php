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
     * ارسال اطلاعات مراکز به CRM
     */
    public function sendToCrm()
    {
        try {
            $agencies = $this->getAgencyData();
            $results = [];
            $successCount = 0;
            $errorCount = 0;

            foreach ($agencies as $agency) {
                $result = $this->processAgency($agency);
                $results[] = $result;

                if ($result['success']) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "ارسال کامل شد. موفق: $successCount، خطا: $errorCount",
                'total' => count($agencies),
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error("Exception while sending agencies to CRM", [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال: ' . $e->getMessage()
            ], 500);
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
                'rhs_name' => $agency['name'],
                'rhs_agency_code' => $agency['agency_code'] ?? '',
                'rhs_mobile' => $this->cleanMobile($agency['mobile'] ?? ''),
                'rhs_phone' => $this->cleanMobile($agency['phone'] ?? ''),
                'rhs_address' => $agency['address'] ?? '',
                'rhs_national_id' => $this->cleanMobile($agency['national_id'] ?? ''),
                'rhs_province' => $agency['province'] ?? '',
                'rhs_city' => $agency['city'] ?? '',
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
            'firstname',
            'lastname',
            'national_id',
            'agency_code',
            'address',
            'mobile',
            'phone',
            'province',
            'city'
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