<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect('/admin/login');
        }

        if (! auth()->user()->isAdmin()) {
            if (auth()->user()->isConsultant()) {
                return redirect()->route('doctor.appointments')->with('error', 'Access Denied: Doctor accounts cannot access the Admin panel.');
            }
            abort(403, 'Unauthorized access to admin panel.');
        }

        return $next($request);
    }
}
