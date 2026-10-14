<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ConsultationRequest;
use App\Models\Inquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ConsultationRequestApiController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|required_without:phone',
            'phone' => 'nullable|string|max:30|required_without:email',
            'contact_method' => 'required|in:email,whatsapp,phone',
            'concern' => 'required|string|max:2000',
            'consultation_type' => 'nullable|string|max:255',
            'specialist_type' => 'nullable|string|max:255',
            'health_goals' => 'nullable',
            'age_range' => 'nullable|string|max:30',
            'dietary_preference' => 'nullable|string|max:50',
            'preferred_time' => 'nullable|string|max:255',
            'consent' => 'accepted',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $reference = 'CONS-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));

        $prefix = [];
        if ($request->filled('consultation_type') || $request->filled('specialist_type')) {
            $specialist = $request->input('consultation_type') ?: $request->input('specialist_type');
            $prefix[] = "[Consultation: {$specialist}]";
        }
        if ($request->filled('health_goals')) {
            $goals = is_array($request->input('health_goals'))
                ? implode(', ', $request->input('health_goals'))
                : (string) $request->input('health_goals');
            if (trim($goals) !== '') {
                $prefix[] = "[Focus Areas: {$goals}]";
            }
        }
        $fullConcern = ! empty($prefix)
            ? implode(' ', $prefix) . "\n\n" . $request->input('concern')
            : $request->input('concern');

        if (! Schema::hasTable('consultation_requests')) {
            $msg = "Consultation Request {$reference}\n"
                . "Preferred contact: {$request->contact_method}\n"
                . "Age range: " . ($request->age_range ?: '-') . "\n"
                . "Dietary preference: " . ($request->dietary_preference ?: '-') . "\n"
                . "Preferred time: " . ($request->preferred_time ?: '-') . "\n\n"
                . "Concern / Goal:\n{$fullConcern}\n\n"
                . 'Consent: accepted';

            $inquiry = Inquiry::create([
                'name' => $request->name,
                'email' => $request->email ? strtolower(trim($request->email)) : 'consultation@example.invalid',
                'phone' => $request->phone ?: '-',
                'msg' => $msg,
                'product_id' => null,
                'status' => 'pending',
                'note' => 'Saved as fallback because consultation_requests table is not migrated yet.',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Consultation request saved. Our expert will contact you within 1-2 working days.',
                'data' => [
                    'id' => $inquiry->id,
                    'reference_no' => $reference,
                    'status' => 'new',
                    'stored_as' => 'inquiry',
                ],
            ], 201);
        }

        $consultation = ConsultationRequest::create([
            'reference_no' => $reference,
            'name' => $request->name,
            'email' => $request->email ? strtolower(trim($request->email)) : null,
            'phone' => $request->phone,
            'preferred_contact_method' => $request->contact_method,
            'concern' => $fullConcern,
            'age_range' => $request->age_range,
            'dietary_preference' => $request->dietary_preference,
            'preferred_time' => $request->preferred_time,
            'consent' => (bool) $request->boolean('consent'),
            'status' => 'new',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Consultation request saved. Our expert will contact you within 1-2 working days.',
            'data' => [
                'id' => $consultation->id,
                'reference_no' => $consultation->reference_no,
                'status' => $consultation->status,
            ],
        ], 201);
    }
}
