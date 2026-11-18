<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebsiteVisit;
use App\Models\User;
use Illuminate\Http\Request;

class WebsiteVisitorController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date');
        $type = $request->input('type', 'all');
        $search = trim((string) $request->input('q', ''));

        $query = WebsiteVisit::with('user')->orderByDesc('last_seen_at')->orderByDesc('id');

        if ($date) {
            $query->whereDate('visit_date', $date);
        }

        if ($type === 'guest') {
            $query->whereNull('user_id');
        } elseif ($type === 'registered') {
            $query->whereNotNull('user_id');
        }

        if ($search !== '') {
            $query->where(function ($filter) use ($search) {
                $filter->where('visitor_name', 'like', "%{$search}%")
                    ->orWhere('visitor_email', 'like', "%{$search}%")
                    ->orWhere('visitor_phone', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('landing_path', 'like', "%{$search}%");
            });
        }

        $visits = $query->paginate(25)->withQueryString();
        $visits->getCollection()->each(function (WebsiteVisit $visit): void {
            $user = $visit->user;
            $hasPurchased = $user?->orders()->exists() ?? false;
            if (! $hasPurchased && $visit->display_email) {
                $hasPurchased = User::where('email', $visit->display_email)
                    ->whereHas('orders')
                    ->exists();
            }
            $visit->setAttribute('has_purchased', $hasPurchased);
        });
        $today = WebsiteVisit::whereDate('visit_date', today());

        return view('admin.visitors.index', [
            'visits' => $visits,
            'selectedDate' => $date,
            'selectedType' => $type,
            'search' => $search,
            'todayCount' => (clone $today)->count(),
            'todayGuestCount' => (clone $today)->whereNull('user_id')->count(),
            'todayRegisteredCount' => (clone $today)->whereNotNull('user_id')->count(),
            'todayPageViews' => (int) (clone $today)->sum('visit_count'),
        ]);
    }
}
