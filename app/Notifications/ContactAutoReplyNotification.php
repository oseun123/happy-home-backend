<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ContactAutoReplyNotification extends Notification
{
    public $contact;

    public function __construct($contact)
    {
        $this->contact = $contact;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Thanks for contacting us')
            ->greeting("Hello {$this->contact->first_name},")
            ->line('Thank you for reaching out to us.')
            ->line('We have received your message and will get back to you shortly.')
            ->line('Best regards,')
            ->line(config('app.name'));
    }
}
