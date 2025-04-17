<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\User;

class FreeRetryAvailableNotification extends Notification
{
    use Queueable;

    protected $subscribedTo;

    public function __construct(User $subscribedTo)
    {
        $this->subscribedTo = $subscribedTo;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $firstName = $this->subscribedTo->personalProfile->first_name ?? '';
        $lastName = $this->subscribedTo->personalProfile->last_name ?? '';
        $name = trim($firstName . ' ' . $lastName);

        $firstNameMe = $notifiable->personalProfile->first_name ?? '';
        $lastNameMe = $notifiable->personalProfile->last_name ?? '';
        $nameMe = trim($firstNameMe . ' ' . $lastNameMe);
        $url = env('FRONTEND_URL');

        return (new MailMessage)
            ->subject('Free Subscription Retry Unlocked!')
            ->greeting("Hey {$nameMe},")
            ->line("Unfortunately, {$name} didn’t subscribe back within the allowed time.")
            ->line("But don’t worry – you’ve been granted a free retry to subscribe to someone else!")
            ->line("Take advantage of this opportunity and find a better match.")
            ->action('Use Free Retry', $url)
            ->line('Good luck, and happy matching!');
    }
}
