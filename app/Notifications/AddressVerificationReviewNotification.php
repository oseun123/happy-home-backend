<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AddressVerificationReviewNotification extends Notification
{
    use Queueable;

    protected $record;
    protected $payload;

    public function __construct($record, array $payload)
    {
        $this->record = $record;
        $this->payload = $payload;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $user = $this->record->user;
        $firstName = $user->personalProfile->first_name ?? '';
        $lastName = $user->personalProfile->last_name ?? '';
        $userName = trim($firstName . ' ' . $lastName) ?: 'N/A';
        $userEmail = $user->email ?? 'N/A';

        $referenceId = $this->payload['reference_id'] ?? 'N/A';
        $message = $this->payload['message'] ?? 'N/A';
        $verificationUrl = $this->payload['verification_url'] ?? null;

        // Extract address details
        $addressData = $this->payload['data']['address']['data']['location'] ?? [];
        $addressName = $addressData['address_location']['name'] ?? 'N/A';

        // Utility bill info if present
        $utilityBill = $addressData['utility_bill'] ?? null;
        $utilityAddress = $utilityBill['address'] ?? null;

        $mailMessage = (new MailMessage)
            ->subject('Address Verification Needs Review - ' . $userName)
            ->greeting('Hello Admin,')
            ->line('An address verification has come back with a **Pending** status and requires your manual review.')
            ->line('---')
            ->line('**User:** ' . $userName)
            ->line('**Email:** ' . $userEmail)
            ->line('**Reference ID:** ' . $referenceId)
            ->line('**Reason:** ' . $message)
            ->line('**Address:** ' . $addressName);

        if ($utilityAddress) {
            $mailMessage->line('**Utility Bill Address:** ' . $utilityAddress);
        }

        if ($verificationUrl) {
            $mailMessage->action('Review on Dojah', $verificationUrl);
        }

        $mailMessage->line('Please review this verification and take appropriate action.');

        return $mailMessage;
    }
}
