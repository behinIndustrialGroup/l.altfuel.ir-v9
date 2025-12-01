<?php

namespace Mkhodroo\AltfuelTicket\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\RandomStringController;
use BehinLogging\Controllers\LoggingController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Behin\CrmClient\CrmClient;
use Carbon\Carbon;
use Mkhodroo\AltfuelTicket\Jobs\SendTicketSmsJob;
use Mkhodroo\AltfuelTicket\Models\CatagoryActor;
use Mkhodroo\AltfuelTicket\Models\CommentAttachments;
use Mkhodroo\AltfuelTicket\Models\ImprovedAnswer;
use Mkhodroo\AltfuelTicket\Models\Ticket;
use Mkhodroo\AltfuelTicket\Models\TicketComment;
use Mkhodroo\AltfuelTicket\Models\TicketCatagory;
use Mkhodroo\AltfuelTicket\Requests\TicketRequest;

class CreateTicketController extends Controller
{
    function index(): View
    {
        return view('ATView::create');
    }

    public function create($cat_id, $title, $conversionType = null)
    {
        $ticket = Ticket::create([
            'user_id' => Auth::id(),
            'ticket_id' => RandomStringController::Generate(20),
            'cat_id' => $cat_id,
            'title' => $title,
            'status' => config('ATConfig.status.new'),
            'conversion_type' => $conversionType,
        ]);
        return $ticket;
    }

    public function store(TicketRequest $r, CrmClient $crmClient)
    {
        if (isset($r->ticket_id)) {
            $ticket = GetTicketController::findByTicketId($r->ticket_id);
            // فقط ایجاد کننده و یا اپراتور باید بتواند کامنت بگذارد
            // if(!in_array(Auth::id(), [$ticket->user_id, $ticket->actor_id]) and $ticket->actor_id != null){
            //     return response(trans("access denied"), 402);
            // }
        } else { //Create new Ticket
            $ticket = $this->create($r->catagory, $r->title, $r->conversion_type);
            $this->createCrmTicket($crmClient, $ticket);
            // TicketAssignController::assign($ticket->cat_id, $ticket->id);
        }
        $status = $this->changeStatus($ticket->cat_id);
        $ticket->status = $status ? $status : $ticket->status;
        $ticket->save();
        $file_path = ($r->file('payload')) ? CommentVoiceController::upload($r->file('payload'), $ticket->ticket_id) : '';
        if(Auth::id() != $ticket->user_id){
            if ($r->frequently_asked) {
                $question = self::getLastComment($ticket->id)->getData()->last_comment;
                $answer = $r->text;
                $saveImprovedResponse = self::saveImprovedResponse($question, $answer);
            }
        }
        $comment = AddTicketCommentController::add($crmClient, $ticket->id, $r->text, $file_path);
        if ($r->file('files')) {
            foreach ($r->file('files') as $name) {
                $attach = CommentAttachmentController::upload($name, $ticket->ticket_id);
                AddTicketCommentAttachmentController::add($comment->id, $attach);
            }
        }
        if(Auth::id() != $ticket->user_id){
            SendTicketSmsJob::dispatch($ticket->user()->email, $ticket->id);
        }
        
        return response([
            'ticket' => $ticket,
            'message' => "ثبت شد"
        ], 200);
    }

    function changeStatus($cat_id, $status = '')
    {
        if (!CatagoryActor::where('cat_id', $cat_id)->where('user_id', Auth::id())->first()) {
            return config('ATConfig.status.new');
        }
        return null;
    }

    public function setScore(Request $r)
    {
        $ticket = GetTicketController::findByTicketId($r->ticket_id);
        $ticket->score = $r->score;
        $ticket->save();
    }

    function score($id)
    {
        return GetTicketController::findByTicketId($id)?->score;
    }

    public static function getLastComment($ticket_id)
    {
        $lastComment = TicketComment::where('ticket_id', $ticket_id)->orderBy('id', 'desc')->first();
        return response()->json(['last_comment' => $lastComment ? $lastComment->text : '']);
    }

    public static function saveImprovedResponse($question, $answer)
    {
        $improvedResponse = new ImprovedAnswer();
        $improvedResponse->question = $question;
        $improvedResponse->answer = $answer;
        $improvedResponse->save();

        return response()->json(['message' => 'پاسخ بهبود یافته با موفقیت ذخیره شد.']);
    }

    private function createCrmTicket(CrmClient $crmClient, Ticket $ticket)
    {
        try {
            $category = TicketCatagory::find($ticket->cat_id);
            $user = $ticket->user();
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
                        $contactId = $body['value'][0]['contactid'];
                    } else {
                        $createResponse = $crmClient->save('contacts', [
                            'createdon' => now(),
                            'telephone1' => $mobile,
                            'mobilephone' => $mobile,
                            'firstname' => $user->display_name ?? $user->name,
                            'emailaddress1' => $user->email,
                        ]);
                        if ($createResponse->successful()) {
                            $entityIdHeader = $createResponse->header('OData-EntityId');
                            if ($entityIdHeader) {
                                preg_match('/\(([^)]+)\)/', $entityIdHeader, $matches);
                                $contactId = $matches[1] ?? null;
                            }
                        }
                    }
                }
            }

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

            $createdOn = $ticket->created_at ? Carbon::parse($ticket->created_at) : now();
            $modifiedOn = $ticket->updated_at ? Carbon::parse($ticket->updated_at) : now();
            $statusOptionSet = $this->mapStatusToOptionSet($ticket->status);

            $ticketData = [
                'new_title' => $ticket->title,
                'new_status' => $ticket->status,
                'new_status_option' => $statusOptionSet,
                'new_conversion_type' => $ticket->conversion_type,
                'new_score' => $ticket->score,
                'new_ticket_id' => $ticket->id,
                'new_created_at' => $createdOn,
                'new_updated_at' => $modifiedOn,
            ];

            if ($categoryCrmId) {
                $ticketData['new_cat_id@odata.bind'] = "/new_ticketcategories($categoryCrmId)";
            }

            if ($contactId) {
                $ticketData['new_contact@odata.bind'] = "/contacts($contactId)";
            }

            $response = $crmClient->request("new_tickets", "GET", [
                '$select' => 'new_ticketid,new_title,new_ticket_id',
                '$filter' => "new_ticket_id eq {$ticket->id}"
            ]);

            if ($response->successful()) {
                $body = $response->json();
                if (!empty($body['value'])) {
                    return true;
                } else {
                    $createResponse = $crmClient->request("new_tickets", "POST", $ticketData);
                    if ($createResponse->successful()) {
                        return true;
                    }
                    Log::error('Failed to create ticket in CRM', [
                        'ticket_id' => $ticket->id,
                        'response' => $createResponse->body()
                    ]);
                    return false;
                }
            }

            Log::error('Failed to query CRM for ticket', [
                'ticket_id' => $ticket->id,
                'response' => $response->body()
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Exception while creating ticket in CRM', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

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
