<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\DoctorProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class DoctorAuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check() && session('doctor_logged_in') && Auth::user()->isDoctor()) {
            return redirect()->route('doctor.appointments');
        }

        return view('doctor.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $remember = $request->has('remember') ? $request->boolean('remember') : true;

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            if (! $user->isDoctor()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Access Restricted: This account does not have Doctor/Consultant privileges. Please sign in with Doctor credentials.',
                ])->withInput($request->only('email'));
            }

            $request->session()->regenerate();
            $request->session()->put('doctor_logged_in', true);
            $request->session()->put('doctor_user_id', $user->id);
            $request->session()->forget('url.intended');

            return redirect()->route('doctor.appointments')->with('success', 'Welcome, Dr. ' . $user->name . '! Logged in to Doctor Workspace.');
        }

        return back()->withErrors([
            'email' => 'Invalid doctor credentials provided. Please check your email and password.',
        ])->withInput($request->only('email'));
    }

    /**
     * 1-Click Instant Demo Login for testing doctor portal
     */
    public function demoLogin(Request $request)
    {
        $doctor = User::where('email', 'drvarshas@yahoo.com')->first();
        if (! $doctor) {
            $doctorRole = Role::firstOrCreate(
                ['name' => 'Doctor'],
                ['name' => 'Doctor', 'guard_name' => 'web']
            );

            $doctor = User::create([
                'name'     => 'Dr. Varsha Shah',
                'email'    => 'drvarshas@yahoo.com',
                'phone'    => '9274683460',
                'role_id'  => $doctorRole->id,
                'status'   => 'active',
                'password' => Hash::make('Prakruti@123'),
            ]);
        }

        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        Auth::login($doctor);
        $request->session()->regenerate();
        $request->session()->put('doctor_logged_in', true);
        $request->session()->put('doctor_user_id', $doctor->id);
        $request->session()->forget('url.intended');

        return redirect()->route('doctor.appointments')->with('success', 'Logged in as Dr. ' . $doctor->name . '. Welcome to your Doctor Workspace.');
    }

    public function showRegisterForm()
    {
        if (Auth::check() && session('doctor_logged_in') && Auth::user()->isDoctor()) {
            return redirect()->route('doctor.appointments');
        }

        return view('doctor.auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|max:255|unique:users,email',
            'phone'          => 'required|string|max:30',
            'qualification'  => 'nullable|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'password'       => 'required|string|min:8|confirmed',
        ]);

        // Find or create Doctor role
        $doctorRole = Role::firstOrCreate(
            ['name' => 'Doctor'],
            ['name' => 'Doctor', 'guard_name' => 'web']
        );

        // Create the doctor user with dedicated doctor role
        $user = User::create([
            'name'      => $validated['name'],
            'email'     => strtolower(trim($validated['email'])),
            'phone'     => trim($validated['phone']),
            'role_id'   => $doctorRole->id,
            'status'    => 'active',
            'password'  => Hash::make($validated['password']),
        ]);

        // Create / update public DoctorProfile entry
        DoctorProfile::updateOrCreate(
            ['email' => strtolower(trim($validated['email']))],
            [
                'name'           => $validated['name'],
                'phone'          => trim($validated['phone']),
                'qualification'  => $validated['qualification'] ?: 'MBBS / Healthcare Specialist',
                'specialization' => $validated['specialization'] ?: 'Lifestyle Medicine',
                'bio'            => 'Practicing Lifestyle Medicine & Holistic Wellness consultant at Prakruti.',
                'experience'     => '5+ Yrs Exp',
                'consultation_fee' => 1500.00,
                'is_active'      => true,
            ]
        );

        // Authenticate the new doctor
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('doctor_logged_in', true);
        $request->session()->put('doctor_user_id', $user->id);
        $request->session()->forget('url.intended');

        return redirect()->route('doctor.appointments')->with('success', 'Doctor account registered successfully! Welcome to your Doctor Workspace, Dr. ' . $user->name . '.');
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['doctor_logged_in', 'doctor_user_id']);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('doctor.login')->with('success', 'Logged out from Doctor Panel successfully.');
    }
}
