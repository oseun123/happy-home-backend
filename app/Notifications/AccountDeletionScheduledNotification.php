<?php

// app/Notifications/AccountDeletionScheduledNotification.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AccountDeletionScheduledNotification extends Notification
{
    use Queueable;

    public function via($notifiable)
    {
        return ['mail', 'database']; // or ['mail'] if no in-app notifications
    }

    public function toMail($notifiable)
    {

        $firstName = $notifiable->personalProfile->first_name ?? '';
        $lastName = $notifiable->personalProfile->last_name ?? '';
        $name = trim($firstName . ' ' . $lastName);
        return (new MailMessage)
            ->subject('Your Account Deletion is Scheduled')
            ->greeting('Hello ' . $name . ',')
            ->line('You have requested to delete your account.')
            ->line('Your account is scheduled to be permanently deleted in 7 days.')
            ->line('If you did not request this or want to cancel the deletion, please contact support.')
            ->line('Thanks for being part of our community!')
            ->salutation('Regards, ' . config('app.name') . ' Team');
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Account Deletion Scheduled',
            'message' => 'Your account is scheduled to be deleted in 7 days.',
            'type' => 'account_deletion_scheduled',
        ];
    }
}
