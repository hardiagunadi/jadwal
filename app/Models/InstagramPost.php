<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class InstagramPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'media_path',
        'processed_path',
        'instagram_template_id',
        'caption',
        'keywords',
        'publish_at',
        'container_id',
        'publish_id',
        'status',
        'response_payload',
    ];

    protected $casts = [
        'publish_at' => 'datetime',
        'response_payload' => 'array',
    ];

    public function template()
    {
        return $this->belongsTo(InstagramTemplate::class, 'instagram_template_id');
    }

    public function getMediaUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->media_path);
    }

    public function getProcessedUrlAttribute(): ?string
    {
        if (empty($this->processed_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->processed_path);
    }

    public function scheduledForFuture(): bool
    {
        return $this->publish_at instanceof Carbon && $this->publish_at->isFuture();
    }
}
