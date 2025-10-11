<?php

namespace TelegramTicket\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use TelegramTicket\Models\TelegramTicket;
use BaleBot\Controllers\TelegramController;

class TelegramTicketController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'open');

        if (!in_array($status, ['open', 'answered', 'closed', 'all'])) {
            $status = 'open';
        }

        $tickets = TelegramTicket::with('latestMessage')
            ->where('is_bot_generated', false)
            ->latest()
            ->when($status !== 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->get();

        return view('telegram-ticket::index', compact('tickets', 'status'));
    }

    public function show($id)
    {
        $ticket = TelegramTicket::with(['messages.replyTo'])
            ->where('is_bot_generated', false)
            ->findOrFail($id);
        return view('telegram-ticket::show', compact('ticket'));
    }

    public function reply($id, Request $request)
    {
        $ticket = TelegramTicket::with('messages')
            ->where('is_bot_generated', false)
            ->findOrFail($id);

        if ($ticket->status === 'closed') {
            return redirect()->back()->withErrors(['reply' => 'این تیکت بسته شده است و امکان پاسخ وجود ندارد.']);
        }

        $validated = $request->validate([
            'reply' => ['required', 'string'],
            'reply_to_message_id' => ['nullable', 'integer'],
        ]);

        $replyToMessage = null;
        if (!empty($validated['reply_to_message_id'])) {
            $replyToMessage = $ticket->messages()
                ->where('id', $validated['reply_to_message_id'])
                ->first();

            if (!$replyToMessage) {
                return redirect()->back()->withErrors(['reply_to_message_id' => 'پیام انتخاب شده معتبر نیست.']);
            }
        }

        $agentReply = $validated['reply'];

        $message = $ticket->messages()->create([
            'sender_id' => Auth::id(),
            'sender_type' => 'agent',
            'message' => $agentReply,
            'reply_to_message_id' => $replyToMessage?->id,
            'platform' => 'panel',
        ]);

        $ticket->status = 'answered';
        $ticket->save();

        $telegram = new TelegramController(config('bale_bot_config.TOKEN'));

        $payload = [
            'chat_id' => $ticket->user_id,
            'text' => "👨‍💼 پاسخ پشتیبان:\n{$agentReply}",
        ];

        if ($replyToMessage && $replyToMessage->platform_message_id) {
            $payload['reply_to_message_id'] = $replyToMessage->platform_message_id;
        }

        $response = $telegram->sendMessage($payload);

        if (is_string($response)) {
            $response = json_decode($response, true);
        }

        if (is_array($response) && isset($response['result']['message_id'])) {
            $message->platform_message_id = $response['result']['message_id'];
            $message->save();
        }

        return redirect()->route('telegram-tickets.show', $ticket->id)->with('success', 'پاسخ ارسال شد.');
    }

    public function closeTicket($id)
    {
        $ticket = TelegramTicket::where('is_bot_generated', false)->findOrFail($id);
        $ticket->status = 'closed';
        $ticket->save();

        return redirect()->route('telegram-tickets.show', $ticket->id)->with('success', 'تیکت بسته شد.');
    }
}
