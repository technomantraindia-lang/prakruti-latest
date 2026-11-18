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
            return redirect()->route('doctor.login');
        }

        $user = Auth::user();
        if (! $user->isDoctor() && ! $user->isAdmin()) {
            return redirect()->route('doctor.login')->withErrors([
                'email' => 'Access Restricted: You must log in with a registered Doctor or Admin account to access the Doctor Workspace.',
            ]);
        }

        return $next($request);
    }
}
