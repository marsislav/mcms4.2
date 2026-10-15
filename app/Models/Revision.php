<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Revision extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['content_translation_id', 'user_id', 'title', 'body'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
