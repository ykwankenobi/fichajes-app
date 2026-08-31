@props([
    'user',
])

@php
    $activeRecord = $user->activeWorkTimeRecord;
    $hasActiveVacation = $user->absenceRequests->isNotEmpty();

    if ($hasActiveVacation) {
        $statusLabel = 'Ausencia';
        $statusClasses = 'bg-blue-100 text-blue-800 ring-blue-200';
    } elseif ($activeRecord?->record_type === \App\Models\WorkTimeRecord::TYPE_WORK) {
        $statusLabel = 'Trabajando';
        $statusClasses = 'bg-green-100 text-green-800 ring-green-200';
    } elseif ($activeRecord?->record_type === \App\Models\WorkTimeRecord::TYPE_JUSTIFIED_EXIT) {
        $statusLabel = 'Salida justificada';
        $statusClasses = 'bg-yellow-100 text-yellow-800 ring-yellow-200';
    } elseif ($activeRecord?->record_type === \App\Models\WorkTimeRecord::TYPE_UNJUSTIFIED_EXIT) {
        $statusLabel = 'Salida no justificada';
        $statusClasses = 'bg-red-100 text-red-800 ring-red-200';
    } else {
        $statusLabel = 'No trabajando';
        $statusClasses = 'bg-gray-100 text-gray-700 ring-gray-200';
    }
@endphp

<span class="inline-flex w-fit items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $statusClasses }}">
    {{ $statusLabel }}
</span>
