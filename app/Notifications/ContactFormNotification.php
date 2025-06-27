<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ContactFormNotification extends Notification
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
            ->subject('New Contact Form Submission')
            ->line('New message from contact form:')
            ->line("Name: {$this->contact->first_name} {$this->contact->last_name}")
            ->line("Email: {$this->contact->email}")
            ->line("Message: {$this->contact->message}");
    }
}
