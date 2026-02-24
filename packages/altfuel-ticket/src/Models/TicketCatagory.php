<?php

namespace Mkhodroo\AltfuelTicket\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketCatagory extends Model
{
    use HasFactory;
    public $table = "altfuel_ticket_catagories";

    protected $fillable = ['order'];

    protected $casts = [
        'conversion_type_enabled' => 'boolean',
        'conversion_type_required' => 'boolean',
    ];

    function countNews() {
        return Ticket::where('cat_id', $this->id)->where('status', config('ATConfig.status.new'))->count();
    }
}
