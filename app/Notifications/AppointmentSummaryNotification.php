<?php

namespace App\Notifications;

use App\Http\Resources\MedAppointmentResource;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use App\Models\MedAppointments;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AppointmentSummaryNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected $appointment)
    {
        //
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
        $details = (new MedAppointmentResource($this->appointment))->toArray(request());
        $mailMessage = (new MailMessage)
            ->subject('Ваші результати готові')
            ->greeting("Вітаю, {$notifiable->name}")
            ->line("Ми надаємо вам результати вашого обстеження {$details['service']['name']}, яке було проведено " . Carbon::parse($details['service']['start_time'])->format('d.m.Y H:i') . " у {$details['hospital']['title']}")
            ->line('До цього листа додається файл із повним висновком спеціаліста, який містить детальну інформацію щодо результатів обстеження. Рекомендуємо ознайомитися з висновком та за необхідності звернутися до вашого лікаря для додаткового пояснення або подальших рекомендацій.')
            ->line("Якщо у вас виникнуть будь-які запитання або вам потрібна консультація, будь ласка, зв'яжіться з нами за номером {$details['hospital']['phone']} або електронною поштою {$details['hospital']['email']}.")
            ->line("Дякуємо за вашу довіру. Бажаємо вам міцного здоров'я!")
            ->salutation('З повагою, команда HMS');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.appointment_summary', compact('details'))->output();

        $mailMessage->attachData($pdf, 'appointment-' . $details['id'] . '.pdf', [
            'mime' => 'application/pdf'
        ]);

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
            //
        ];
    }
}
