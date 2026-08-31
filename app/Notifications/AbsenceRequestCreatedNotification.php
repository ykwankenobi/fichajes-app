<?php

namespace App\Notifications;

use App\Models\AbsenceRequest;
use App\Models\CompanySetting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AbsenceRequestCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public AbsenceRequest $absenceRequest
    ) {
        $this->absenceRequest->loadMissing('user');
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $type = match ($this->absenceRequest->type) {
            'vacation' => 'Vacaciones',
            'sick_leave', 'medical_leave' => 'Baja médica',
            'leave_of_absence' => 'Excedencia',
            'personal', 'personal_leave' => 'Asunto personal',
            'other' => 'Otro',
            default => $this->absenceRequest->type,
        };

        $employeeName = $this->absenceRequest->user?->name ?? 'Empleado';
        $startsAt = $this->absenceRequest->starts_at?->format('d/m/Y');
        $endsAt = $this->absenceRequest->ends_at?->format('d/m/Y');
        $reason = $this->absenceRequest->reason ?: 'Sin motivo indicado';

        $company = CompanySetting::current();
        $message = (new MailMessage)
            ->from($company->mailFromAddress(), $company->mailFromName())
            ->subject($company->absence_request_subject ?: 'Nueva solicitud de ausencia')
            ->greeting('Nueva solicitud de ausencia')
            ->line("El empleado {$employeeName} ha enviado una nueva solicitud.")
            ->line("Tipo: {$type}")
            ->line("Desde: {$startsAt}")
            ->line("Hasta: {$endsAt}")
            ->line("Motivo: {$reason}")
            ->action('Revisar solicitud', url('/admin/absence-requests/' . $this->absenceRequest->id . '/edit'))
            ->line('Puedes aprobarla o rechazarla desde el panel de administración.');

        if ($replyTo = $company->mailReplyTo()) {
            $message->replyTo($replyTo, $company->mailFromName());
        }

        return $message;
    }
}
