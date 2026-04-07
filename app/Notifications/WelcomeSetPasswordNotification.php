<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class WelcomeSetPasswordNotification extends Notification
{
    protected string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = route('set-password.show', ['token' => $this->token])
            . '?email=' . urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Welcome to Medisource HRIS – Set Your Password')
            ->greeting('Welcome to Medisource HRIS!')
            ->line('Your account has been created. Click the button below to verify your email and set your password.')
            ->action('Set Your Password', $url)
            ->line('This link will expire in 60 minutes.')
            ->line('If you did not expect this email, no further action is required.');
    }
}
