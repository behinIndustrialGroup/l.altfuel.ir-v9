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
     * نمایش لیست تیکت‌های CRM بر اساس Contact ID
     */
    function list(Request $request): View
    {
        // اگر contact_id در request باشه از اون استفاده کن، وگرنه از user لاگین شده بگیر
        $contactId = $request->input('contact_id');
        
        if (!$contactId && auth()->check()) {
            $contactId = auth()->user()->crm_contact_id;
        }

        $tickets = [];
        if ($contactId) {
            $tickets = $this->getTicketsByContactId($contactId);
        }

        return view('ATView::crm-list', compact('tickets', 'contactId'));
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

        return view('ATView::crm-show')->with([
            'ticket' => $ticket,
            'comments' => $comments,
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
                '$orderby' => 'new_created_at desc'
            ]);

            if ($response->successful()) {
                $body = $response->json();
                return $body['value'] ?? [];
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
                return $body['value'][0] ?? null;
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
            
            // دریافت کامنت‌ها با expand برای گرفتن اطلاعات contact و createdby
            $response = $this->crmClient->request("new_ticketcomments", "GET", [
                '$select' => 'new_ticketcommentid,new_text,new_created_at,new_is_owner,_new_contact_value,_createdby_value,_ownerid_value',
                '$filter' => "_new_ticket_value eq $cleanTicketId",
                '$expand' => 'new_contact($select=contactid,fullname,firstname,lastname),createdby($select=systemuserid,fullname),ownerid($select=systemuserid,fullname)',
                '$orderby' => 'new_created_at asc'
            ]);

            if ($response->successful()) {
                $body = $response->json();
                $comments = $body['value'] ?? [];
                
                // برای هر کامنت، اطمینان از وجود اطلاعات کامل
                foreach ($comments as &$comment) {
                    // اگر contact نداره، سعی کن از _new_contact_value بگیری
                    if (empty($comment['new_contact']) && !empty($comment['_new_contact_value'])) {
                        $contactId = $comment['_new_contact_value'];
                        $contactInfo = $this->getContactInfo($contactId);
                        if ($contactInfo) {
                            $comment['new_contact'] = $contactInfo;
                        }
                    }
                    
                    // اگر createdby نداره، سعی کن از _createdby_value بگیری
                    if (empty($comment['createdby']) && !empty($comment['_createdby_value'])) {
                        $userId = $comment['_createdby_value'];
                        $userInfo = $this->getSystemUserInfo($userId);
                        if ($userInfo) {
                            $comment['createdby'] = $userInfo;
                        }
                    }
                    
                    // اگر ownerid نداره، سعی کن از _ownerid_value بگیری
                    if (empty($comment['ownerid']) && !empty($comment['_ownerid_value'])) {
                        $ownerId = $comment['_ownerid_value'];
                        $ownerInfo = $this->getSystemUserInfo($ownerId);
                        if ($ownerInfo) {
                            $comment['ownerid'] = $ownerInfo;
                        }
                    }
                }
                
                Log::info("Comments retrieved from CRM", [
                    'ticket_id' => $ticketId,
                    'count' => count($comments)
                ]);
                
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
     * دریافت اطلاعات یک کاربر سیستم (کارشناس) از CRM
     */
    private function getSystemUserInfo($userId)
    {
        try {
            $cleanUserId = str_replace(['{', '}', ' '], '', $userId);
            
            $response = $this->crmClient->request("systemusers($cleanUserId)", "GET", [
                '$select' => 'systemuserid,fullname,firstname,lastname'
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Failed to get system user info", [
                'user_id' => $userId,
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
            // دریافت یک کامنت نمونه
            $response = $this->crmClient->request("new_ticketcomments", "GET", [
                '$top' => 1
            ]);

            if ($response->successful()) {
                $body = $response->json();
                return response()->json([
                    'sample_comment' => $body['value'][0] ?? null,
                    'all_fields' => array_keys($body['value'][0] ?? [])
                ]);
            }

            return response()->json(['error' => 'Failed to get sample comment', 'response' => $response->body()]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    /**
     * تبدیل Option Set به متن فارسی
     */
    private function mapOptionSetToStatus($optionSet)
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
