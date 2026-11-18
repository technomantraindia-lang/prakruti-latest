<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ConsultationRequest;
use App\Models\DoctorProfile;
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
            'session_type' => 'required|in:new_consultation,follow_up',
            'payment_status' => 'required|in:paid_demo,paid',
            'payment_method' => 'required|in:upi,card,netbanking',
            'payment_gateway' => 'nullable|string|max:50',
            'payment_transaction_id' => 'required|string|max:100',
            'payment_amount' => 'required|numeric|min:0',
            'consent' => 'accepted',
            'doctor_profile_id' => 'nullable|integer|exists:doctor_profiles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $doctor = null;
        if ($request->filled('doctor_profile_id')) {
            $doctor = DoctorProfile::query()
                ->whereKey($request->integer('doctor_profile_id'))
                ->where('is_active', true)
                ->first();

            if (! $doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select an active doctor profile.',
                    'errors' => ['doctor_profile_id' => ['The selected doctor is no longer available.']],
                ], 422);
            }
        }

        $reference = 'CONS-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
        $user = $request->user();

        // Calculate visit number (1st time, 2nd time, etc.) by checking past consultations
        $phoneDigits = substr(preg_replace('/[^0-9]/', '', (string)$request->phone), -10);
        $pastQuery = ConsultationRequest::query();
        if ($user) {
            $pastQuery->where(function ($q) use ($user, $phoneDigits, $request) {
                $q->where('user_id', $user->id);
                if (!empty($phoneDigits)) {
                    $q->orWhere('phone', 'like', "%{$phoneDigits}%");
                }
                if ($request->email) {
                    $q->orWhere('email', strtolower(trim($request->email)));
                }
            });
        } elseif (!empty($phoneDigits)) {
            $pastQuery->where(function ($q) use ($phoneDigits, $request) {
                $q->where('phone', 'like', "%{$phoneDigits}%");
                if ($request->email) {
                    $q->orWhere('email', strtolower(trim($request->email)));
                }
            });
        } elseif ($request->email) {
            $pastQuery->where('email', strtolower(trim($request->email)));
        }

        $previousConsultationCount = $pastQuery->count();
        $sessionNumber = $previousConsultationCount + 1;
        $resolvedSessionType = ($sessionNumber > 1 || $request->input('session_type') === 'follow_up') ? 'follow_up' : 'new_consultation';

        $prefix = [];
        if ($sessionNumber > 1) {
            $prefix[] = "[PATIENT VISIT #{$sessionNumber}: 2nd Time / Follow-up Patient]";
        } else {
            $prefix[] = "[PATIENT VISIT #1: 1st Time / Initial Patient]";
        }

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
                    'session_type' => $request->input('session_type'),
                    'session_number' => $sessionNumber,
                    'doctor_profile_id' => $doctor?->id,
                ],
            ], 201);
        }

        $consultation = ConsultationRequest::create([
            'reference_no' => $reference,
            'user_id' => $user->id,
            'name' => $request->name,
            'email' => $request->email ? strtolower(trim($request->email)) : null,
            'phone' => $request->phone,
            'preferred_contact_method' => $request->contact_method,
            'concern' => $fullConcern,
            'age_range' => $request->age_range,
            'dietary_preference' => $request->dietary_preference,
            'preferred_time' => $request->preferred_time,
            'consent' => (bool) $request->boolean('consent'),
            'status' => 'not_sent',
            'assigned_to' => $doctor?->name,
            'doctor_profile_id' => $doctor?->id,
            'doctor_phone' => $doctor?->phone,
            'doctor_email' => $doctor?->email,
            'session_type' => $resolvedSessionType,
            'session_number' => $sessionNumber,
            'payment_status' => $request->input('payment_status'),
            'payment_method' => $request->input('payment_method'),
            'payment_gateway' => $request->input('payment_gateway') ?: 'demo',
            'payment_transaction_id' => $request->input('payment_transaction_id'),
            'payment_amount' => $request->input('payment_amount'),
            'payment_paid_at' => now(),
        ]);

        // Dispatch notification to Admin
        try {
            $adminRoleIds = \App\Models\Role::whereIn('name', ['Super Admin', 'Admin'])->pluck('id');
            $admins = \App\Models\User::whereIn('role_id', $adminRoleIds)->get();
            foreach ($admins as $adm) {
                $adm->notify(new \App\Notifications\ConsultationStatusUpdatedNotification($consultation, 'booked', 'admin'));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Admin notification dispatch error: ' . $e->getMessage());
        }

        // Dispatch notification to Customer (if registered account or matching email exists)
        try {
            $custUser = $user ?: ($consultation->email ? \App\Models\User::where('email', strtolower(trim($consultation->email)))->first() : null);
            if ($custUser) {
                $custUser->notify(new \App\Notifications\ConsultationStatusUpdatedNotification($consultation, 'booked', 'customer'));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Customer notification dispatch error: ' . $e->getMessage());
        }

        // Prepare customer WhatsApp booking confirmation message
        $cleanPhone = preg_replace('/[^0-9]/', '', (string)$consultation->phone);
        if (strlen($cleanPhone) === 10) {
            $cleanPhone = '91' . $cleanPhone;
        }
        $isReturning = $sessionNumber > 1;
        $waConfirmText = "🌿 *PRAKRUTI LIFESTYLE MEDICINE* 🌿\nNamaste {$consultation->name}!\n\nYour consultation booking has been received successfully!\n\n• Reference No: #{$consultation->reference_no}\n• Visit: " . ($isReturning ? "2nd Time Consultation (Follow-up #{$sessionNumber})" : "1st Time Initial Consultation") . "\n• Payment: PAID (₹" . number_format((float) ($consultation->payment_amount ?: 1500), 0) . ")\n\nOur healthcare desk will assign your doctor and schedule your appointment time shortly. You will receive your appointment time and video meeting link right here on WhatsApp.\n\nThank you for choosing Prakruti!\n- Prakruti Care Team (+91 92746 83460)";
        $customerWaUrl = !empty($cleanPhone) ? "https://api.whatsapp.com/send?phone={$cleanPhone}&text=" . rawurlencode($waConfirmText) : null;

        return response()->json([
            'success' => true,
            'message' => 'Consultation booked successfully! Your appointment details and live meeting link will appear directly in your customer account dashboard.',
            'data' => [
                'id' => $consultation->id,
                'reference_no' => $consultation->reference_no,
                'status' => $consultation->status,
                'session_type' => $consultation->session_type,
                'session_number' => $consultation->session_number,
                'is_second_time' => $isReturning,
                'visit_badge_label' => $consultation->visit_badge_label,
                'customer_whatsapp_url' => $customerWaUrl,
                'payment_status' => $consultation->payment_status,
                'doctor_profile_id' => $consultation->doctor_profile_id,
            ],
        ], 201);
    }

    /**
     * Get all consultations for the authenticated customer.
     */
    public function getCustomerConsultations(Request $request)
    {
        $user = $request->user();

        if (! Schema::hasTable('consultation_requests')) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $consultations = ConsultationRequest::query()
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if ($user->email) {
                    $q->orWhere('email', strtolower($user->email));
                }
                if ($user->phone) {
                    $q->orWhere('phone', $user->phone);
                }
            })
            ->latest('id')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'reference_no' => $item->reference_no,
                    'patient_name' => $item->name,
                    'session_type' => $item->session_type === 'follow_up' ? 'Follow-up Session' : 'Initial Consultation',
                    'session_raw_type' => $item->session_type,
                    'session_number' => $item->session_number,
                    'is_second_time' => (bool) $item->is_returning_patient,
                    'visit_badge_label' => $item->visit_badge_label,
                    'visit_number' => $item->visit_number,
                    'concern' => $item->concern,
                    'status' => $item->status,
                    'video_status' => $item->video_status,
                    'admin_approved' => (bool) $item->admin_approved,
                    'scheduled_at' => $item->scheduled_at ? $item->scheduled_at->format('d M Y, h:i A') : null,
                    'scheduled_at_iso' => $item->scheduled_at ? $item->scheduled_at->toIso8601String() : null,
                    'meeting_link' => $item->zoom_link,
                    'assigned_doctor' => $item->assigned_to ?: 'Lifestyle Medicine Specialist',
                    'doctor_notes' => $item->doctor_notes,
                    'payment_status' => $item->payment_status,
                    'payment_amount' => $item->payment_amount,
                    'created_at' => $item->created_at ? $item->created_at->format('d M Y') : null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $consultations,
        ]);
    }

    /**
     * Track consultation by reference number.
     */
    public function track(Request $request)
    {
        $ref = trim((string) $request->get('ref'));

        if (empty($ref)) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a valid consultation reference number.',
            ], 422);
        }

        if (! Schema::hasTable('consultation_requests')) {
            return response()->json([
                'success' => false,
                'message' => 'Consultation tracking unavailable.',
            ], 404);
        }

        $item = ConsultationRequest::where('reference_no', $ref)->first();

        if (! $item) {
            return response()->json([
                'success' => false,
                'message' => "No consultation found for reference #{$ref}.",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $item->id,
                'reference_no' => $item->reference_no,
                'patient_name' => $item->name,
                'session_type' => $item->session_type === 'follow_up' ? 'Follow-up Session' : 'Initial Consultation',
                'concern' => $item->concern,
                'status' => $item->status,
                'admin_approved' => (bool) $item->admin_approved,
                'scheduled_at' => $item->scheduled_at ? $item->scheduled_at->format('d M Y, h:i A') : null,
                'meeting_link' => $item->zoom_link,
                'assigned_doctor' => $item->assigned_to ?: 'Lifestyle Medicine Specialist',
                'doctor_notes' => $item->doctor_notes,
            ],
        ]);
    }
}
