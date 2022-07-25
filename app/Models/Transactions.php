<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transactions extends Model
{
    use HasFactory;

    protected $fillable = [
        'from',
        'send_to',
        'sending_amount',
        'recieving_amount',
        'charges',
        'sender_balance',
        'description',
        'reciever_balance',
        'transfer_type',
        'status',
    ];
}
