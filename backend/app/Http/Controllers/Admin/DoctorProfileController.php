<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DoctorProfile;
use Illuminate\Http\Request;

class DoctorProfileController extends Controller
{
    public function index()
    {
        $doctors = DoctorProfile::query()
            ->withCount('consultations')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.doctor-profiles.index', compact('doctors'));
    }

    public function store(Request $request)
    {
        DoctorProfile::create($this->validated($request));

        return back()->with('success', 'Doctor profile added to the master list.');
    }

    public function update(Request $request, DoctorProfile $doctorProfile)
    {
        $doctorProfile->update($this->validated($request));

        return back()->with('success', 'Doctor profile updated successfully.');
    }

    public function destroy(DoctorProfile $doctorProfile)
    {
        if ($doctorProfile->consultations()->exists()) {
            $doctorProfile->update(['is_active' => false]);

            return back()->with('success', 'Doctor profile was deactivated because it has consultation history.');
        }

        $doctorProfile->delete();

        return back()->with('success', 'Doctor profile removed from the master list.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:191'],
            'experience' => ['nullable', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]) + [
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) $request->input('sort_order', 0),
        ];
    }
}
