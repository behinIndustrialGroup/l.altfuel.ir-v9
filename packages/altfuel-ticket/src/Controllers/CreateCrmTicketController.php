<?php

namespace Mkhodroo\AltfuelTicket\Controllers;

use App\Http\Controllers\Controller;
use Behin\CrmClient\CrmClient;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CreateCrmTicketController extends Controller
{
    protected $crmClient;

    public function __construct(CrmClient $crmClient)
    {
        $this->crmClient = $crmClient;
    }

    /**
     * نمایش فرم ایجاد تیکت CRM
     */
    public function index(): View
    {
        // دریافت دسته‌بندی‌های والد از CRM
        $parentCategories = $this->getCrmParentCategories();
        
        return view('ATView::crm-create', compact('parentCategories'));
    }

    /**
     * ذخیره تیکت جدید در CRM
     */
    public function store(Request $request)
    {
        // اعتبارسنجی مشابه TicketRequest
        $this->validateRequest($request);

        try {
            // ایجاد یا دریافت contact در CRM
            $contactId = $this->getOrCreateContact();
            
            if (!$contactId) {
                return response()->json(['error' => 'خطا در ایجاد مخاطب در CRM'], 500);
            }

            // دریافت یا ایجاد دسته‌بندی در CRM
            $categoryCrmId = $this->getOrCreateCategoryInCrm($request->input('category_id'));
            
            if (!$categoryCrmId) {
                return response()->json(['error' => 'خطا در پیدا کردن دسته‌بندی در CRM'], 500);
            }

            // بررسی وجود تیکت در CRM و تولید ID یکتا
            do {
                $ticketId = $this->generateTicketId();
                $existingTicket = $this->checkTicketExists($ticketId);
            } while ($existingTicket); // تا زمانی که ID یکتا پیدا نشه ادامه بده

            // ایجاد تیکت در CRM
            $ticketData = [
                'new_title' => $request->input('title'),
                'new_status' => 'جدید',
                'new_status_option' => 100000000, // جدید
                'new_created_at' => now()->toIso8601String(),
                'new_updated_at' => now()->toIso8601String(),
                'new_ticket_id' => $ticketId, // شناسه عددی
                'new_contact@odata.bind' => "/contacts($contactId)",
                'new_cat_id@odata.bind' => "/new_ticketcategories($categoryCrmId)",
            ];

            // افزودن نوع تبدیل اگر ارسال شده باشد
            if ($request->filled('conversion_type')) {
                $ticketData['new_conversion_type'] = $request->input('conversion_type');
            }

            $ticketResponse = $this->crmClient->request("new_tickets", "POST", $ticketData);

            if (!$ticketResponse->successful()) {
                Log::error("Failed to create ticket in CRM", [
                    'response' => $ticketResponse->body(),
                    'data' => $ticketData
                ]);
                return response()->json(['error' => 'خطا در ایجاد تیکت'], 500);
            }

            // دریافت ID تیکت ایجاد شده
            $crmTicketId = $this->extractEntityId($ticketResponse);
            
            if (!$crmTicketId) {
                return response()->json(['error' => 'خطا در دریافت شناسه تیکت'], 500);
            }

            // افزودن کامنت اولیه
            $this->addInitialComment($crmTicketId, $contactId, $request->input('text'));

            // آپلود فایل‌ها اگر وجود داشته باشند
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    if ($file->isValid()) {
                        $this->uploadAttachmentToCrm($crmTicketId, $file);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'تیکت با موفقیت ایجاد شد',
                'ticket_id' => $ticketId,
                'crm_ticket_id' => $crmTicketId
            ]);

        } catch (\Exception $e) {
            Log::error("Exception while creating CRM ticket", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'خطا در ایجاد تیکت'], 500);
        }
    }

    /**
     * اعتبارسنجی درخواست مشابه TicketRequest
     */
    private function validateRequest(Request $request)
    {
        // بررسی فایل‌ها
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                if ($file && $file->getSize() >= config('ATConfig.max-attach-file-size') * 1024) {
                    throw new \Illuminate\Validation\ValidationException(
                        validator([], [])->errors()->add('files', 'حجم فایل بیش از مقدار مجاز است. مقدار مجاز: ' . config('ATConfig.max-attach-file-size') . 'KB')
                    );
                }
                
                if ($file && !in_array($file->getClientMimeType(), config('ATConfig.attachment-file-types'))) {
                    throw new \Illuminate\Validation\ValidationException(
                        validator([], [])->errors()->add('files', 'فایل پشتیبانی نمیشود. فایل های مجاز: ' . implode(' یا ', config('ATConfig.attachment-file-types-translate')))
                    );
                }
            }
        }

        // بررسی متن یا صدا
        if (!$request->input('text') && !$request->hasFile('payload')) {
            throw new \Illuminate\Validation\ValidationException(
                validator([], [])->errors()->add('text', 'متن یا صدا را تکمیل کنید')
            );
        }

        // اعتبارسنجی اصلی
        $rules = [
            'category_id' => 'required|string',
            'title' => 'required|string|max:255',
            'text' => 'required|string|min:10',
        ];

        // بررسی نوع تبدیل
        $conversionTypes = array_values(config('ATConfig.conversion_types', []));
        if (!empty($conversionTypes)) {
            $rules['conversion_type'] = 'nullable|in:' . implode(',', $conversionTypes);
        }

        $messages = [
            'category_id.required' => 'لطفا دسته بندی را انتخاب کنید',
            'title.required' => 'لطفا عنوان را وارد کنید',
            'title.max' => 'عنوان نباید بیش از 255 کاراکتر باشد',
            'text.required' => 'وارد کردن متن پیام الزامی است',
            'text.min' => 'متن پیام باید حداقل 10 کاراکتر باشد',
            'conversion_type.in' => 'نوع تبدیل انتخاب شده معتبر نیست',
        ];

        $request->validate($rules, $messages);
    }

    /**
     * دریافت دسته‌بندی‌های والد از CRM
     */
    private function getCrmParentCategories()
    {
        try {
            $response = $this->crmClient->request("new_ticketcategories", "GET", [
                '$select' => 'new_ticketcategoryid,new_name,_new_parent_id_value,new_conversion_type_enabled',
                '$orderby' => 'new_name asc',
                '$top' => 100
            ]);

            if ($response->successful()) {
                $body = $response->json();
                $allCategories = $body['value'] ?? [];
                
                // فیلتر کردن دسته‌بندی‌های والد
                $parentCategories = array_filter($allCategories, function($category) {
                    return empty($category['_new_parent_id_value']) || $category['_new_parent_id_value'] === null;
                });
                
                return array_values($parentCategories);
            }

            return [];
        } catch (\Exception $e) {
            Log::error("Exception while getting parent categories from CRM", [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * بررسی فعال بودن نوع تبدیل برای دسته‌بندی
     */
    public function checkConversionTypeEnabled(Request $request)
    {
        $categoryId = $request->input('category_id');
        
        if (!$categoryId) {
            return response()->json(['enabled' => false]);
        }

        try {
            $cleanCategoryId = str_replace(['{', '}', ' '], '', $categoryId);
            
            $response = $this->crmClient->request("new_ticketcategories($cleanCategoryId)", "GET", [
                '$select' => 'new_conversion_type_enabled'
            ]);

            if ($response->successful()) {
                $category = $response->json();
                $enabled = isset($category['new_conversion_type_enabled']) && $category['new_conversion_type_enabled'] === true;
                
                return response()->json(['enabled' => $enabled]);
            }

            return response()->json(['enabled' => false]);
        } catch (\Exception $e) {
            Log::error("Exception while checking conversion type enabled", [
                'category_id' => $categoryId,
                'error' => $e->getMessage()
            ]);
            return response()->json(['enabled' => false]);
        }
    }

    /**
     * دریافت زیردسته‌ها بر اساس والد
     */
    public function getChildCategories(Request $request)
    {
        $parentId = $request->input('parent_id');
        
        if (!$parentId) {
            return response()->json(['error' => 'Parent ID is required'], 400);
        }

        try {
            $cleanParentId = str_replace(['{', '}', ' '], '', $parentId);
            
            // دریافت زیردسته‌ها
            $response = $this->crmClient->request("new_ticketcategories", "GET", [
                '$select' => 'new_ticketcategoryid,new_name,new_conversion_type_enabled',
                '$filter' => "_new_parent_id_value eq $cleanParentId",
                '$orderby' => 'new_name asc'
            ]);

            $childCategories = [];
            
            if ($response->successful()) {
                $body = $response->json();
                $childCategories = $body['value'] ?? [];
            }

            // دریافت اطلاعات دسته‌بندی والد
            $parentResponse = $this->crmClient->request("new_ticketcategories($cleanParentId)", "GET", [
                '$select' => 'new_ticketcategoryid,new_name,new_conversion_type_enabled'
            ]);

            if ($parentResponse->successful()) {
                $parentCategory = $parentResponse->json();
                // افزودن دسته‌بندی والد به ابتدای لیست با علامت مشخص
                $parentCategory['new_name'] = $parentCategory['new_name'] . ' (دسته اصلی)';
                array_unshift($childCategories, $parentCategory);
            }

            return response()->json($childCategories);
        } catch (\Exception $e) {
            Log::error("Exception while getting child categories from CRM", [
                'parent_id' => $parentId,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'خطا در دریافت زیردسته‌ها'], 500);
        }
    }

    /**
     * دریافت یا ایجاد دسته‌بندی در CRM بر اساس نام
     */
    private function getOrCreateCategoryInCrm($categoryId)
    {
        try {
            // اگر categoryId یک GUID است، مستقیماً استفاده کن
            if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $categoryId)) {
                return str_replace(['{', '}', ' '], '', $categoryId);
            }

            // در غیر این صورت، فرض کن که نام دسته‌بندی است
            $response = $this->crmClient->request("new_ticketcategories", "GET", [
                '$select' => 'new_name,new_ticketcategoryid',
                '$filter' => "new_name eq '$categoryId'"
            ]);

            if ($response->successful()) {
                $body = $response->json();
                if (!empty($body['value'])) {
                    return $body['value'][0]['new_ticketcategoryid'];
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Exception while getting category from CRM", [
                'category_id' => $categoryId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * بررسی وجود تیکت در CRM
     */
    private function checkTicketExists($ticketId)
    {
        try {
            $response = $this->crmClient->request("new_tickets", "GET", [
                '$select' => 'new_ticketid,new_title,new_ticket_id',
                '$filter' => "new_ticket_id eq '$ticketId'"
            ]);

            if ($response->successful()) {
                $body = $response->json();
                return !empty($body['value']);
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Exception while checking ticket existence", [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * ایجاد یا دریافت contact در CRM
     */
    private function getOrCreateContact()
    {
        try {
            $user = auth()->user();
            
            if (!$user || !$user->email) {
                return null;
            }

            // اگر کاربر قبلاً crm_contact_id دارد، از آن استفاده کن
            if ($user->crm_contact_id) {
                return $user->crm_contact_id;
            }

            $mobile = $this->convertPersianToEnglish($user->email);
            
            // جستجو برای contact موجود
            $response = $this->crmClient->request("contacts", "GET", [
                '$select' => 'contactid,fullname,mobilephone',
                '$filter' => "mobilephone eq '$mobile'"
            ]);

            if ($response->successful()) {
                $body = $response->json();
                if (!empty($body['value'])) {
                    $contactId = $body['value'][0]['contactid'];
                    
                    // ذخیره contact_id در کاربر
                    $user->crm_contact_id = $contactId;
                    $user->save();
                    
                    return $contactId;
                }
            }

            // ایجاد contact جدید
            $contactData = [
                'createdon' => now()->toIso8601String(),
                'telephone1' => $mobile,
                'mobilephone' => $mobile,
                'firstname' => $user->display_name ?? $user->name ?? 'کاربر',
                'emailaddress1' => $user->email,
            ];

            $createResponse = $this->crmClient->request("contacts", "POST", $contactData);
            
            if ($createResponse->successful()) {
                $contactId = $this->extractEntityId($createResponse);
                
                if ($contactId) {
                    // ذخیره contact_id در کاربر
                    $user->crm_contact_id = $contactId;
                    $user->save();
                }
                
                return $contactId;
            }

            Log::error("Failed to create contact in CRM", [
                'response' => $createResponse->body(),
                'data' => $contactData
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Exception while getting or creating contact", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * افزودن کامنت اولیه به تیکت
     */
    private function addInitialComment($ticketId, $contactId, $text)
    {
        try {
            $commentData = [
                'new_text' => $text,
                'new_is_owner' => true,
                'new_created_at' => now()->toIso8601String(),
                'new_updated_at' => now()->toIso8601String(),
                'new_ticket@odata.bind' => "/new_tickets($ticketId)",
                'new_contact@odata.bind' => "/contacts($contactId)",
            ];

            $response = $this->crmClient->request("new_ticketcomments", "POST", $commentData);
            
            if (!$response->successful()) {
                Log::error("Failed to add initial comment to CRM ticket", [
                    'ticket_id' => $ticketId,
                    'response' => $response->body()
                ]);
            }

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Exception while adding initial comment", [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * آپلود فایل پیوست به CRM
     */
    private function uploadAttachmentToCrm($ticketId, $file)
    {
        try {
            // ذخیره فایل
            $path = $file->store('crm-ticket-uploads', 'public');
            $fullPath = '/storage/' . $path;
            
            $payload = [
                "subject" => "پیوست تیکت",
                "notetext" => $fullPath,
                "filename" => $file->getClientOriginalName(),
                "mimetype" => $file->getMimeType(),
                "isdocument" => false,
                "objectid_new_ticket@odata.bind" => "/new_tickets($ticketId)",
            ];

            $response = $this->crmClient->request("annotations", "POST", $payload);
            
            if (!$response->successful()) {
                Log::error("Failed to upload attachment to CRM", [
                    'ticket_id' => $ticketId,
                    'file_name' => $file->getClientOriginalName(),
                    'response' => $response->body()
                ]);
            }

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Exception while uploading attachment to CRM", [
                'ticket_id' => $ticketId,
                'file_name' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
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

    /**
     * تولید شناسه یکتا برای تیکت (عددی)
     */
    private function generateTicketId()
    {
        // تولید شناسه عددی یکتا در محدوده Int32
        // استفاده از عدد تصادفی بزرگ
        return rand(100000000, 2147483647); // محدوده Int32
    }

    /**
     * نگاشت وضعیت به OptionSet (مشابه منطق اصلی)
     */
    private function mapStatusToOptionSet($status)
    {
        return match ($status) {
            'جدید', 'new' => 100000000,
            'درحال بررسی', 'in_progress' => 100000001,
            'پاسخ داده شده', 'answered' => 100000002,
            'بسته شده', 'closed' => 100000003,
            default => 100000000,
        };
    }

    /**
     * تبدیل اعداد فارسی به انگلیسی
     */
    private function convertPersianToEnglish($string)
    {
        static $map = [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ];
        return strtr($string, $map);
    }
}