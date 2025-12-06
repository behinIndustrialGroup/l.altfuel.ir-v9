<?php

namespace Mkhodroo\AltfuelTicket\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mkhodroo\AltfuelTicket\Models\CommentAttachments;
use Mkhodroo\AltfuelTicket\Models\Ticket;
use Mkhodroo\AltfuelTicket\Models\TicketComment;
use Behin\CrmClient\CrmClient;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class AddTicketCommentAttachmentController extends Controller
{
    
    public static function add(CrmClient $crmClient, $comment_id, $file)
    {
        $attachment = CommentAttachments::create([
            'comment_id' => $comment_id,
            'file' => $file
        ]);

        try {
            $comment = TicketComment::find($comment_id);
            if ($comment) {
                $ticketId = $comment->ticket_id;
                $ticketLookup = $crmClient->request("new_tickets", "GET", [
                    '$select' => 'new_ticketid,new_ticket_id',
                    '$filter' => "new_ticket_id eq {$ticketId}"
                ]);
                if ($ticketLookup->successful() && !empty($ticketLookup->json()['value'])) {
                    $crmTicketId = $ticketLookup->json()['value'][0]['new_ticketid'];
                    $payload = [
                        "subject" => "پیوست تیکت (لینک)",
                        "notetext" => '<a href="https://l.altfuel.ir'.$file.'" target="_blank">دانلود فایل</a>',
                        "objectid_new_ticket@odata.bind" => "/new_tickets($crmTicketId)",
                    ];
                    $crmClient->save('annotations', $payload);
                } else {
                    Log::error('CRM ticket not found for attachment', [
                        'comment_id' => $comment_id,
                        'ticket_id' => $ticketId,
                        'response' => $ticketLookup->body()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Exception while sending ticket attachment to CRM', [
                'comment_id' => $comment_id,
                'file' => $file,
                'error' => $e->getMessage()
            ]);
        }

        return $attachment;
    }

    public function syncAllAttachments(CrmClient $crmClient)
    {
        $processed = 0;
        $success = 0;
        $skipped = 0;
        $errors = 0;
        $chunkSize = 1000;
        CommentAttachments::select('id','comment_id','file')->orderBy('id','asc')->chunk($chunkSize, function ($attachments) use ($crmClient, &$processed, &$success, &$skipped, &$errors) {
            foreach ($attachments as $att) {
                $processed++;
                try {
                    $comment = TicketComment::find($att->comment_id);
                    if (!$comment) { $skipped++; continue; }
                    $ticketId = $comment->ticket_id;
                    $ticketLookup = $crmClient->request("new_tickets", "GET", [
                        '$select' => 'new_ticketid,new_ticket_id',
                        '$filter' => "new_ticket_id eq {$ticketId}"
                    ]);
                    if (!($ticketLookup->successful() && !empty($ticketLookup->json()['value']))) { $errors++; continue; }
                    $crmTicketId = $ticketLookup->json()['value'][0]['new_ticketid'];
                    $payload = [
                        "subject" => "پیوست تیکت (لینک)",
                        "notetext" => $att->file,
                        "objectid_new_ticket@odata.bind" => "/new_tickets($crmTicketId)",
                    ];
                    $resp = $crmClient->save('annotations', $payload);
                    if ($resp->successful()) { $success++; } else { $errors++; }
                } catch (\Exception $e) {
                    $errors++;
                    Log::error('Exception while syncing attachments to CRM', [
                        'attachment_id' => $att->id,
                        'comment_id' => $att->comment_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        });
        return "Processed: {$processed}, Success: {$success}, Skipped: {$skipped}, Errors: {$errors}";
    }

    public function downloadZip(Request $request)
    {

        $commentIds = TicketComment::where('ticket_id', $request->id)->pluck('id');
        $files = CommentAttachments::whereIn('comment_id', $commentIds)->pluck('file');

        if (count($files)) {
            $zipFileName = 'ticket_' . $request->id . '.zip';

            $zip = new \ZipArchive;
            $zipPath = public_path($zipFileName);

            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                foreach ($files as $file) {
                    $filePath = public_path('\..\\' .$file);
                    $zip->addFile($filePath, basename($filePath));
                }

                $zip->close();
            }

            return response()->download($zipPath)->deleteFileAfterSend(true);
        }else{
            return response('پیوستی وجود ندارد', 404);
        }

    }
}
