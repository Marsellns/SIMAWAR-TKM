<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\DashboardViewerRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function showRegistrationForm(): View
    {
        return view('auth.register', [
            'roles' => $this->roleOptions(),
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
            'requested_role' => ['required', Rule::in(array_keys($this->roleOptions()))],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'account_status' => 'pending',
            'requested_role' => $validated['requested_role'],
        ]);

        return redirect()->route('login')->with('status', 'Permintaan akses telah dikirim dan menunggu persetujuan admin.');
    }

    private function roleOptions(): array
    {
        return [
            'viewer' => 'Viewer (perlu persetujuan)',
            'admin' => 'Admin (akses penuh, perlu persetujuan)',
            'manager_nop' => 'Manager NOP (approval Presales)',
            'manager_sq' => 'Manager SQ (approval Presales)',
            'manager_nos' => 'Manager NOS (approval Presales)',
            'manager_nbae' => 'Manager NBAE (approval Presales)',
            ...DashboardViewerRole::options(),
        ];
    }
}
