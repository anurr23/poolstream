<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class RegisteredUserController extends Controller
{
    public function create()
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request, EmailOtpService $otp)
    {
        $request->merge([
            'email' => Str::lower((string) $request->input('email')),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => User::makeUsernameFromEmail($validated['email']),
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'customer',
            'is_active' => true,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        try {
            $otp->send($user);
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->route('customer.verification.notice')
                ->with('error', 'Akun dibuat, tetapi email OTP gagal dikirim. Silakan kirim ulang kode.');
        }

        return redirect()->route('customer.verification.notice')
            ->with('success', 'Akun berhasil dibuat. Cek email untuk kode OTP.');
    }
}
