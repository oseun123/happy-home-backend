<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\User;

class ReminderToReciprocateNotification extends Notification
{
    use Queueable;

    protected $subscriber;

    public function __construct(User $subscriber)
    {
        $this->subscriber = $subscriber;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {


        $firstName = $this->subscriber->personalProfile->first_name ?? '';
        $lastName = $this->subscriber->personalProfile->last_name ?? '';
        $name = trim($firstName . ' ' . $lastName);

        $firstNameMe = $notifiable->personalProfile->first_name ?? '';
        $lastNameMe = $notifiable->personalProfile->last_name ?? '';
        $nameMe = trim($firstNameMe . ' ' . $lastNameMe);
        $url = env('FRONTEND_URL');
        return (new MailMessage)
            ->subject('Someone Subscribed to You – Don’t Miss Out!')
            ->greeting("Hey {$nameMe},")
            ->line("{$name} recently subscribed to you on our platform.")
            ->line("To complete the connection and unlock mutual features, you need to subscribe back within the next 24 hours.")
            ->line("If you don’t respond, the subscription will be canceled and {$name} will get a chance to subscribe to someone else.")
            ->action('Subscribe Back', $url)
            ->line('Thanks for being part of our community!');
    }
}
