<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WebsiteVisit;
use App\Notifications\SystemAlertNotification;
use Illuminate\Http\Request;

class WebsiteVisitApiController extends Controller
{
    public function store(Request $request)
    {
        $isContactSubmission = $request->boolean('contact_submission');
        $validated = $request->validate([
            'visitor_id' => ['required', 'string', 'max:180'],
            'page' => ['nullable', 'string', 'max:500'],
            'page_title' => ['nullable', 'string', 'max:255'],
            'contact_submission' => ['sometimes', 'boolean'],
            'name' => [$isContactSubmission ? 'required' : 'nullable', 'string', 'max:255'],
            'email' => [$isContactSubmission ? 'required' : 'nullable', 'email', 'max:255'],
            'phone' => array_merge(
                ['nullable'],
                $isContactSubmission ? ['required_without:email'] : [],
                ['string', 'max:40']
            ),
            'marketing_consent' => ['sometimes', 'boolean'],
            'consent' => [$isContactSubmission ? 'accepted' : 'nullable'],
        ]);

        $user = $request->user();
        $today = now()->toDateString();
        // Store a one-way key instead of the browser identifier itself.
        $visitorKey = hash('sha256', $validated['visitor_id']);

        $visit = WebsiteVisit::firstOrNew([
            'visitor_key' => $visitorKey,
            'visit_date' => $today,
        ]);
        $isNewVisit = ! $visit->exists;
        $previousVisit = null;
        if ($isNewVisit) {
            $previousVisit = WebsiteVisit::where('visitor_key', $visitorKey)
                ->orderByDesc('visit_date')
                ->first();
            if ($previousVisit) {
                $visit->visitor_name = $previousVisit->visitor_name;
                $visit->visitor_email = $previousVisit->visitor_email;
                $visit->visitor_phone = $previousVisit->visitor_phone;
                $visit->marketing_consent = $previousVisit->marketing_consent;
                $visit->contact_submitted_at = $previousVisit->contact_submitted_at;
                $visit->follow_up_sent_at = $previousVisit->follow_up_sent_at;
                $visit->user_id = $previousVisit->user_id;
                $visit->is_authenticated = $previousVisit->is_authenticated;
            }
        }

        $now = now();
        if ($isNewVisit) {
            $visit->visit_count = 1;
            $visit->first_seen_at = $now;
        } else {
            $visit->visit_count = max(1, (int) $visit->visit_count) + 1;
        }

        $visit->fill([
            'user_id' => $user?->id,
            'visitor_name' => $user?->name ?: ($validated['name'] ?? $visit->visitor_name),
            'visitor_email' => $user?->email ?: ($validated['email'] ?? $visit->visitor_email),
            'visitor_phone' => $user?->phone ?: ($validated['phone'] ?? $visit->visitor_phone),
            'is_authenticated' => (bool) $user,
            'marketing_consent' => (bool) ($validated['marketing_consent'] ?? $visit->marketing_consent),
            'contact_submitted_at' => (! empty($validated['email']) || ! empty($validated['phone']) || $user)
                ? ($visit->contact_submitted_at ?: $now)
                : $visit->contact_submitted_at,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000) ?: null,
            'referrer' => substr((string) $request->headers->get('referer'), 0, 1000) ?: null,
            'landing_path' => $validated['page'] ?? null,
            'page_title' => $validated['page_title'] ?? null,
            'last_seen_at' => $now,
        ]);
        $visit->save();

        // Carry voluntarily shared contact details and consent across this browser's visit history.
        if ($isContactSubmission || $user) {
            WebsiteVisit::where('visitor_key', $visitorKey)->update([
                'user_id' => $user?->id,
                'visitor_name' => $visit->visitor_name,
                'visitor_email' => $visit->visitor_email,
                'visitor_phone' => $visit->visitor_phone,
                'is_authenticated' => (bool) $user,
                'marketing_consent' => (bool) $visit->marketing_consent,
                'contact_submitted_at' => $visit->contact_submitted_at,
            ]);
        }

        if ($isNewVisit) {
            $this->notifyAdmins($visit);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'visit_id' => $visit->id,
                'tracked_for_today' => true,
                'contact_saved' => (bool) ($visit->visitor_email || $visit->visitor_phone),
            ],
        ]);
    }

    private function notifyAdmins(WebsiteVisit $visit): void
    {
        $label = $visit->is_authenticated
            ? ($visit->display_name . ' visited the website.')
            : 'A guest visitor visited the website without logging in or registering.';

        User::with('role')
            ->where('status', 'active')
            ->get()
            ->filter(fn (User $user) => $user->isAdmin())
            ->each(function (User $admin) use ($visit, $label): void {
                $admin->notifyNow(new SystemAlertNotification(
                    'New website visitor',
                    $label . ' Visit details are available in Website Visitors.',
                    'info',
                    route('admin.visitors.index'),
                    [
                        'visit_id' => $visit->id,
                        'visit_date' => $visit->visit_date?->toDateString(),
                        'authenticated' => $visit->is_authenticated,
                        'visitor_name' => $visit->display_name,
                        'page' => $visit->landing_path,
                    ]
                ));
            });
    }
}
