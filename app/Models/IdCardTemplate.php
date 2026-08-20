<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class IdCardTemplate extends Model
{
    protected $fillable = [
        'front_image_path',
        'back_image_path',
        'front_layout',
        'back_layout',
    ];

    protected $casts = [
        'front_layout' => 'array',
        'back_layout' => 'array',
    ];

    protected $appends = ['front_image_url', 'back_image_url'];

    public function getFrontImageUrlAttribute(): ?string
    {
        return $this->front_image_path ? Storage::disk('public')->url($this->front_image_path) : null;
    }

    public function getBackImageUrlAttribute(): ?string
    {
        return $this->back_image_path ? Storage::disk('public')->url($this->back_image_path) : null;
    }
}