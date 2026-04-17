<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect('/dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            // Check if account is approved
            if ($user->account_status !== 'approved') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $statusMsg = $user->account_status === 'pending'
                    ? 'Akun Anda masih menunggu persetujuan dari Manager.'
                    : 'Akun Anda ditolak. Silakan hubungi Administrator.';

                return back()->withErrors(['email' => $statusMsg])->onlyInput('email');
            }

            $request->session()->regenerate();

            // Redirect based on role
            if ($user->isStaff()) {
                return redirect()->route('transactions.index')->with('success', 'Selamat datang, ' . $user->name . '!');
            }

            return redirect()->intended('/dashboard')->with('success', 'Selamat datang, ' . $user->name . '!');
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login')->with('success', 'Berhasil logout.');
    }
}
