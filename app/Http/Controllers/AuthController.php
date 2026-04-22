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
        return redirect('/')->with('openLogin', true);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $isAjax = $request->ajax() || $request->wantsJson();

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

                if ($isAjax) {
                    return response()->json(['success' => false, 'message' => $statusMsg], 422);
                }

                return back()->withErrors(['email' => $statusMsg])->onlyInput('email');
            }

            $request->session()->regenerate();

            // Determine redirect
            $redirect = $user->isStaff()
                ? route('transactions.index')
                : '/dashboard';

            if ($isAjax) {
                return response()->json(['success' => true, 'redirect' => $redirect]);
            }

            return redirect()->intended($redirect)->with('success', 'Selamat datang, ' . $user->name . '!');
        }

        if ($isAjax) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.',
            ], 422);
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
        return redirect('/')->with('success', 'Berhasil logout.');
    }
}
