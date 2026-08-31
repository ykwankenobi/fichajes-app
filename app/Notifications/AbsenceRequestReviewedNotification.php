<?php

namespace App\Notifications;

use App\Models\AbsenceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AbsenceRequestReviewedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public AbsenceRequest $absenceRequest
    ) {
        $this->absenceRequest->loadMissing('user', 'reviewer');
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

        $status = match ($this->absenceRequest->status) {
            'approved' => 'aprobada',
            'rejected' => 'rechazada',
            default => $this->absenceRequest->status,
        };

        $subject = match ($this->absenceRequest->status) {
            'approved' => 'Solicitud de ausencia aprobada',
            'rejected' => 'Solicitud de ausencia rechazada',
            default => 'Solicitud de ausencia revisada',
        };

        $startsAt = $this->absenceRequest->starts_at?->format('d/m/Y');
        $endsAt = $this->absenceRequest->ends_at?->format('d/m/Y');
        $reviewerName = $this->absenceRequest->reviewer?->name ?? 'Administración';
        $adminNotes = $this->absenceRequest->admin_notes ?: 'Sin notas adicionales';

        return (new MailMessage)
            ->subject($subject)
            ->greeting($subject)
            ->line("Tu solicitud de ausencia ha sido {$status}.")
            ->line("Tipo: {$type}")
            ->line("Desde: {$startsAt}")
            ->line("Hasta: {$endsAt}")
            ->line("Revisado por: {$reviewerName}")
            ->line("Notas de administración: {$adminNotes}")
            ->action('Ver mis ausencias', route('absence-requests.index'))
            ->line('Puedes consultar el estado desde tu panel de ausencias.');
    }
}
