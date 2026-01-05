<?php

namespace Mkhodroo\AltfuelTicket\Controllers;

use App\Http\Controllers\Controller;
use Behin\CrmClient\CrmClient;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShowCrmTicketController extends Controller
{
    protected $crmClient;

    public function __construct(CrmClient $crmClient)
    {
        $this->crmClient = $crmClient;
    }

    /**
     * نمایش لیست تیکت‌های CRM بر اساس دسته‌بندی یا Contact ID
     */
    function list(Request $request): View
    {
        $category = $request->input('category');
        $contactId = null;
        $tickets = [];
        $categories = [];

        // دریافت لیست دسته‌بندی‌ها
        $categories = $this->getTicketCategories();

        // اگر کاربر کارشناس باشد
        if (auth()->user()->access('Ticket-Actors')) {
            // اگر دسته‌بندی انتخاب شده باشد
            if ($category) {
                $tickets = $this->getTicketsByCategory($category);
            } else {
                // نمایش همه تیکت‌ها
                $tickets = $this->getAllTickets();
            }
        } else {
            // برای کاربران عادی، فقط تیکت‌های خودشان
            $contactId = auth()->user()->crm_contact_id;
            if ($contactId) {
                $tickets = $this->getTicketsByContactId($contactId);
            }
        }

        return view('ATView::crm-list', compact('tickets', 'contactId', 'categories'));
    }

    /**
     * نمایش جزئیات یک تیکت از CRM
     */
    function show(Request $request)
    {
        $ticketId = $request->input('ticket_id');
        
        if (!$ticketId) {
            return response()->json(['error' => 'Ticket ID is required'], 400);
        }

        $ticket = $this->getTicketFromCrm($ticketId);
        
        if (!$ticket) {
            return response()->json(['error' => 'Ticket not found'], 404);
        }

        // دریافت کامنت‌های تیکت
        $comments = $this->getTicketComments($ticketId);
        
        // دریافت پیوست‌های تیکت
        $attachments = $this->getTicketAttachments($ticketId);

        return view('ATView::crm-show')->with([
            'ticket' => $ticket,
            'comments' => $comments,
            'attachments' => $attachments,
        ]);
    }

    /**
     * دریافت تیکت‌ها از CRM بر اساس Contact ID
     */
    private function getTicketsByContactId($contactId)
    {
        try {
            $response = $this->crmClient->request("new_tickets", "GET", [
                '$select' => 'new_ticketid,new_title,new_status,new_status_option,new_created_at,new_updated_at,new_ticket_id',
                '$filter' => "_new_contact_value eq $contactId",
                '$expand' => 'new_cat_id($select=new_ticketcategoryid,new_name)',
                '$orderby' => 'new_created_at desc'
            ]);

            if ($response->successful()) {
                $body = $response->json();
                $tickets = $body['value'] ?? [];
                
                // تبدیل status_option به متن فارسی و اضافه کردن دسته‌بندی
                foreach ($tickets as &$ticket) {
                    if (isset($ticket['new_status_option'])) {
                        $ticket['new_status'] = self::mapOptionSetToStatus($ticket['new_status_option']);
                    }
                    
                    // اضافه کردن نام دسته‌بندی
                    $ticket['new_category'] = $ticket['new_cat_id']['new_name'] ?? 'بدون دسته‌بندی';
                }
                
                return $tickets;
            }

            Log::error("Failed to get tickets from CRM", [
                'contact_id' => $contactId,
                'response' => $response->body()
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error("Exception while getting tickets from CRM", [
                'contact_id' => $contactId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * دریافت همه تیکت‌ها از CRM
     */
    private function getAllTickets()
    {
        try {
            $response = $this->crmClient->request("new_tickets", "GET", [
                '$select' => 'new_ticketid,new_title,new_status,new_status_option,new_created_at,new_updated_at,new_ticket_id',
                '$expand' => 'new_cat_id($select=new_ticketcategoryid,new_name)',
                '$orderby' => 'new_created_at desc',
                '$top' => 100
            ]);

            if ($response->successful()) {
                $body = $response->json();
                $tickets = $body['value'] ?? [];
                
                // تبدیل status_option به متن فارسی و اضافه کردن دسته‌بندی
                foreach ($tickets as &$ticket) {
                    if (isset($ticket['new_status_option'])) {
                        $ticket['new_status'] = self::mapOptionSetToStatus($ticket['new_status_option']);
                    }
                    
                    // اضافه کردن نام دسته‌بندی
                    $ticket['new_category'] = $ticket['new_cat_id']['new_name'] ?? 'بدون دسته‌بندی';
                }
                
                return $tickets;
            }

            Log::error("Failed to get all tickets from CRM", [
                'response' => $response->body()
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error("Exception while getting all tickets from CRM", [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * دریافت تیکت‌ها از CRM بر اساس دسته‌بندی
     */
    private function getTicketsByCategory($categoryId)
    {
        try {
            $response = $this->crmClient->request("new_tickets", "GET", [
                '$select' => 'new_ticketid,new_title,new_status,new_status_option,new_created_at,new_updated_at,new_ticket_id',
                '$filter' => "_new_cat_id_value eq $categoryId",
                '$expand' => 'new_cat_id($select=new_ticketcategoryid,new_name)',
                '$orderby' => 'new_created_at desc'
            ]);

            if ($response->successful()) {
                $body = $response->json();
                $tickets = $body['value'] ?? [];
                
                // تبدیل status_option به متن فارسی و اضافه کردن دسته‌بندی
                foreach ($tickets as &$ticket) {
                    if (isset($ticket['new_status_option'])) {
                        $ticket['new_status'] = self::mapOptionSetToStatus($ticket['new_status_option']);
                    }
                    
                    // اضافه کردن نام دسته‌بندی
                    $ticket['new_category'] = $ticket['new_cat_id']['new_name'] ?? 'بدون دسته‌بندی';
                }
                
                return $tickets;
            }

            Log::error("Failed to get tickets by category from CRM", [
                'category_id' => $categoryId,
                'response' => $response->body()
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error("Exception while getting tickets by category from CRM", [
                'category_id' => $categoryId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * دریافت لیست دسته‌بندی‌های تیکت از CRM
     */
    private function getTicketCategories()
    {
        try {
            $response = $this->crmClient->request("new_ticketcategories", "GET", [
                '$select' => 'new_ticketcategoryid,new_name,_new_parent_id_value',
                '$orderby' => 'new_name asc',
                '$top' => 100
            ]);

            if ($response->successful()) {
                $body = $response->json();
                $allCategories = $body['value'] ?? [];
                
                // تبدیل به فرمت مناسب برای dropdown
                $formattedCategories = [];
                foreach ($allCategories as $category) {
                    $formattedCategories[] = [
                        'value' => $category['new_ticketcategoryid'],
                        'label' => $category['new_name']
                    ];
                }
                
                return $formattedCategories;
            }

            Log::error("Failed to get categories from CRM", [
                'response' => $response->body()
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error("Exception while getting categories from CRM", [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * دریافت جزئیات یک تیکت از CRM
     */
    private function getTicketFromCrm($ticketId)
    {
        try {
            $response = $this->crmClient->request("new_tickets", "GET", [
                '$select' => 'new_ticketid,new_title,new_status,new_status_option,new_created_at,new_updated_at,new_ticket_id,new_conversion_type,new_score',
                '$filter' => "new_ticketid eq $ticketId",
                '$expand' => 'new_contact($select=contactid,fullname,mobilephone,emailaddress1),new_cat_id($select=new_ticketcategoryid,new_name)'
            ]);

            if ($response->successful()) {
                $body = $response->json();
                $ticket = $body['value'][0] ?? null;
                
                // تبدیل status_option به متن فارسی
                if ($ticket && isset($ticket['new_status_option'])) {
                    $ticket['new_status'] = self::mapOptionSetToStatus($ticket['new_status_option']);
                }
                
                return $ticket;
            }

            Log::error("Failed to get ticket from CRM", [
                'ticket_id' => $ticketId,
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Exception while getting ticket from CRM", [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * دریافت کامنت‌های یک تیکت از CRM
     */
    private function getTicketComments($ticketId)
    {
        try {
            // حذف براکت‌ها و فاصله‌ها از GUID
            $cleanTicketId = str_replace(['{', '}', ' '], '', $ticketId);
            
            // دریافت کامنت‌ها با expand برای contact
            $response = $this->crmClient->request("new_ticketcomments", "GET", [
                '$select' => 'new_ticketcommentid,new_text,new_created_at,new_is_owner,_new_contact_value',
                '$filter' => "_new_ticket_value eq $cleanTicketId",
                '$expand' => 'new_contact($select=contactid,fullname,firstname,lastname)',
                '$orderby' => 'new_created_at asc'
            ]);

            if ($response->successful()) {
                $body = $response->json();
                $comments = $body['value'] ?? [];
                
                Log::info("Raw comments from CRM", [
                    'ticket_id' => $ticketId,
                    'count' => count($comments),
                    'sample' => $comments[0] ?? null
                ]);
                
                // برای هر کامنت، تعیین نام
                foreach ($comments as &$comment) {
                    $contactName = 'کاربر';
                    
                    // اول سعی کن از expand بگیری
                    if (isset($comment['new_contact'])) {
                        if (!empty($comment['new_contact']['fullname'])) {
                            $contactName = $comment['new_contact']['fullname'];
                        } elseif (!empty($comment['new_contact']['firstname']) || !empty($comment['new_contact']['lastname'])) {
                            $contactName = trim(($comment['new_contact']['firstname'] ?? '') . ' ' . ($comment['new_contact']['lastname'] ?? ''));
                        }
                    }
                    // اگر expand کار نکرد، مستقیم از API بگیر
                    elseif (!empty($comment['_new_contact_value'])) {
                        $contactId = $comment['_new_contact_value'];
                        $contactInfo = $this->getContactInfo($contactId);
                        
                        Log::info("Fetched contact info", [
                            'contact_id' => $contactId,
                            'contact_info' => $contactInfo
                        ]);
                        
                        if ($contactInfo) {
                            if (!empty($contactInfo['fullname'])) {
                                $contactName = $contactInfo['fullname'];
                            } elseif (!empty($contactInfo['firstname']) || !empty($contactInfo['lastname'])) {
                                $contactName = trim(($contactInfo['firstname'] ?? '') . ' ' . ($contactInfo['lastname'] ?? ''));
                            }
                        }
                    }
                    
                    $comment['contact_name'] = $contactName;
                    
                    Log::info("Comment processed", [
                        'comment_id' => $comment['new_ticketcommentid'] ?? 'unknown',
                        'has_contact_value' => !empty($comment['_new_contact_value']),
                        'contact_value' => $comment['_new_contact_value'] ?? null,
                        'has_expanded_contact' => isset($comment['new_contact']),
                        'final_name' => $contactName
                    ]);
                }
                
                return $comments;
            }

            Log::warning("Failed to get comments from CRM", [
                'ticket_id' => $ticketId,
                'clean_id' => $cleanTicketId,
                'response' => $response->body()
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error("Exception while getting comments from CRM", [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * دریافت پیوست‌های یک تیکت از CRM
     */
    private function getTicketAttachments($ticketId)
    {
        try {
            $cleanTicketId = str_replace(['{', '}', ' '], '', $ticketId);
            
            $response = $this->crmClient->request("annotations", "GET", [
                '$select' => 'annotationid,subject,notetext,filename,createdon',
                '$filter' => "_objectid_value eq $cleanTicketId",
                '$orderby' => 'createdon asc'
            ]);

            if ($response->successful()) {
                $body = $response->json();
                return $body['value'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error("Exception while getting attachments from CRM", [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * دریافت اطلاعات یک مخاطب از CRM
     */
    private function getContactInfo($contactId)
    {
        try {
            $cleanContactId = str_replace(['{', '}', ' '], '', $contactId);
            
            $response = $this->crmClient->request("contacts($cleanContactId)", "GET", [
                '$select' => 'contactid,fullname,firstname,lastname'
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Failed to get contact info", [
                'contact_id' => $contactId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * ثبت کامنت جدید در CRM
     */
    public function addComment(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required',
            'text' => 'required|string',
            'files.*' => 'file|max:' . config('ATConfig.max-attach-file-size'),
        ]);

        $ticketId = $request->input('ticket_id');
        $text = $request->input('text');

        try {
            // دریافت اطلاعات تیکت از CRM
            $ticket = $this->getTicketFromCrm($ticketId);
            
            if (!$ticket) {
                return response()->json(['error' => 'تیکت یافت نشد'], 404);
            }

            // تعیین اینکه آیا کاربر صاحب تیکت است یا نه
            $isOwner = false;
            $contactId = null;
            
            if (auth()->check()) {
                $userContactId = auth()->user()->crm_contact_id;
                $ticketContactId = $ticket['new_contact']['contactid'] ?? null;
                
                if ($userContactId && $ticketContactId && $userContactId === $ticketContactId) {
                    $isOwner = true;
                    $contactId = $userContactId;
                }
            }

            // آماده‌سازی داده‌های کامنت
            $commentData = [
                'new_text' => $text,
                'new_is_owner' => $isOwner,
                'new_created_at' => now()->toIso8601String(),
                'new_updated_at' => now()->toIso8601String(),
                'new_ticket@odata.bind' => "/new_tickets($ticketId)",
            ];

            if ($contactId) {
                $commentData['new_contact@odata.bind'] = "/contacts($contactId)";
            }

            // ارسال کامنت به CRM
            $response = $this->crmClient->request("new_ticketcomments", "POST", $commentData);

            if ($response->successful()) {
                // ذخیره فایل‌ها اگر وجود داشته باشند
                if ($request->hasFile('files')) {
                    foreach ($request->file('files') as $file) {
                        if ($file->isValid()) {
                            // آپلود فایل
                            $path = $file->store('ticket-uploads', 'public');
                            $fullPath = '/storage/' . $path;

                            // ارسال به CRM به عنوان annotation
                            $this->uploadAttachmentToCrm($ticketId, $fullPath, $file->getClientOriginalName());
                        }
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'کامنت با موفقیت ثبت شد'
                ]);
            }

            Log::error("Failed to create comment in CRM", [
                'ticket_id' => $ticketId,
                'response' => $response->body()
            ]);

            return response()->json(['error' => 'خطا در ثبت کامنت'], 500);

        } catch (\Exception $e) {
            Log::error("Exception while creating comment", [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'خطا در ثبت کامنت'], 500);
        }
    }

    /**
     * متد کمکی برای debug - نمایش ساختار entity
     */
    public function debugCommentStructure(Request $request)
    {
        try {
            // دریافت چند کامنت نمونه بدون expand
            $response1 = $this->crmClient->request("new_ticketcomments", "GET", [
                '$top' => 5,
                '$orderby' => 'createdon desc'
            ]);

            // دریافت با expand
            $response2 = $this->crmClient->request("new_ticketcomments", "GET", [
                '$top' => 5,
                '$orderby' => 'createdon desc',
                '$expand' => 'new_contact($select=contactid,fullname,firstname,lastname)'
            ]);

            $result = [
                'without_expand' => null,
                'with_expand' => null,
            ];

            if ($response1->successful()) {
                $body = $response1->json();
                $comments = $body['value'] ?? [];
                
                $commentWithContact = null;
                foreach ($comments as $comment) {
                    if (!empty($comment['_new_contact_value'])) {
                        $commentWithContact = $comment;
                        break;
                    }
                }
                
                $result['without_expand'] = [
                    'total' => count($comments),
                    'comment_with_contact' => $commentWithContact,
                    'contact_id' => $commentWithContact['_new_contact_value'] ?? null
                ];
            }

            if ($response2->successful()) {
                $body = $response2->json();
                $comments = $body['value'] ?? [];
                
                $commentWithContact = null;
                foreach ($comments as $comment) {
                    if (!empty($comment['_new_contact_value'])) {
                        $commentWithContact = $comment;
                        break;
                    }
                }
                
                $result['with_expand'] = [
                    'total' => count($comments),
                    'comment_with_contact' => $commentWithContact,
                    'has_new_contact_field' => isset($commentWithContact['new_contact']),
                    'new_contact_data' => $commentWithContact['new_contact'] ?? null
                ];
            }

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    /**
     * آپلود فایل پیوست به CRM
     */
    private function uploadAttachmentToCrm($ticketId, $filePath, $fileName)
    {
        try {
            $cleanTicketId = str_replace(['{', '}', ' '], '', $ticketId);
            
            $payload = [
                "subject" => "پیوست تیکت",
                "notetext" => $filePath,
                "filename" => $fileName,
                "mimetype" => "text/html",
                "isdocument" => false,
                "objectid_new_ticket@odata.bind" => "/new_tickets($cleanTicketId)",
            ];

            $response = $this->crmClient->save('annotations', $payload);
            
            if (!$response->successful()) {
                Log::error("Failed to upload attachment to CRM", [
                    'ticket_id' => $ticketId,
                    'file_path' => $filePath,
                    'response' => $response->body()
                ]);
            }

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Exception while uploading attachment to CRM", [
                'ticket_id' => $ticketId,
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * ثبت امتیاز برای تیکت در CRM
     */
    public function setScore(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required',
            'score' => 'required|integer|min:1|max:5',
        ]);

        $ticketId = $request->input('ticket_id');
        $score = $request->input('score');

        try {
            $cleanTicketId = str_replace(['{', '}', ' '], '', $ticketId);
            
            // بروزرسانی امتیاز در CRM
            $response = $this->crmClient->request("new_tickets($cleanTicketId)", "PATCH", [
                'new_score' => $score
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'امتیاز با موفقیت ثبت شد',
                    'score' => $score
                ]);
            }

            Log::error("Failed to set score in CRM", [
                'ticket_id' => $ticketId,
                'score' => $score,
                'response' => $response->body()
            ]);

            return response()->json(['error' => 'خطا در ثبت امتیاز'], 500);

        } catch (\Exception $e) {
            Log::error("Exception while setting score", [
                'ticket_id' => $ticketId,
                'score' => $score,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'خطا در ثبت امتیاز'], 500);
        }
    }

    /**
     * تبدیل Option Set به متن فارسی
     */
    public static function mapOptionSetToStatus($optionSet)
    {
        return match ($optionSet) {
            100000000 => 'جدید',
            100000001 => 'درحال بررسی',
            100000002 => 'پاسخ داده شده',
            100000003 => 'بسته شده',
            default => 'نامشخص',
        };
    }
}
