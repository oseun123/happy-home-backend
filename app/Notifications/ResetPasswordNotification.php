<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public $resetToken;

    public function __construct($resetToken)
    {
        $this->resetToken = $resetToken;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        // $resetUrl = env('FRONTEND_URL') . '/reset-password' . '?token=' . $this->resetToken . '&email=' . urlencode($notifiable->email);
        $resetUrl = env('FRONTEND_URL') . '/reset-password' . '?token=' . $this->resetToken . '&email=' . $notifiable->email;

        return (new MailMessage)
            ->subject('Reset Your Password')
            ->line('You requested a password reset. Click the button below to reset your password.')
            ->action('Reset Password', $resetUrl)
            ->line('This link will expire in 1 hour. If you did not request a password reset, no action is required.');
    }
}
