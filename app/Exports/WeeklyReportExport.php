<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class WeeklyReportExport implements FromCollection, WithHeadings
{
    public function __construct(
        private Collection $report,
        private Collection $dailyReport
    ) {}

    public function headings(): array
    {
        return [
            'Tipo',
            'Empleado / Fecha',
            'Horas computadas',
            'Trabajadas',
            'Justificadas',
            'Sin justificar',
            'Esperadas',
            'Diferencia',
        ];
    }

    public function collection(): Collection
    {
        $rows = collect();

        foreach ($this->report as $row) {
            $rows->push([
                'Resumen semanal',
                $row['usuario'],
                $row['computables'],
                $row['trabajadas'],
                $row['justificadas'],
                $row['injustificadas'],
                $row['esperadas'],
                $row['diferencia'],
            ]);
        }

        foreach ($this->dailyReport as $row) {
            $rows->push([
                'Detalle diario',
                \Carbon\Carbon::parse($row['fecha'])->format('d/m/Y'),
                $row['computables'],
                $row['trabajadas'],
                $row['justificadas'],
                $row['injustificadas'],
                '',
                '',
            ]);
        }

        return $rows;
    }
}