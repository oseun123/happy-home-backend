<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AddressVerificationPendingNotification extends Notification
{
    use Queueable;

    protected $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $firstName = $notifiable->personalProfile->first_name ?? '';
        $lastName = $notifiable->personalProfile->last_name ?? '';
        $name = trim($firstName . ' ' . $lastName);

        $message = $this->payload['message'] ?? 'Your verification requires additional review.';

        return (new MailMessage)
            ->subject('Address Verification Under Review')
            ->greeting('Hello ' . $name . ',')
            ->line('Your address verification is currently **under review**.')
            ->line('**Reason:** ' . $message)
            ->line('Our team will review your submission and get back to you shortly. No further action is needed from you at this time.')
            ->line('Thank you for your patience!');
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Address Verification Under Review',
            'message' => 'Your address verification is pending review. We will notify you once it has been reviewed.',
            'type' => 'address_verification_pending',
        ];
    }
}
