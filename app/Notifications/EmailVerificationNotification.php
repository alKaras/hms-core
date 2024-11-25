<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected $url)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Підтвердження пошти')
            ->line("Привіт, $notifiable->name")
            ->line('Дякуємо за реєстрацію в нашій системі HMS. Для забезпечення безпеки, будь ласка, підтвердіть свою електронну пошту')
            ->action('Підтвердити', $this->url)
            ->line('Якщо ви не створили обліковий запис, ніяких подальших дій від вас не вимагається.')
            ->salutation('З повагою, команда HMS');

    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
