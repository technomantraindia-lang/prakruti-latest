<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SettingController extends Controller
{
    public function index()
    {
        return view('admin.settings.index');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'admin_current_password' => ['required', 'string'],
            'admin_new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $admin = $request->user();

        if (! $admin || ! Hash::check((string) $data['admin_current_password'], (string) $admin->password)) {
            return back()
                ->withInput()
                ->withErrors(['admin_current_password' => 'Current password is incorrect.']);
        }

        $admin->forceFill(['password' => Hash::make($data['admin_new_password'])])->save();

        return redirect()->route('admin.settings.index')->with('success', 'Password updated successfully.');
    }
}
