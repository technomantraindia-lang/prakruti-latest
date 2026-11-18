<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class WebsiteVisit extends Model
{
    protected $fillable = [
        'visitor_key',
        'visit_date',
        'user_id',
        'visitor_name',
        'visitor_email',
        'visitor_phone',
        'is_authenticated',
        'marketing_consent',
        'contact_submitted_at',
        'follow_up_sent_at',
        'ip_address',
        'user_agent',
        'referrer',
        'landing_path',
        'page_title',
        'visit_count',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'is_authenticated' => 'boolean',
        'marketing_consent' => 'boolean',
        'contact_submitted_at' => 'datetime',
        'follow_up_sent_at' => 'datetime',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->user?->name ?: ($this->visitor_name ?: 'Guest visitor');
    }

    public function getDisplayEmailAttribute(): ?string
    {
        return $this->user?->email ?: $this->visitor_email;
    }

    public function getDisplayPhoneAttribute(): ?string
    {
        return $this->user?->phone ?: $this->visitor_phone;
    }
}
