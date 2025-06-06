<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class BlockStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    protected $blocker;
    protected $isBlocked;

    public function __construct($blocker, $isBlocked)
    {
        $this->blocker = $blocker;
        $this->isBlocked = $isBlocked;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $firstName = $this->blocker->personalProfile->first_name ?? '';
        $lastName = $this->blocker->personalProfile->last_name ?? '';
        $blockerName = trim("{$firstName} {$lastName}");

        $message = $this->isBlocked
            ? "{$blockerName} has chosen to pause interactions with you for now."
            : "{$blockerName} has lifted the block. You can now interact again.";

        return (new MailMessage)
            ->subject('Update on Your Connection')
            ->greeting('Hello!')
            ->line($message)
            ->line('Thanks for understanding and being part of our community.');
    }

    public function toArray($notifiable)
    {
        $firstName = $this->blocker->personalProfile->first_name ?? '';
        $lastName = $this->blocker->personalProfile->last_name ?? '';
        $blockerName = trim("{$firstName} {$lastName}");

        return [
            'message' => $this->isBlocked
                ? "You've been blocked by {$blockerName}"
                : "{$blockerName} has unblocked you",
            'type' => 'block_status_changed',
            'blocker_id' => $this->blocker->id,
            'is_blocked' => $this->isBlocked,
            'action_url' => null, // No action needed for block status changes
        ];
    }
}
