<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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

        return Socialite::driver('google')->stateless()->redirect();
    }

    public function callback(Request $request)
    {
        if (! $this->configured()) {
            return $this->popupResponse('error', route('login'), 'Login dengan Google belum dikonfigurasi.');
        }

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable $exception) {
            report($exception);

            return $this->popupResponse('error', route('login'), 'Gagal masuk dengan Google. Silakan coba lagi.');
        }

        $email = $googleUser->getEmail();

        if (! $email) {
            return $this->popupResponse('error', route('login'), 'Akun Google tidak memiliki alamat email.');
        }

        $email = Str::lower($email);

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $email)
            ->first();

        if ($user) {
            if (! $user->isCustomer()) {
                return $this->popupResponse('error', route('login'), 'Email ini terdaftar sebagai akun staff. Silakan login dengan username.');
            }

            $user->google_id = $googleUser->getId();

            if (! $user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
            } else {
                $user->save();
            }

            if (! $user->photo_path || str_starts_with((string) $user->photo_path, 'user_photos/google_avatar_')) {
                $this->syncAvatar($user, $googleUser->getAvatar());
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

            $this->syncAvatar($user, $googleUser->getAvatar());
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return $this->popupResponse('success', route('customer.home'), 'Berhasil masuk dengan Google.');
    }

    private function popupResponse(string $status, string $redirectUrl, string $message)
    {
        $cleanUrl = preg_replace('#/public(/|$)#', '$1', $redirectUrl);

        return response()->view('auth.google-callback', [
            'status' => $status,
            'redirectUrl' => $cleanUrl,
            'message' => $message,
        ]);
    }

    private function syncAvatar(User $user, ?string $avatarUrl): void
    {
        if (! $avatarUrl) {
            return;
        }

        try {
            $response = Http::timeout(5)->get($avatarUrl);
            if ($response->successful() && $response->body()) {
                $filename = 'user_photos/google_avatar_'.$user->id.'.jpg';
                Storage::disk('public')->put($filename, $response->body());
                $user->forceFill(['photo_path' => $filename])->save();

                return;
            }
        } catch (\Throwable $e) {
            report($e);
        }

        if (strlen($avatarUrl) <= 255) {
            $user->forceFill(['photo_path' => $avatarUrl])->save();
        }
    }

    private function configured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }
}
