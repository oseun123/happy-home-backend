<?php

// app/Notifications/AccountDeletedNotification.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AccountDeletedNotification extends Notification
{
    use Queueable;

    public function via($notifiable)
    {
        return ['mail', 'database']; // Optional: just 'mail'
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your Account Has Been Deleted')
            ->greeting('Hello,')
            ->line('Your account has now been permanently deleted from our system.')
            ->line('We’re sorry to see you go.')
            ->salutation('Best wishes, ' . config('app.name') . ' Team');
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Account Deleted',
            'message' => 'Your account has been successfully deleted.',
            'type' => 'account_deleted',
        ];
    }
}
