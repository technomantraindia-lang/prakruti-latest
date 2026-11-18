<?php

namespace App\Http\Controllers\Consultant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConsultantAuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check() && Auth::user()->isConsultant()) {
            return redirect()->route('consultant.appointments');
        }

        return view('consultant.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            if (! $user->isConsultant()) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'This account does not have access to the Lifestyle Medicine Consultant Portal.',
                ])->withInput($request->only('email'));
            }

            $request->session()->regenerate();
            return redirect()->intended(route('consultant.appointments'))->with('success', 'Welcome, Dr. ' . $user->name . '! Logged in successfully.');
        }

        return back()->withErrors([
            'email' => 'Invalid email or password provided.',
        ])->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('consultant.login')->with('success', 'Logged out from Consultant Portal successfully.');
    }
}
