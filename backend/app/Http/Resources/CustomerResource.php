<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'profession' => $this->profession,
            'gender' => $this->gender,
            'terms_accepted_at' => $this->terms_accepted_at ? $this->terms_accepted_at->toIso8601String() : null,
            'role' => $this->role?->name ?? 'Customer',
            'status' => $this->status ?? 'active',
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}
