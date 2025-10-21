<?php

namespace Mkhodroo\AltfuelTicket\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\RandomStringController;
use BehinLogging\Controllers\LoggingController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Mkhodroo\AltfuelTicket\Jobs\SendTicketSmsJob;
use Mkhodroo\AltfuelTicket\Models\CatagoryActor;
use Mkhodroo\AltfuelTicket\Models\CommentAttachments;
use Mkhodroo\AltfuelTicket\Models\ImprovedAnswer;
use Mkhodroo\AltfuelTicket\Models\Ticket;
use Mkhodroo\AltfuelTicket\Models\TicketComment;
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

    public function store(TicketRequest $r)
    {
        if (isset($r->ticket_id)) {
            $ticket = GetTicketController::findByTicketId($r->ticket_id);
            // فقط ایجاد کننده و یا اپراتور باید بتواند کامنت بگذارد
            // if(!in_array(Auth::id(), [$ticket->user_id, $ticket->actor_id]) and $ticket->actor_id != null){
            //     return response(trans("access denied"), 402);
            // }
        } else { //Create new Ticket
            $ticket = $this->create($r->catagory, $r->title, $r->conversion_type);
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
        $comment = AddTicketCommentController::add($ticket->id, $r->text, $file_path);
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
}
