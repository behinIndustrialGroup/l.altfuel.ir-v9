<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IrngvCharge extends Model
{
    use HasFactory;
    protected $table = "irngv_charges";
    protected $fillable = [
        'order_id', 'amount', 'description', 'mobile', 'callback_url', 'authority', 'ref_id', 'status'
    ];
}
