<?php

namespace Mkhodroo\AltfuelTicket\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Ticket extends Model
{
    use HasFactory;

    public $table = "altfuel_tickets";
    protected $fillable = [
        'ticket_id',
        'user_id',
        'cat_id',
        'title',
        'status',
        'junk',
        'actor_id',
        'conversion_type',
    ];

    protected $appends = [
        'conversion_type_label',
    ];
    

    // protected static function boot()
    // {
    //     parent::boot();

    //     static::created(function ($ticket) {
    //         Cache::put('ticket_' . $ticket->id, $ticket, now()->addMinutes(60));
    //     });

    //     static::updated(function ($ticket) {
    //         Cache::put('ticket_' . $ticket->id, $ticket, now()->addMinutes(60));
    //     });

    //     static::deleted(function ($ticket) {
    //         Cache::forget('ticket_' . $ticket->id);
    //     });
    // }

    public function comments() {
        return TicketComment::where('ticket_id', $this->id)->get();
    }

    function catagory() {
        return TicketCatagory::find($this->cat_id)->only(['id', 'name']);
    }

    function user() {
        return User::find($this->user_id);
    }

    function actor() {
        return User::find($this->actor_id);
    }

    public function getConversionTypeLabelAttribute(): ?string
    {
        $types = config('ATConfig.conversion_types', []);
        return $this->conversion_type ? ($types[$this->conversion_type] ?? $this->conversion_type) : null;
    }
}
