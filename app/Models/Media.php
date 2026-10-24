<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = ['path', 'thumb_path', 'original_name', 'mime', 'size', 'width', 'height', 'alt', 'user_id'];

    public function url(): string
    {
        return upload_url($this->path);
    }

    public function thumbUrl(): string
    {
        return upload_url($this->thumb_path ?: $this->path);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime, 'image/');
    }

    public function isVideo(): bool
    {
        return str_starts_with($this->mime, 'video/');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
