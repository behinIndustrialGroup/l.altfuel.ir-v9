<?php

namespace TelegramTicket\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramTicketMessage extends Model
{
    protected $fillable = [
        'ticket_id',
        'sender_id',
        'sender_type',
        'message',
        'attachment_path',
        'attachment_name',
        'attachment_mime',
        'attachment_size',
        'reply_to_message_id',
        'platform_message_id',
        'platform',
        'feedback',
    ];

    protected $casts = [
        'feedback' => 'string',
        'attachment_size' => 'integer',
    ];

    public function ticket()
    {
        return $this->belongsTo(TelegramTicket::class, 'ticket_id');
    }

    public function replyTo()
    {
        return $this->belongsTo(self::class, 'reply_to_message_id');
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'reply_to_message_id');
    }
}
