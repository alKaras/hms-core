<?php

namespace App\Notifications;

use App\Http\Resources\TimeSlotsResource;
use App\Models\TimeSlots;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TimeSlotConfirmationNotification extends Notification
{
    use Queueable;

    protected $timeslots;

    /**
     * Create a new notification instance.
     */
    public function __construct($timeSlots)
    {
        $this->timeslots = $timeSlots;
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
        $mailMessage = (new MailMessage)
            ->subject('Ваше замовлення підтверджено')
            ->greeting("Привіт, {$notifiable->name}!")
            ->line('Дякуємо, що скористалися нашим сервісом для отримання медичних послуг! ')
            ->line('Ваш платіж успішно проведено. Талони послуг у вкладенні.')
            ->line('Ми вдячні вам за вибір нашої платформи. Якщо у вас виникнуть запитання або потреба в додатковій інформації, будь ласка, не соромтеся звертатися до нашої служби підтримки.')
            ->line('')
            ->line('Бережіть своє здоров\'я та до нових зустрічей!')
            ->salutation('З повагою, команда HMS');

        foreach ($this->timeslots as $slot) {
            if ($slot instanceof TimeSlots) {
                $details = (new TimeSlotsResource($slot))->toArray(request());
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.specific_timeslot', compact('details'))->output();

                $mailMessage->attachData($pdf, 'timeslot-' . $details['id'] . '.pdf', [
                    'mime' => 'application/pdf',
                ]);
            }

        }
        return $mailMessage;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'time_slots' => $this->timeslots
        ];
    }
}
