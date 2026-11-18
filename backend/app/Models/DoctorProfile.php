<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorProfile extends Model
{
    protected $fillable = [
        'name',
        'specialization',
        'qualification',
        'phone',
        'email',
        'experience',
        'location',
        'bio',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function consultations()
    {
        return $this->hasMany(ConsultationRequest::class);
    }

    /**
     * Always format doctor name cleanly with exactly one 'Dr.' prefix.
     */
    public function getFormattedNameAttribute(): string
    {
        $name = trim((string) $this->name);
        $clean = preg_replace('/^Dr\.?\s+/i', '', $name);
        return 'Dr. ' . $clean;
    }
}
