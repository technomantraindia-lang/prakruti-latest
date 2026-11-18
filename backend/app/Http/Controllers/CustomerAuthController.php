<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CustomerAuthController extends Controller
{
    public function showRegister()
    {
        return view('customer.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'profession' => ['required', 'string', 'max:120'],
            'gender' => ['required', 'string', 'max:30'],
            'terms_accepted' => ['accepted'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'phone.required' => 'Mobile number is required.',
            'profession.required' => 'Please provide your profession.',
            'gender.required' => 'Please select your gender.',
            'terms_accepted.accepted' => 'You must agree to the Terms & Conditions, Return, Breakage & Opened Goods Policy to register.',
        ]);

        $customerRoleId = Role::where('name', 'Customer')->value('id');

        $user = new User();
        $user->name = $validated['name'];
        $user->email = strtolower(trim($validated['email']));
        $user->phone = trim($validated['phone']);
        $user->profession = trim($validated['profession']);
        $user->gender = trim($validated['gender']);
        $user->terms_accepted_at = now();
        $user->password = Hash::make($validated['password']);

        // Customer role
        $user->role_id = $customerRoleId;
        $user->status = 'active';
        $user->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('customer.account');
    }

    public function showLogin()
    {
        return view('customer.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            if ($user && $user->role && $user->role->name === 'Customer' && $user->status === 'active') {
                $request->session()->regenerate();

                return redirect()->route('customer.account');
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()
            ->back()
            ->withInput($request->only('email'))
            ->with('error', 'Invalid customer login details.');
    }

    public function account()
    {
        $user = Auth::user();

        if (! $user || ! $user->isCustomer()) {
            return redirect()->route('admin.dashboard');
        }

        return view('customer.account', compact('user'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('customer.login');
    }
}