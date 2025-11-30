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
        // ۱. خواندن 50 تیکت آخر از جدول altfuel_tickets (برای تست)
        $tickets = Ticket::latest()->take(2)->get();

        // ۲. حلقه برای ارسال داده‌ها به API برای هر تیکت
        foreach ($tickets as $ticket) {
            // ۳. دریافت اطلاعات مرتبط
            $category = TicketCatagory::find($ticket->cat_id);
            $user = $ticket->user();
            $actor = $ticket->actor();

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
                        $response = $crmClient->save('contacts', [
                            "createdon" => now(),
                            "telephone1" => $mobile,
                            "mobilephone" => $mobile,
                            "firstname" => $user->display_name ?? $user->name,
                            "emailaddress1" => $user->email,
                        ]);

                        $entityIdHeader = $response->header('OData-EntityId');
                        if ($entityIdHeader) {
                            // استخراج GUID از داخل پرانتز
                            preg_match('/\(([^)]+)\)/', $entityIdHeader, $matches);
                            $contactId = $matches[1] ?? null;
                        }
                    }
                }
            }

            // ۵. پیدا کردن ID دسته‌بندی در CRM
            $categoryCrmId = null;
            if ($category) {
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
            }

            // ۶. آماده‌سازی داده‌ها برای ارسال به API
            $ticketData = [
                'new_title' => $ticket->title,
                'new_status' => $ticket->status,
                'new_conversion_type' => $ticket->conversion_type,
                'new_score' => $ticket->score,
                'new_ticket_id' => $ticket->id, // ID اصلی تیکت برای اتصال کامنت‌ها
                'createdon' => $ticket->created_at ? Carbon::parse($ticket->created_at) : now(),
                'modifiedon' => $ticket->updated_at ? Carbon::parse($ticket->updated_at) : now(),
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
                    $ticketCrmId = $body['value'][0]['new_ticketid'];
                    echo "Ticket '{$ticket->title}' (ID: {$ticket->id}) already exists in CRM: $ticketCrmId<br>";
                } else {
                    // تیکت وجود ندارد → ایجاد جدید
                    $createResponse = $crmClient->request("new_tickets", "POST", $ticketData);

                    if ($createResponse->successful()) {
                        echo "New ticket '{$ticket->title}' (ID: {$ticket->id}) created successfully!<br>";
                    } else {
                        echo "Failed to create ticket '{$ticket->title}' (ID: {$ticket->id}): " . $createResponse->body() . "<br>";
                    }
                }
            } else {
                echo "Failed to query CRM for ticket '{$ticket->title}' (ID: {$ticket->id}): " . $response->body() . "<br>";
            }
        }

        return 'Tickets sync process completed.';
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
}
