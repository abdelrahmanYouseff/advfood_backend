<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappMsg extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_msg';

    protected $fillable = [
        'deliver_order',
        'location',
    ];
}


