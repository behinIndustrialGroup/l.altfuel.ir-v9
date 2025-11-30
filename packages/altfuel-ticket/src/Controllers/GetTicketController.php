<?php

namespace Mkhodroo\AltfuelTicket\Controllers;

use App\Http\Controllers\Controller;
use Behin\CrmClient\CrmClient;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mkhodroo\AltfuelTicket\Models\CatagoryActor;
use Mkhodroo\AltfuelTicket\Models\Ticket;
use Mkhodroo\AltfuelTicket\Models\TicketCatagory;
use Mkhodroo\AltfuelTicket\Models\TicketComment;

class GetTicketController extends Controller
{

    function getAll()
    {
        return ['data' => []];
        $result = Ticket::get()->each(function ($row) {
            $row->catagory = $row->catagory();
            $row->user = $row->user()?->display_name;
            $row->actor = $row->actor()?->display_name;
            // $row->user_level = $row->user()->level();
        });
        return $result;
    }

    function getMyTickets()
    {
        $data = Ticket::where('user_id', Auth::id())->get()->each(function ($row) {
            $row->catagory = $row->catagory()['name'];
            $row->user = $row->user()?->display_name;
            $row->actor = $row->actor()?->display_name;
            $row->conversion_type_label = $row->conversion_type_label;
            // $row->user_level = $row->user()->level();
        });
        return $data;
    }

    function getMyTicketsByCatagory($catagory_id)
    {
        if (is_array($catagory_id)) {
            return Ticket::where('user_id', Auth::id())->WhereIn('cat_id', $catagory_id)->get()->each(function ($row) {
                $row->catagory = $row->catagory();
                $row->user = $row->user()?->display_name;
                $row->actor = $row->actor()?->display_name;
                $row->conversion_type_label = $row->conversion_type_label;
                // $row->user_level = $row->user()->level();
            });
        }
        $category = CatagoryController::get($catagory_id)->name;
        return Ticket::where('user_id', Auth::id())->where('cat_id', $catagory_id)->get()->each(function ($row) use ($category) {
            $row->catagory = $category;
            $row->user = $row->user()?->display_name;
            $row->actor = $row->actor()?->display_name;
            $row->conversion_type_label = $row->conversion_type_label;
            // $row->user_level = $row->user()->level();
        });
    }

    function getByCatagory(Request $r)
    {
        if (auth()->user()->access("Ticket-Actors")) {
            // $actors = CatagoryActor::where('user_id', Auth::id())->pluck('cat_id');
            $category = CatagoryController::get($r->catagory)->name;

            return Ticket::where('cat_id', $r->catagory)
                ->whereIn('status', [config('ATConfig.status.new'), config('ATConfig.status.in_progress')])
                ->select('id', 'title', 'status', 'user_id', 'updated_at')
                ->get()
                ->map(function ($row) use ($category) {
                    return [
                        'id' => $row->id,
                        'title' => $row->title,
                        'user' => $row->user()?->display_name,
                        'catagory' => $category,
                        'status' => $row->status,
                        'conversion_type_label' => $row->conversion_type_label,
                        'updated_at' => verta($row->updated_at)->format('Y-m-d H:i'),
                    ];
                });
        }
        return $this->getMyTicketsByCatagory($r->catagory);
    }

    function oldGetByCatagory(Request $r)
    {
        if (auth()->user()->access("Ticket-Actors")) {
            // $actors = CatagoryActor::where('user_id', Auth::id())->pluck('cat_id');
            $category = CatagoryController::get($r->catagory)->name;
            return Ticket::where('cat_id', $r->catagory)
                ->whereIn('status', [config('ATConfig.status.answered'), config('ATConfig.status.closed')])
                ->select('id', 'title', 'status', 'user_id', 'updated_at')
                ->get()
                ->map(function ($row) use ($category) {
                    return [
                        'id' => $row->id,
                        'title' => $row->title,
                        'user' => $row->user()?->display_name,
                        'catagory' => $category,
                        'status' => $row->status,
                        'conversion_type_label' => $row->conversion_type_label,
                        'updated_at' => verta($row->updated_at)->format('Y-m-d H:i'),
                    ];
                });
        }
        return $this->getMyTicketsByCatagory($r->catagory);
    }

    function getAllByCatagory(Request $r)
    {
        if (auth()->user()->access("Ticket-Actors")) {
            // $actors = CatagoryActor::where('user_id', Auth::id())->pluck('cat_id');
            $category = CatagoryController::get($r->catagory)->name;
            return Ticket::where('cat_id', $r->catagory)
                ->select('id', 'title', 'status', 'user_id', 'updated_at')
                ->get()
                ->map(function ($row) use ($category) {
                    return [
                        'id' => $row->id,
                        'title' => $row->title,
                        'user' => $row->user()?->display_name,
                        'catagory' => $category,
                        'status' => $row->status,
                        'conversion_type_label' => $row->conversion_type_label,
                        'updated_at' => verta($row->updated_at)->format('Y-m-d H:i'),
                    ];
                });
        }
        return $this->getMyTicketsByCatagory($r->catagory);
    }

    public static function get($id)
    {
        return Ticket::find($id);
        // $ticket = Cache::remember('ticket_' . $id, 60, function () use ($id) {

        // });
    }

    public static function findByTicketId($ticket_id)
    {
        return Ticket::where('ticket_id', $ticket_id)->first();
    }

    public function syncTickets(CrmClient $crmClient)
    {
        // ۱. شمارش کل تیکت‌ها
        $totalTickets = Ticket::count();
        $processedCount = 0;
        $successCount = 0;
        $errorCount = 0;
        $skippedCount = 0;
        
        echo "Total tickets to sync: $totalTickets<br>";
        echo "Starting sync process...<br><br>";
        flush();
        ob_flush();
        
        // ۲. پردازش دسته‌ای (هر دسته 10 تیکت) برای جلوگیری از overload
        $chunkSize = 10;
        $delayBetweenChunks = 2; // ثانیه
        
        Ticket::orderBy('id', 'asc')->chunk($chunkSize, function ($tickets) use ($crmClient, &$processedCount, &$successCount, &$errorCount, &$skippedCount, $totalTickets, $delayBetweenChunks) {
            foreach ($tickets as $ticket) {
                $processedCount++;
                $result = $this->syncSingleTicket($crmClient, $ticket);
                
                if ($result === 'success') {
                    $successCount++;
                } elseif ($result === 'skipped') {
                    $skippedCount++;
                } else {
                    $errorCount++;
                }
                
                // نمایش پیشرفت
                if ($processedCount % 10 == 0 || $processedCount == $totalTickets) {
                    $progress = round(($processedCount / $totalTickets) * 100, 2);
                    echo "Progress: $processedCount/$totalTickets ($progress%) - Success: $successCount, Skipped: $skippedCount, Errors: $errorCount<br>";
                    flush();
                    ob_flush();
                }
                
                // تأخیر کوتاه بین هر تیکت برای جلوگیری از rate limiting
                usleep(500000); // 0.5 ثانیه
            }
            
            // تأخیر بین دسته‌ها
            if ($processedCount < $totalTickets) {
                sleep($delayBetweenChunks);
            }
        });

        echo "<br>=== Sync Summary ===<br>";
        echo "Total: $totalTickets<br>";
        echo "Success: $successCount<br>";
        echo "Skipped: $skippedCount<br>";
        echo "Errors: $errorCount<br>";
        
        return "Tickets sync process completed. Processed: $processedCount, Success: $successCount, Skipped: $skippedCount, Errors: $errorCount";
    }

    private function syncSingleTicket(CrmClient $crmClient, $ticket)
    {
        try {
            // ۳. دریافت اطلاعات مرتبط
            $category = TicketCatagory::find($ticket->cat_id);
            $user = $ticket->user();
            // $actor = $ticket->actor();

            // ۴. پیدا کردن یا ایجاد Contact در CRM بر اساس email کاربر
            $contactId = null;
            if ($user && $user->email) {
                $mobile = $this->convertPersianToEnglish($user->email);
                
                $response = $crmClient->request("contacts", "GET", [
                    '$select' => 'contactid,fullname,mobilephone',
                    '$filter' => "mobilephone eq '$mobile'"
                ]);

                if ($response->successful()) {
                    $body = $response->json();
                    if (!empty($body['value'])) {
                        // مخاطب موجود است
                        $contactId = $body['value'][0]['contactid'];
                    } else {
                        // مخاطب وجود ندارد → ایجاد جدید
                        $createResponse = $crmClient->save('contacts', [
                            "createdon" => now(),
                            "telephone1" => $mobile,
                            "mobilephone" => $mobile,
                            "firstname" => $user->display_name ?? $user->name,
                            "emailaddress1" => $user->email,
                        ]);

                        if ($createResponse->successful()) {
                            $entityIdHeader = $createResponse->header('OData-EntityId');
                            if ($entityIdHeader) {
                                // استخراج GUID از داخل پرانتز
                                preg_match('/\(([^)]+)\)/', $entityIdHeader, $matches);
                                $contactId = $matches[1] ?? null;
                            }
                        } else {
                            // اگر ایجاد contact ناموفق بود، دوباره بررسی می‌کنیم (ممکن است در حالی که در حال ایجاد بودیم، ایجاد شده باشد)
                            $retryResponse = $crmClient->request("contacts", "GET", [
                                '$select' => 'contactid,fullname,mobilephone',
                                '$filter' => "mobilephone eq '$mobile'"
                            ]);
                            if ($retryResponse->successful()) {
                                $retryBody = $retryResponse->json();
                                if (!empty($retryBody['value'])) {
                                    $contactId = $retryBody['value'][0]['contactid'];
                                }
                            }
                        }
                    }
                }
            }

            // ۵. پیدا کردن ID دسته‌بندی در CRM
            $categoryCrmId = null;
            if ($category) {
                try {
                    $response = $crmClient->request("new_ticketcategories", "GET", [
                        '$select' => 'new_name,new_ticketcategoryid',
                        '$filter' => "new_name eq '{$category->name}'"
                    ]);
                    if ($response->successful()) {
                        $body = $response->json();
                        if (!empty($body['value'])) {
                            $categoryCrmId = $body['value'][0]['new_ticketcategoryid'];
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to get category from CRM", [
                        'category_id' => $category->id,
                        'category_name' => $category->name,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // ۶. آماده‌سازی داده‌ها برای ارسال به API
            // تبدیل تاریخ از فرمت datetime (مثل: 2023-06-26 11:53:09) به Carbon برای CRM
            $createdOn = $ticket->created_at 
                ? Carbon::parse($ticket->created_at)
                : now();
            
            $modifiedOn = $ticket->updated_at 
                ? Carbon::parse($ticket->updated_at)
                : now();

            // تبدیل status از string به Option Set (عدد)
            $statusOptionSet = $this->mapStatusToOptionSet($ticket->status);

            $ticketData = [
                'new_title' => $ticket->title,
                'new_status' => $ticket->status,
                'new_status_option' => $statusOptionSet,
                'new_conversion_type' => $ticket->conversion_type,
                'new_score' => $ticket->score,
                'new_ticket_id' => $ticket->id, // ID اصلی تیکت برای اتصال کامنت‌ها
                'createdon' => $createdOn,
                'modifiedon' => $modifiedOn,
            ];

            // ۷. اضافه کردن رابطه با دسته‌بندی
            if ($categoryCrmId) {
                $ticketData['new_cat_id@odata.bind'] = "/new_ticketcategories($categoryCrmId)";
            }

            // ۸. اضافه کردن رابطه با Contact
            if ($contactId) {
                $ticketData['new_contact@odata.bind'] = "/contacts($contactId)";
            }

            // ۹. بررسی وجود تیکت در سیستم CRM (بر اساس new_ticket_id)
            $response = $crmClient->request("new_tickets", "GET", [
                '$select' => 'new_ticketid,new_title,new_ticket_id',
                '$filter' => "new_ticket_id eq {$ticket->id}"
            ]);

            if ($response->successful()) {
                $body = $response->json();
                if (!empty($body['value'])) {
                    // تیکت موجود است
                    return 'skipped';
                } else {
                    // تیکت وجود ندارد → ایجاد جدید
                    $createResponse = $crmClient->request("new_tickets", "POST", $ticketData);

                    if ($createResponse->successful()) {
                        return 'success';
                    } else {
                        // بررسی خطای duplicate
                        $errorBody = $createResponse->json();
                        if (isset($errorBody['error']['message']) && 
                            (strpos($errorBody['error']['message'], 'duplicate') !== false || 
                             strpos($errorBody['error']['message'], 'already exists') !== false)) {
                            // تیکت احتمالاً در حالی که در حال ایجاد بودیم، ایجاد شده است
                            return 'skipped';
                        }
                        
                        Log::error("Failed to create ticket in CRM", [
                            'ticket_id' => $ticket->id,
                            'title' => $ticket->title,
                            'response' => $createResponse->body()
                        ]);
                        return 'error';
                    }
                }
            } else {
                Log::error("Failed to query CRM for ticket", [
                    'ticket_id' => $ticket->id,
                    'title' => $ticket->title,
                    'response' => $response->body()
                ]);
                return 'error';
            }
        } catch (\Exception $e) {
            Log::error("Exception while syncing ticket", [
                'ticket_id' => $ticket->id,
                'title' => $ticket->title,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 'error';
        }
    }

    private function convertPersianToEnglish($string)
    {
        static $map = [
            '۰' => '0',
            '۱' => '1',
            '۲' => '2',
            '۳' => '3',
            '۴' => '4',
            '۵' => '5',
            '۶' => '6',
            '۷' => '7',
            '۸' => '8',
            '۹' => '9',
        ];

        return strtr($string, $map);
    }

    /**
     * تبدیل status از string به Option Set (عدد) برای CRM
     * مقادیر Option Set باید با مقادیر تعریف شده در CRM مطابقت داشته باشند
     */
    private function mapStatusToOptionSet($status)
    {
        // مقادیر Option Set در CRM - این مقادیر باید با Option Set در CRM شما مطابقت داشته باشند
        return match ($status) {
            'جدید', 'new' => 100000000,        // جدید
            'درحال بررسی', 'in_progress' => 100000001,         // در حال انجام (معادل بازشده)
            'پاسخ داده شده', 'answered' => 100000002, // پاسخ داده شده
            'بسته شده', 'closed' => 100000003,  // بسته شده
            default => 100000000,                // پیش‌فرض: جدید
        };
    }
}
