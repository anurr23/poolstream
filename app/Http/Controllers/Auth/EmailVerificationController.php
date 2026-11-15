<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\EmailOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class EmailVerificationController extends Controller
{
    public function notice(Request $request)
    {
        $user = $request->user();

        if (! $user->isCustomer() || $user->hasVerifiedEmail()) {
            return redirect()->route('customer.booking.index');
        }

        return Inertia::render('Auth/VerifyOtp', [
            'email' => $user->email,
            'mailLogMode' => config('mail.default') === 'log',
        ]);
    }

    public function verify(Request $request, EmailOtpService $otp)
    {
        $user = $request->user();

        if (! $user->isCustomer() || $user->hasVerifiedEmail()) {
            return redirect()->route('customer.booking.index');
        }

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $status = $otp->verify($user, $validated['code']);

        if ($status === 'verified') {
            return redirect()->route('customer.booking.index')
                ->with('success', 'Email berhasil diverifikasi. Selamat datang!');
        }

        $message = match ($status) {
            'expired' => 'Kode OTP sudah kedaluwarsa. Kirim ulang kode baru.',
            'locked' => 'Terlalu banyak percobaan. Kirim ulang kode baru.',
            default => 'Kode OTP tidak valid.',
        };

        throw ValidationException::withMessages([
            'code' => $message,
        ]);
    }

    public function resend(Request $request, EmailOtpService $otp)
    {
        $user = $request->user();

        if (! $user->isCustomer() || $user->hasVerifiedEmail()) {
            return redirect()->route('customer.booking.index');
        }

        $key = 'otp-resend:'.$user->id;

        if (Cache::has($key)) {
            return back()->with('error', 'Tunggu 60 detik sebelum mengirim ulang kode.');
        }

        Cache::put($key, true, 60);

        try {
            $otp->send($user);
        } catch (\Throwable $exception) {
            report($exception);
            Cache::forget($key);

            return back()->with('error', 'Gagal mengirim email OTP. Coba lagi nanti.');
        }

        return back()->with('success', 'Kode OTP baru sudah dikirim ke email Anda.');
    }
}
