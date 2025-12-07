<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class InstagramTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'frame_path',
        'canvas_width',
        'canvas_height',
    ];

    protected $appends = ['frame_url'];

    public function getFrameUrlAttribute(): ?string
    {
        if (empty($this->frame_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->frame_path);
    }
}
