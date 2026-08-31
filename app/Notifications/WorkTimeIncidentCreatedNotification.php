<?php

namespace App\Notifications;

use App\Models\WorkTimeRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkTimeIncidentCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public WorkTimeRecord $workTimeRecord
    ) {
        $this->workTimeRecord->loadMissing('user');
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $employeeName = $this->workTimeRecord->user?->name ?? 'Empleado';
        $startedAt = $this->workTimeRecord->started_at?->format('d/m/Y H:i');
        $endedAt = $this->workTimeRecord->ended_at?->format('d/m/Y H:i') ?? 'Sin finalizar';
        $unjustifiedMinutes = (int) $this->workTimeRecord->unjustified_exit_minutes;

        $reason = match (true) {
            $this->workTimeRecord->closed_automatically => 'Cierre automático de fichaje',
            $this->workTimeRecord->record_type === WorkTimeRecord::TYPE_UNJUSTIFIED_EXIT => 'Salida no justificada',
            $this->workTimeRecord->end_type === WorkTimeRecord::TYPE_UNJUSTIFIED_EXIT => 'Salida no justificada',
            default => 'Incidencia de fichaje',
        };

        return (new MailMessage)
            ->subject('Nueva incidencia de fichaje')
            ->greeting('Nueva incidencia de fichaje')
            ->line("Se ha generado una incidencia para {$employeeName}.")
            ->line("Motivo: {$reason}")
            ->line("Inicio: {$startedAt}")
            ->line("Fin: {$endedAt}")
            ->line("Minutos no justificados: {$unjustifiedMinutes}")
            ->action('Revisar incidencia', url('/admin/work-time-incidents/' . $this->workTimeRecord->id . '/edit'))
            ->line('Puedes revisarla desde el panel de administración.');
    }
}
