<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $fillable = [
        'name',
        'location',
        'img',
        'video',
        'rating',
        'msg',
        'status',
        'source',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    protected $appends = [
        'image_url',
        'video_url',
    ];

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['active', 'approved']);
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->img) {
            return null;
        }

        if (str_starts_with($this->img, 'http://') || str_starts_with($this->img, 'https://')) {
            return $this->img;
        }

        return asset('storage/' . ltrim($this->img, '/'));
    }

    public function getVideoUrlAttribute(): ?string
    {
        if (! $this->video) {
            return null;
        }

        if (str_starts_with($this->video, 'http://') || str_starts_with($this->video, 'https://')) {
            return $this->video;
        }

        return asset('storage/' . ltrim($this->video, '/'));
    }
}
