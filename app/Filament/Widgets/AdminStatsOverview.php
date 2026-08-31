<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\AbsenceRequests\AbsenceRequestResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\WorkTimeRecords\WorkTimeRecordResource;
use App\Models\AbsenceRequest;
use App\Models\User;
use App\Models\WorkTimeRecord;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Resumen del sistema';

    protected int | array | null $columns = [
        'default' => 1,
        'md' => 2,
    ];

    protected function getStats(): array
    {
        $activeEmployeesCount = User::query()
            ->where('is_admin', false)
            ->where('activo', true)
            ->count();

        $inactiveEmployeesCount = User::query()
            ->where('is_admin', false)
            ->where('activo', false)
            ->count();

        $pendingAbsencesCount = AbsenceRequest::query()
            ->where('status', 'pending')
            ->count();

        $pendingIncidentsCount = WorkTimeRecord::query()
            ->where('requires_review', true)
            ->count();

        $workingNowCount = User::query()
            ->where('is_admin', false)
            ->where('activo', true)
            ->whereHas('activeWorkTimeRecord', function ($query): void {
                $query->where('record_type', WorkTimeRecord::TYPE_WORK);
            })
            ->count();

        $currentDateTime = now()
            ->timezone(config('app.timezone'))
            ->format('d/m/Y H:i');

        return [
            Stat::make('Trabajando ahora', $workingNowCount)
                ->description("Actualizado: {$currentDateTime}")
                ->url(UserResource::getUrl('index', [
                    'filters' => [
                        'status' => [
                            'value' => 'working_now',
                        ],
                    ],
                ]))
                ->color('info')
                ->columnSpanFull(),

            Stat::make('Empleados activos', $activeEmployeesCount)
                ->description('Gestionar usuarios')
                ->url(UserResource::getUrl('index', [
                    'filters' => [
                        'status' => [
                            'value' => 'active',
                        ],
                    ],
                ]))
                ->color('success'),

            Stat::make('Empleados inactivos', $inactiveEmployeesCount)
                ->description('Bajas, excedencias o no disponibles')
                ->url(UserResource::getUrl('index', [
                    'filters' => [
                        'status' => [
                            'value' => 'inactive',
                        ],
                    ],
                ]))
                ->color($inactiveEmployeesCount > 0 ? 'warning' : 'success'),

            Stat::make('Ausencias pendientes', $pendingAbsencesCount)
                ->description('Solicitudes por revisar')
                ->url(AbsenceRequestResource::getUrl('index', [
                    'filters' => [
                        'status' => [
                            'value' => 'pending',
                        ],
                    ],
                ]))
                ->color($pendingAbsencesCount > 0 ? 'warning' : 'success'),

            Stat::make('Incidencias pendientes', $pendingIncidentsCount)
                ->description('Fichajes que requieren revisión')
                ->url(WorkTimeRecordResource::getUrl('index', [
                    'filters' => [
                        'requires_review' => [
                            'value' => 'true',
                        ],
                    ],
                ]))
                ->color($pendingIncidentsCount > 0 ? 'danger' : 'success'),
        ];
    }
}
