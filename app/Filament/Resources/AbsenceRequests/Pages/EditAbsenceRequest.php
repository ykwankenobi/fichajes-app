<?php

namespace App\Filament\Resources\AbsenceRequests\Pages;

use App\Filament\Resources\AbsenceRequests\AbsenceRequestResource;
use App\Notifications\AbsenceRequestReviewedNotification;
use Filament\Resources\Pages\EditRecord;

class EditAbsenceRequest extends EditRecord
{
    protected static string $resource = AbsenceRequestResource::class;

    protected ?string $originalStatus = null;

    public function getTitle(): string
    {
        $this->record->loadMissing('user');

        $type = match ($this->record->type) {
            'vacation' => 'Vacaciones',
            'sick_leave', 'medical_leave' => 'Baja médica',
            'leave_of_absence' => 'Excedencia',
            'personal', 'personal_leave' => 'Asunto personal',
            'other' => 'Otro',
            default => 'Ausencia',
        };

        $employeeName = $this->record->user?->name ?? 'Empleado';

        return "Editar {$type} - {$employeeName}";
    }

    protected function afterFill(): void
    {
        $this->originalStatus = $this->record->status;
    }

    protected function afterSave(): void
    {
        $newStatus = $this->record->status;

        if (
            $this->originalStatus !== $newStatus
            && in_array($newStatus, ['approved', 'rejected'], true)
            && $this->record->user !== null
        ) {
            $this->record->user->notify(
                new AbsenceRequestReviewedNotification($this->record)
            );
        }
    }

    protected function getRedirectUrl(): string
    {
        return AbsenceRequestResource::getUrl('index');
    }
}
