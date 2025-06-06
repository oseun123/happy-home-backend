<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SuperAdminNotification extends Notification
{
    use Queueable;

    protected $subject;
    protected $message;
    protected $actionText;
    protected $actionUrl;

    public function __construct($subject, $message, $actionText = null, $actionUrl = null)
    {
        $this->subject = $subject;
        $this->message = $message;
        $this->actionText = $actionText;
        $this->actionUrl = $actionUrl;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $mailMessage = (new MailMessage)
            ->subject($this->subject)
            ->greeting('Hello, Super Admin')
            ->line($this->message);

        if ($this->actionText && $this->actionUrl) {
            $mailMessage->action($this->actionText, $this->actionUrl);
        }

        return $mailMessage;
    }
}
