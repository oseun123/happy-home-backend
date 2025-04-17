<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NudgeReminder extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($subscriber)
    {
        $this->subscriber = $subscriber;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
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
            ->subject('Someone’s waiting on you!')
            ->greeting("Hey {$nameMe},")
            ->line("{$name} just nudged you – they’re hoping you'll subscribe back so you both can fully connect.")
            ->line("This is a little reminder that they're interested and would love to hear from you.")
            ->action('Check Out Their Profile', $url)
            ->line('Let’s keep the connection going – you never know where it might lead.')
            ->line('Thanks for being part of our community!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
