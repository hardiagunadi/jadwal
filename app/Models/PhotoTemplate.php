<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PhotoTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'base_image',
        'overlay_config',
    ];

    protected $casts = [
        'overlay_config' => 'array',
    ];
}
