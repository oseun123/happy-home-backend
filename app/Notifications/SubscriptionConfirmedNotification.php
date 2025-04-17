<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class SubscriptionConfirmedNotification extends Notification
{
    use Queueable;

    public $subscribedUser;

    public function __construct($subscribedUser)
    {
        $this->subscribedUser = $subscribedUser;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {

        $firstName = $this->subscribedUser->personalProfile->first_name ?? '';
        $lastName = $this->subscribedUser->personalProfile->last_name ?? '';
        $name = trim($firstName . ' ' . $lastName);

        $firstNameMe = $notifiable->personalProfile->first_name ?? '';
        $lastNameMe = $notifiable->personalProfile->last_name ?? '';
        $nameMe = trim($firstNameMe . ' ' . $lastNameMe);
        return (new MailMessage)
            ->subject('Subscription Successful')
            ->greeting('Hello ' . $nameMe)
            ->line('You have successfully subscribed to ' . $name . '.')
            ->line('Thanks for being part of our community!');
    }
}
