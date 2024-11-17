<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegisteredUserCredentials extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected $email, protected $password)
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
            ->subject('Вас зареєстровано в системі')
            ->greeting('Привіт, ' . $notifiable->name)
            ->line('Вас зареєстровано в централізованій системі HMS')
            ->line('Ваші дані для входу:')
            ->line('Пошта: ' . $this->email)
            ->line('Пароль:' . $this->password)
            ->line('Будь ласка не повідомляйте цю інформацію нікому. Якщо вважаєте, що реєстрація помилкова - зверніться на пошту hms-admin@gmail.com')
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
