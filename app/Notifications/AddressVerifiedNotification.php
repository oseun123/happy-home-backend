<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AddressVerifiedNotification extends Notification
{
    public function via($notifiable)
    {
        return ['mail', 'database']; // optional: store in-app too
    }

    public function toMail($notifiable)
    {

        $firstName = $notifiable->personalProfile->first_name ?? '';
        $lastName = $notifiable->personalProfile->last_name ?? '';
        $name = trim($firstName . ' ' . $lastName);
        return (new MailMessage)
            ->subject('Address Verification Successful')
            ->greeting('Hello ' . $name . ',')
            ->line('Your address has been successfully verified.')
            ->line('Thanks for being part of our community!');
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Address Verified',
            'message' => 'Your address was successfully verified.',
            'type' => 'address_verified',
        ];
    }
}
