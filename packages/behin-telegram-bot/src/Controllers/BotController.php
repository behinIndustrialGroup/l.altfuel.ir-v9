<?php

namespace TelegramBot\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mkhodroo\AltfuelTicket\Controllers\LangflowController;
use TelegramBot\Models\TelegramUser;
use TelegramTicket\Models\TelegramTicket;

class BotController extends Controller
{
    public function chat()
    {

        Log::info("Receive Message");
        $content = file_get_contents('php://input');
        $update = json_decode($content, true);
        if (isset($update['callback_query'])) {
            return $this->handleCallback($update);
        }
        $telegram = new TelegramController(config('telegram_bot_config.TOKEN'));

        $message = $update['message'] ?? null;
        $chat_id = $message['chat']['id'] ?? null;
        $text = $message['text'] ?? null;
        $caption = $message['caption'] ?? null;
        $contact = $message['contact'] ?? null;
        $telegramMessageId = $message['message_id'] ?? null; // ✅ اضافه شد
        $replyToPlatformId = $message['reply_to_message']['message_id'] ?? null;

        if (!$chat_id || !$telegramMessageId) return;

        $openTicket = TelegramTicket::where('user_id', $chat_id)->whereIn('status', ['open', 'answered'])->first();
        if ($openTicket) {
            $alreadyStored = $openTicket->messages()->where('platform_message_id', $telegramMessageId)->exists();
            if ($alreadyStored) {
                Log::info("Duplicate ticket message ignored: $telegramMessageId");
                return;
            }

            $replyTarget = null;
            if ($replyToPlatformId) {
                $replyTarget = $openTicket->messages()
                    ->where('platform_message_id', $replyToPlatformId)
                    ->first();
            }

            $attachmentMeta = $this->extractAttachmentFromMessage($message);
            $storedAttachment = $attachmentMeta ? $this->downloadTelegramAttachment($telegram, $attachmentMeta) : null;

            $messageContent = trim((string)($text ?? $caption ?? ''));

            $messageData = [
                'sender_id' => $chat_id,
                'sender_type' => 'user',
                'message' => $messageContent,
                'reply_to_message_id' => $replyTarget?->id,
                'platform_message_id' => $telegramMessageId,
            ];

            if ($storedAttachment) {
                $messageData = array_merge($messageData, $storedAttachment);
            }

            $openTicket->messages()->create($messageData);

            $openTicket->status = 'open';
            $openTicket->save();

            $ackPayload = [
                'chat_id' => $chat_id,
                'text' => 'پیام شما به پشتیبانی ارسال شد. منتظر پاسخ کارشناس باشید.'
            ];

            if ($telegramMessageId) {
                $ackPayload['reply_to_message_id'] = $telegramMessageId;
            }

            $telegram->sendMessage($ackPayload);
            return;
        }

        // ✅ چک کن که آیا قبلاً این پیام پردازش شده یا نه
        $alreadyProcessed = DB::table('telegram_messages')
            ->where('telegram_message_id', $telegramMessageId)
            ->where('user_id', $chat_id)
            ->exists();

        if ($alreadyProcessed) {
            Log::info("Duplicate message ignored: $telegramMessageId");
            return;
        }

        $user = TelegramUser::firstOrCreate(['chat_id' => $chat_id]);

        // گرفتن نام کاربر
        if (!$user->name) {
            if ($text !== '/start') {
                $user->name = $text;
                $user->save();

                $telegram->sendMessage([
                    'chat_id' => $chat_id,
                    'text' => "مرسی {$text} 🙏\nحالا لطفاً شماره تماس خود را ارسال کن:",
                    'reply_markup' => json_encode([
                        'keyboard' => [
                            [['text' => '📞 ارسال شماره من', 'request_contact' => true]]
                        ],
                        'resize_keyboard' => true,
                        'one_time_keyboard' => true
                    ])
                ]);
                return;
            }

            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => "سلام! من پاکو هستم 🤖\nدستیار هوش مصنوعی شما در تلگرام.\nبرای شروع لطفاً نام خود را وارد کن."
            ]);
            return;
        }

        // گرفتن شماره تلفن
        if (!$user->phone) {
            if ($contact && isset($contact['phone_number'])) {
                $user->phone = $contact['phone_number'];
                $user->save();
            } elseif (preg_match('/^09\d{9}$/', $text)) {
                $user->phone = $text;
                $user->save();
            } else {
                $telegram->sendMessage([
                    'chat_id' => $chat_id,
                    'text' => "❗ لطفاً شماره تلفن معتبر وارد کن یا با دکمه زیر ارسال کن:",
                    'reply_markup' => json_encode([
                        'keyboard' => [
                            [['text' => '📞 ارسال شماره من', 'request_contact' => true]]
                        ],
                        'resize_keyboard' => true,
                        'one_time_keyboard' => true
                    ])
                ]);
                return;
            }

            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => "✅ اطلاعاتت ثبت شد. حالا سوالت رو بپرس ✨"
            ]);
            return;
        }

        // پردازش سوال کاربر
        if ($text && $text !== '/start') {
            try {
                $botResponse = LangflowController::run($text, $chat_id);
            } catch (\Exception $e) {
                Log::error("Langflow Error: " . $e->getMessage());
                $telegram->sendMessage([
                    'chat_id' => $chat_id,
                    'text' => "❌ متأسفم، مشکلی پیش اومده. لطفاً دوباره امتحان کن."
                ]);
                return;
            }

            $messageId = DB::table('telegram_messages')->insertGetId([
                'user_id' => $chat_id,
                'user_message' => $text,
                'bot_response' => $botResponse,
                'feedback' => 'none',
                'telegram_message_id' => $telegramMessageId, // ✅ اضافه شد
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '👍', 'callback_data' => "like:$messageId"],
                        ['text' => '👎', 'callback_data' => "dislike:$messageId"],
                    ]
                ]
            ];

            $response = $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => $botResponse,
                'reply_markup' => json_encode($keyboard)
            ]);

            $responseData = json_decode($response, true);
            $msgTelegramId = $responseData['result']['message_id'] ?? null;

            DB::table('telegram_messages')->where('id', $messageId)->update([
                'telegram_message_id' => $msgTelegramId
            ]);

            return;
        }

        if ($text === '/start') {
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => "سلام {$user->name} ! من پاکو هستم 🤖\nدستیار هوش مصنوعی شما در تلگرام.\nچه کمکی از دستم بر میاد"
            ]);
            return;
        }
    }

    private function extractAttachmentFromMessage(array $message): ?array
    {
        $attachmentKeys = [
            'document' => 'document',
            'photo' => 'photo',
            'audio' => 'audio',
            'voice' => 'voice',
            'video' => 'video',
            'video_note' => 'video_note',
            'animation' => 'animation',
        ];

        foreach ($attachmentKeys as $key => $type) {
            if (empty($message[$key])) {
                continue;
            }

            $fileData = $message[$key];
            if ($key === 'photo') {
                if (!is_array($fileData)) {
                    continue;
                }
                $fileData = $fileData[array_key_last($fileData)] ?? null;
            }

            if (!is_array($fileData) || empty($fileData['file_id'])) {
                continue;
            }

            return [
                'type' => $type,
                'file_id' => $fileData['file_id'],
                'file_unique_id' => $fileData['file_unique_id'] ?? null,
                'file_name' => $fileData['file_name'] ?? ($fileData['file_unique_id'] ?? null),
                'mime_type' => $fileData['mime_type'] ?? ($type === 'photo' ? 'image/jpeg' : null),
                'file_size' => $fileData['file_size'] ?? null,
            ];
        }

        return null;
    }

    private function downloadTelegramAttachment(TelegramController $telegram, array $attachmentMeta): ?array
    {
        try {
            $fileResponse = $telegram->getFile($attachmentMeta['file_id']);
        } catch (\Throwable $throwable) {
            Log::error('Telegram attachment getFile failed', [
                'exception' => $throwable->getMessage(),
            ]);
            return null;
        }

        if (!is_array($fileResponse) || !($fileResponse['ok'] ?? false)) {
            Log::warning('Telegram attachment getFile returned invalid response', [
                'response' => $fileResponse,
            ]);
            return null;
        }

        $filePath = $fileResponse['result']['file_path'] ?? null;
        if (!$filePath) {
            return null;
        }

        $downloadUrl = sprintf('https://api.telegram.org/file/bot%s/%s', config('telegram_bot_config.TOKEN'), $filePath);

        try {
            $response = Http::timeout(30)->get($downloadUrl);
        } catch (\Throwable $throwable) {
            Log::error('Telegram attachment download failed', [
                'url' => $downloadUrl,
                'exception' => $throwable->getMessage(),
            ]);
            return null;
        }

        if (!$response->successful()) {
            Log::warning('Telegram attachment download returned unsuccessful response', [
                'url' => $downloadUrl,
                'status' => $response->status(),
            ]);
            return null;
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        if (!$extension && !empty($attachmentMeta['mime_type'])) {
            $extension = $this->guessExtensionFromMime($attachmentMeta['mime_type']);
        }

        $filename = Str::uuid()->toString() . ($extension ? '.' . $extension : '');
        $storageDirectory = 'telegram-ticket/' . ($attachmentMeta['type'] ?? 'attachments') . '/' . now()->format('Y/m/d');
        $storagePath = $storageDirectory . '/' . $filename;

        Storage::disk('public')->put($storagePath, $response->body());

        return [
            'attachment_path' => $storagePath,
            'attachment_name' => $attachmentMeta['file_name'] ?? $filename,
            'attachment_mime' => $attachmentMeta['mime_type'] ?? null,
            'attachment_size' => $attachmentMeta['file_size'] ?? strlen($response->body()),
        ];
    }

    private function guessExtensionFromMime(?string $mime): ?string
    {
        if (!$mime) {
            return null;
        }

        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'application/zip' => 'zip',
            'application/x-zip-compressed' => 'zip',
            'audio/mpeg' => 'mp3',
            'audio/ogg' => 'ogg',
            'audio/webm' => 'webm',
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            'video/x-msvideo' => 'avi',
            'text/plain' => 'txt',
        ];

        return $map[$mime] ?? null;
    }

    public function handleCallback()
    {
        Log::info("Receive Callback");
        $content = file_get_contents("php://input");
        $update = json_decode($content, true);

        if (isset($update['callback_query'])) {
            Log::info($update);
            $callbackData = $update['callback_query']['data'];
            $chatId = $update['callback_query']['message']['chat']['id'];
            $msgTelegramId = $update['callback_query']['message']['message_id'];

            list($action, $msgId) = explode(':', $callbackData);

            DB::table('telegram_messages')->where('id', $msgId)->update([
                'feedback' => $action,
                'updated_at' => now()
            ]);

            if ($action === 'dislike') {
                $lastMessages = DB::table('telegram_messages')
                    ->where('user_id', $chatId)
                    ->orderByDesc('id')
                    ->limit(3)
                    ->get()
                    ->reverse();

                $compiledMessages = "📩 پیام‌های اخیر کاربر:\n";
                foreach ($lastMessages as $msg) {
                    $compiledMessages .= "👤 کاربر: {$msg->user_message}\n🤖 ربات: {$msg->bot_response}\n\n";
                }

                // ✅ ایجاد تیکت با استفاده از مدل پکیج
                $ticket = TelegramTicket::create([
                    'user_id' => $chatId,
                    'status' => 'open',
                ]);

                foreach ($lastMessages as $msg) {
                    if (!empty($msg->user_message)) {
                        $ticket->messages()->create([
                            'sender_id' => $chatId,
                            'sender_type' => 'user',
                            'message' => $msg->user_message,
                        ]);
                    }

                    if (!empty($msg->bot_response)) {
                        $ticket->messages()->create([
                            'sender_type' => 'bot',
                            'message' => $msg->bot_response,
                            'platform_message_id' => $msg->telegram_message_id,
                        ]);
                    }
                }

                Log::info("تیکت جدید برای پشتیبانی ثبت شد:\n" . $compiledMessages);
            }


            $telegram = new TelegramController(config('telegram_bot_config.TOKEN'));

            // حذف دکمه‌ها
            $telegram->editMessageReplyMarkup([
                'chat_id' => $chatId,
                'message_id' => $msgTelegramId,
                'reply_markup' => json_encode(['inline_keyboard' => []])
            ]);

            // ارسال پیام تشکر
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'ممنون بابت بازخورد شما 🙏'
            ]);
        }
    }
}
