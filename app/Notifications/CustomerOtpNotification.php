<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerOtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $code,
        public int $expiresInMinutes,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kode OTP PoolStream')
            ->greeting('Halo '.$notifiable->name.'!')
            ->line('Kode OTP untuk verifikasi email Anda adalah:')
            ->line($this->code)
            ->line('Kode ini berlaku selama '.$this->expiresInMinutes.' menit.')
            ->line('Jika Anda tidak merasa mendaftar di PoolStream, abaikan email ini.')
            ->salutation('Salam, PoolStream');
    }
}
