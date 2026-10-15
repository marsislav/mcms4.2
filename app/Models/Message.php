<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['name', 'email', 'subject', 'body', 'ip', 'locale', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];
}
