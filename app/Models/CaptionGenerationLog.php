<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaptionGenerationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'kegiatan_id',
        'user_id',
        'keywords',
        'brand_style',
        'max_length',
        'generated_caption',
        'status',
        'error_message',
    ];

    public function kegiatan()
    {
        return $this->belongsTo(Kegiatan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
