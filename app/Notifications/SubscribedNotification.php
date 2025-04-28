<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class SubscribedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $subscriber;

    public function __construct($subscriber)
    {
        $this->subscriber = $subscriber;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $firstName = $this->subscriber->personalProfile->first_name ?? '';
        $lastName = $this->subscriber->personalProfile->last_name ?? '';
        $name = trim($firstName . ' ' . $lastName);
        $url = env('FRONTEND_URL');

        return (new MailMessage)
            ->subject('You Have a New Subscriber!')
            ->greeting("Hello {$notifiable->personalProfile->first_name},")
            ->line("{$name} just subscribed to you!")
            ->line('Subscribe back to complete the full connection!')
            ->action('Login', $url)
            ->line('Thank you for being part of our community!');
    }

    public function toArray($notifiable)
    {
        $firstName = $this->subscriber->personalProfile->first_name ?? '';
        $lastName = $this->subscriber->personalProfile->last_name ?? '';
        $name = trim($firstName . ' ' . $lastName);

        return [
            'message' => "New subscriber: {$name} subscribed to your profile",
            'action_url' => env('FRONTEND_URL'),
            'subscriber_id' => $this->subscriber->id,
            'type' => 'new_subscription',
        ];
    }
}
