<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['user_send_id', 'user_recieve_id', 'message', 'room_id'];
}
