<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DoctorAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('doctor.login')->withErrors([
                'email' => 'Please log in with your doctor credentials to access the Doctor Workspace.',
            ]);
        }

        $user = Auth::user();

        // Strict security: User must have explicitly logged in to the Doctor Portal
        if (! $request->session()->get('doctor_logged_in')) {
            return redirect()->route('doctor.login')->withErrors([
                'email' => 'Please sign in to the Doctor Workspace with your registered Doctor account.',
            ]);
        }

        if (! $user->isDoctor()) {
            return redirect()->route('doctor.login')->withErrors([
                'email' => 'Access Restricted: You must log in with a registered Doctor account to access the Doctor Workspace.',
            ]);
        }

        return $next($request);
    }
}
