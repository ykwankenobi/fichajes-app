<?php

namespace App\Filament\Resources\WorkTimeRecords\Pages;

use App\Filament\Resources\WorkTimeRecords\WorkTimeRecordResource;
use App\Models\WorkTimeRecordCorrection;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditWorkTimeRecord extends EditRecord
{
    protected static string $resource = WorkTimeRecordResource::class;

    public function getTitle(): string
    {
        $type = $this->record->closed_automatically
            ? 'cierre automático'
            : match ($this->record->record_type) {
                'work' => 'trabajo',
                'justified_exit' => 'salida justificada',
                'unjustified_exit' => 'salida no justificada',
                default => 'incidencia',
            };

        return 'Editar ' . $type;
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label('Aprobar revisión')
            ->icon('heroicon-o-check-circle')
            ->color('success');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Revisión aprobada';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $correction = $this->record
            ->corrections()
            ->latest()
            ->first();

        $data['correction'] = [
            'corrected_started_at' => $correction?->corrected_started_at,
            'corrected_ended_at' => $correction?->corrected_ended_at,
            'reason' => $correction?->reason,
        ];

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $correctionData = $data['correction'] ?? [];

        unset($data['correction']);

        $this->validateCorrectionData($correctionData);

        $data['requires_review'] = false;
        $data['reviewed_by'] = Filament::auth()->id();
        $data['reviewed_at'] = now();

        $record->update($data);

        $hasCorrection = filled($correctionData['corrected_started_at'] ?? null)
            || filled($correctionData['corrected_ended_at'] ?? null)
            || filled($correctionData['reason'] ?? null);

        if ($hasCorrection) {
            $record->corrections()->updateOrCreate(
                [
                    'id' => $record->corrections()->latest()->value('id'),
                ],
                [
                    'requested_by' => Filament::auth()->id(),
                    'reviewed_by' => Filament::auth()->id(),
                    'original_started_at' => $record->started_at,
                    'original_ended_at' => $record->ended_at,
                    'corrected_started_at' => $correctionData['corrected_started_at'] ?? null,
                    'corrected_ended_at' => $correctionData['corrected_ended_at'] ?? null,
                    'status' => WorkTimeRecordCorrection::STATUS_APPROVED,
                    'reason' => $correctionData['reason'] ?? 'Corrección aprobada por revisión de incidencia.',
                    'reviewed_at' => now(),
                ]
            );
        }

        return $record;
    }

    protected function validateCorrectionData(array $correctionData): void
    {
        $startedAt = $correctionData['corrected_started_at'] ?? null;
        $endedAt = $correctionData['corrected_ended_at'] ?? null;
        $reason = $correctionData['reason'] ?? null;

        $hasAnyCorrectionData = filled($startedAt) || filled($endedAt) || filled($reason);

        if (! $hasAnyCorrectionData) {
            return;
        }

        if (! filled($startedAt) || ! filled($endedAt)) {
            $this->stopWithValidationError('Para corregir un fichaje debes indicar inicio corregido y fin corregido.');
        }

        if (! filled($reason)) {
            $this->stopWithValidationError('Para corregir un fichaje debes indicar el motivo de la corrección.');
        }

        $startedAt = Carbon::parse($startedAt);
        $endedAt = Carbon::parse($endedAt);

        if ($endedAt->lessThanOrEqualTo($startedAt)) {
            $this->stopWithValidationError('El fin corregido debe ser posterior al inicio corregido.');
        }

        $durationMinutes = $startedAt->diffInMinutes($endedAt);

        if ($durationMinutes > 16 * 60) {
            $this->stopWithValidationError('La corrección no puede superar 16 horas. Revisa las fechas antes de aprobar la revisión.');
        }
    }

    protected function stopWithValidationError(string $message): never
    {
        Notification::make()
            ->title('Corrección no válida')
            ->body($message)
            ->danger()
            ->send();

        throw ValidationException::withMessages([
            'correction.corrected_ended_at' => $message,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return WorkTimeRecordResource::getUrl('index');
    }
}
