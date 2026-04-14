<?php

namespace App\Notifications;

use App\Models\Workshop;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkshopReminderNotification extends Notification
{
    public function __construct(public Workshop $workshop) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tz = config('app.timezone');
        $startsLocal = $this->workshop->starts_at->copy()->timezone($tz)->format('l j F Y, H:i');

        return (new MailMessage)
            ->subject("Workshop reminder: {$this->workshop->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line("This is a reminder that you are registered for **{$this->workshop->title}**, which takes place on:")
            ->line("**{$startsLocal} ({$tz})**")
            ->line('Thank you for using '.config('app.name').'.');
    }
}
