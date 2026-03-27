<?php

namespace App\Http\Controllers\Inertia;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AuthPageController extends Controller
{
    public function loginPage() { return Inertia::render('LoginPage'); }
    public function registerPage() { return Inertia::render('RegisterPage'); }
    public function forgotPasswordPage() { return Inertia::render('ForgotPasswordPage'); }
    public function resetPasswordPage() { return Inertia::render('ResetPasswordPage'); }

    public function login(Request $request)
    {
        $request->validate(['email' => 'required|email', 'password' => 'required']);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            if ($user) $user->increment('failed_login_attempts');
            return back()->withErrors(['email' => 'Email atau password salah.']);
        }

        if ($user->status === 'BLOCKED') return back()->withErrors(['email' => 'Akun Anda diblokir. Silakan hubungi Customer Service.']);
        if ($user->status === 'PENDING_VERIFICATION') return back()->withErrors(['email' => 'Akun Anda belum aktif. Silakan cek email Anda untuk verifikasi OTP.']);
        if ($user->status !== 'ACTIVE') return back()->withErrors(['email' => 'Akun Anda tidak aktif. Hubungi Customer Service.']);

        $user->update(['failed_login_attempts' => 0]);
        Auth::login($user);
        $request->session()->regenerate();

        return $user->role_id === 9
            ? redirect()->intended('/dashboard')
            : redirect()->intended('/admin/dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    public function landing()
    {
        $config = DB::table('system_configurations')->whereIn('config_key', ['APP_DOWNLOAD_LINK_IOS', 'APP_DOWNLOAD_LINK_ANDROID'])->pluck('config_value', 'config_key');
        return Inertia::render('LandingPage', [
            'appLinks' => ['ios' => $config['APP_DOWNLOAD_LINK_IOS'] ?? '#', 'android' => $config['APP_DOWNLOAD_LINK_ANDROID'] ?? '#'],
        ]);
    }
}
