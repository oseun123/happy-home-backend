<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\AddressVerification;

class AddressVerificationFailedNotification extends Notification
{
    use Queueable;

    protected $record;
    protected $payload;

    public function __construct(AddressVerification $record, array $payload)
    {
        $this->record = $record;
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

        $message = $this->payload['message'] ?? 'Address verification failed.';
        $retriesLeft = max(0, $this->record->retry_limit - $this->record->retry_count);
        $url = config('app.frontend_url', 'http://localhost:3000') . '/transactions';

        $mailMessage = (new MailMessage)
            ->subject('Address Verification Failed')
            ->greeting('Hello ' . $name . ',')
            ->line('Unfortunately, your address verification has failed.')
            ->line('**Reason:** ' . $message);

        if ($retriesLeft > 0) {
            $mailMessage->line('You can reverify without paying on your transaction page. You have **' . $retriesLeft . '** ' . ($retriesLeft === 1 ? 'attempt' : 'attempts') . ' left.')
                ->action('Go to Transaction Page', $url);
        } else {
            $mailMessage->line('You have exceeded the retry limit for this verification. You will need to make a new payment to initialize address verification.');
        }

        $mailMessage->line('Thank you for being part of our community!');

        return $mailMessage;
    }

    public function toArray($notifiable)
    {
        $retriesLeft = max(0, $this->record->retry_limit - $this->record->retry_count);
        $message = $this->payload['message'] ?? 'Address verification failed.';
        return [
            'title' => 'Address Verification Failed',
            'message' => 'Your address verification failed. Reason: ' . $message . '. You have ' . $retriesLeft . ' retries left.',
            'type' => 'address_verification_failed',
        ];
    }
}
