<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        if (! $this->configured()) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Login dengan Google belum dikonfigurasi.']);
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request)
    {
        if (! $this->configured()) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Login dengan Google belum dikonfigurasi.']);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->route('login')
                ->withErrors(['email' => 'Gagal masuk dengan Google. Silakan coba lagi.']);
        }

        $email = $googleUser->getEmail();

        if (! $email) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Akun Google tidak memiliki alamat email.']);
        }

        $email = Str::lower($email);

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $email)
            ->first();

        if ($user) {
            if (! $user->isCustomer()) {
                return redirect()->route('login')
                    ->withErrors(['email' => 'Email ini terdaftar sebagai akun staff. Silakan login dengan username.']);
            }

            $user->google_id = $googleUser->getId();

            if (! $user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
            } else {
                $user->save();
            }
        } else {
            $user = User::create([
                'name' => $googleUser->getName() ?: Str::before($email, '@'),
                'username' => User::makeUsernameFromEmail($email),
                'email' => $email,
                'email_verified_at' => now(),
                'google_id' => $googleUser->getId(),
                'password' => Str::random(40),
                'role' => 'customer',
                'is_active' => true,
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->route('customer.booking.index')
            ->with('success', 'Berhasil masuk dengan Google.');
    }

    private function configured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }
}
