<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DoctorProfile;

class DoctorProfileApiController extends Controller
{
    public function index()
    {
        $doctors = DoctorProfile::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'specialization',
                'qualification',
                'phone',
                'email',
                'experience',
                'location',
                'bio',
            ]);

        return response()->json([
            'success' => true,
            'data' => $doctors,
        ]);
    }
}
