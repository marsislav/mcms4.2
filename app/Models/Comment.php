<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    public const STATUSES = ['pending' => 'Чакащи', 'approved' => 'Одобрени', 'spam' => 'Спам', 'trash' => 'Кошче'];

    protected $fillable = [
        'content_id', 'parent_id', 'author_name', 'author_email', 'author_url', 'author_ip',
        'user_agent', 'body', 'status', 'user_id',
    ];

    public function content()
    {
        return $this->belongsTo(Content::class)->withTrashed();
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function scopeApproved(Builder $q): Builder
    {
        return $q->where('status', 'approved');
    }
}
