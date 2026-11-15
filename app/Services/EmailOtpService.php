<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\CustomerOtpNotification;
use Illuminate\Support\Facades\Hash;

class EmailOtpService
{
    public const EXPIRES_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    public function send(User $user): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->forceFill([
            'otp_code' => Hash::make($code),
            'otp_expires_at' => now()->addMinutes(self::EXPIRES_MINUTES),
            'otp_attempts' => 0,
        ])->save();

        $user->notify(new CustomerOtpNotification($code, self::EXPIRES_MINUTES));
    }

    /**
     * @return string verified|invalid|expired|locked
     */
    public function verify(User $user, string $code): string
    {
        if (! $user->otp_code || ! $user->otp_expires_at) {
            return 'invalid';
        }

        if ($user->otp_expires_at->isPast()) {
            return 'expired';
        }

        if ($user->otp_attempts >= self::MAX_ATTEMPTS) {
            return 'locked';
        }

        if (! Hash::check($code, $user->otp_code)) {
            $user->increment('otp_attempts');

            return 'invalid';
        }

        $user->markEmailAsVerified();

        return 'verified';
    }
}
