<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'action', 'subject', 'details', 'ip', 'user_agent'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
