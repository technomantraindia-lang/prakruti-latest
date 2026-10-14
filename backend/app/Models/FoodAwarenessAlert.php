<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodAwarenessAlert extends Model
{
    protected $fillable = [
        'title',
        'category',
        'category_label',
        'tag',
        'severity',
        'media_type',
        'image',
        'video',
        'source',
        'incident_date',
        'summary',
        'details',
        'how_to_test',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        return route('media.file', ['path' => $this->image]);
    }

    public function getVideoUrlAttribute(): ?string
    {
        if (!$this->video) {
            return null;
        }

        if (str_starts_with($this->video, 'http://') || str_starts_with($this->video, 'https://')) {
            return $this->video;
        }

        return route('media.file', ['path' => $this->video]);
    }
}
