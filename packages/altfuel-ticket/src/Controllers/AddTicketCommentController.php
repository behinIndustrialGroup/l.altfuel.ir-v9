<?php

namespace Mkhodroo\AltfuelTicket\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Behin\CrmClient\CrmClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Mkhodroo\AltfuelTicket\Models\Ticket;
use Mkhodroo\AltfuelTicket\Models\TicketComment;

class AddTicketCommentController extends Controller
{

    public static function add($ticket_id, $text = null, $voice = null)
    {
        return TicketComment::create([
            'user_id' => Auth::id(),
            'ticket_id' => $ticket_id,
            'text' => $text,
            'voice' => $voice
        ]);
    }

    public function syncComments(CrmClient $crmClient)
    {
        $totalComments = TicketComment::count();
        $processedCount = 0;
        $successCount = 0;
        $errorCount = 0;
        $skippedCount = 0;

        echo "Total comments to sync: $totalComments<br>";
        echo "Starting sync process...<br><br>";
        flush();
        ob_flush();

        $chunkSize = 1000;
        $delayBetweenChunks = 0;

        TicketComment::select('id', 'ticket_id', 'user_id', 'text', 'created_at', 'updated_at')
            ->orderBy('id', 'asc')
            ->chunk($chunkSize, function ($comments) use ($crmClient, &$processedCount, &$successCount, &$errorCount, &$skippedCount, $totalComments, $delayBetweenChunks) {
                foreach ($comments as $comment) {
                    $processedCount++;
                    $result = $this->syncSingleComment($crmClient, $comment);

                    if ($result === 'success') {
                        $successCount++;
                    } elseif ($result === 'skipped') {
                        $skippedCount++;
                    } else {
                        $errorCount++;
                    }

                    if ($processedCount % 100 == 0 || $processedCount == $totalComments) {
                        $progress = round(($processedCount / $totalComments) * 100, 2);
                        echo "Progress: $processedCount/$totalComments ($progress%) - Success: $successCount, Skipped: $skippedCount, Errors: $errorCount<br>";
                        flush();
                        ob_flush();
                    }
                }
            });

        echo "<br>=== Sync Summary ===<br>";
        echo "Total: $totalComments<br>";
        echo "Success: $successCount<br>";
        echo "Skipped: $skippedCount<br>";
        echo "Errors: $errorCount<br>";

        return "Comments sync process completed. Processed: $processedCount, Success: $successCount, Skipped: $skippedCount, Errors: $errorCount";
    }

    private function syncSingleComment(CrmClient $crmClient, TicketComment $comment)
    {
        try {
            $ticket = Ticket::find($comment->ticket_id);
            if (!$ticket) {
                return 'skipped';
            }

            static $ticketCrmCache = [];
            $crmTicketId = $ticketCrmCache[$ticket->id] ?? null;
            if (!$crmTicketId) {
                $ticketLookup = $crmClient->request("new_tickets", "GET", [
                    '$select' => 'new_ticketid,new_ticket_id',
                    '$filter' => "new_ticket_id eq {$ticket->id}"
                ]);
                if ($ticketLookup->successful()) {
                    $body = $ticketLookup->json();
                    if (!empty($body['value'])) {
                        $crmTicketId = $body['value'][0]['new_ticketid'];
                        $ticketCrmCache[$ticket->id] = $crmTicketId;
                    } else {
                        return 'skipped';
                    }
                } else {
                    Log::error("Failed to query CRM for parent ticket", [
                        'ticket_id' => $ticket->id,
                        'response' => $ticketLookup->body()
                    ]);
                    return 'error';
                }
            }

            $createdOn = $comment->created_at ? Carbon::parse($comment->created_at) : now();
            $modifiedOn = $comment->updated_at ? Carbon::parse($comment->updated_at) : now();

            $isOwner = ($comment->user_id === $ticket->user_id);
            $commentData = [
                'new_text' => $comment->text,
                'new_is_owner' => $isOwner ? 1 : 0,
                'new_created_at' => $createdOn,
                'new_updated_at' => $modifiedOn,
            ];

            if ($crmTicketId) {
                $commentData['new_ticket@odata.bind'] = "/new_tickets($crmTicketId)";
            }

            $create = $crmClient->request("new_ticketcomments", "POST", $commentData);
            if ($create->successful()) {
                return 'success';
            } else {
                Log::error("Failed to create comment in CRM", [
                    'comment_id' => $comment->id,
                    'ticket_id' => $ticket->id,
                    'response' => $create->body()
                ]);
                return 'error';
            }
        } catch (\Exception $e) {
            Log::error("Exception while syncing comment", [
                'comment_id' => $comment->id,
                'ticket_id' => $comment->ticket_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 'error';
        }
    }
}

