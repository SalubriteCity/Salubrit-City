<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailOtp extends Notification
{
    use Queueable;

    public function __construct(public readonly string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Salubrit-City verification code')
            ->greeting('Verify your email address')
            ->line("Your verification code is {$this->code}.")
            ->line('This code expires in '.config('authentication.email_otp_ttl').' minutes.')
            ->line('If you did not create an account, you can ignore this message.');
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
