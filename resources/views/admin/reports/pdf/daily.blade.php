<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe diario de fichajes</title>

    <style>
        @page {
            margin: 24px 28px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
            line-height: 1.35;
        }

        .header {
            border-bottom: 2px solid #f59e0b;
            padding-bottom: 10px;
            margin-bottom: 18px;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
            margin: 0;
            color: #111827;
        }

        .subtitle {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }

        .meta {
            width: 100%;
            margin-bottom: 16px;
            border-collapse: collapse;
        }

        .meta td {
            border: none;
            padding: 3px 0;
            font-size: 11px;
        }

        .meta-label {
            width: 110px;
            color: #6b7280;
            font-weight: bold;
        }

        h2 {
            font-size: 14px;
            margin: 18px 0 8px;
            color: #111827;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        th {
            background-color: #f3f4f6;
            color: #374151;
            font-weight: bold;
            font-size: 10px;
            text-align: left;
            border: 1px solid #d1d5db;
            padding: 6px 5px;
        }

        td {
            border: 1px solid #d1d5db;
            padding: 6px 5px;
            vertical-align: top;
            font-size: 10px;
        }

        tr:nth-child(even) td {
            background-color: #fafafa;
        }

        .text-right {
            text-align: right;
        }

        .positive {
            color: #047857;
            font-weight: bold;
        }

        .negative {
            color: #dc2626;
            font-weight: bold;
        }

        .empty {
            color: #6b7280;
            text-align: center;
            padding: 14px;
        }

        .small {
            font-size: 9px;
            color: #6b7280;
        }

        .correction-box {
            margin-top: 6px;
            font-size: 10px;
            color: #374151;
        }

        .footer {
            position: fixed;
            bottom: -10px;
            left: 0;
            right: 0;
            font-size: 9px;
            color: #9ca3af;
            text-align: center;
            border-top: 1px solid #e5e7eb;
            padding-top: 6px;
        }
    </style>
</head>
<body>
    @php
        $isMonth = preg_match('/^\d{4}-\d{2}$/', (string) $week) === 1;
        $periodLabel = $week;
        $corrections = $corrections ?? collect();

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $week) === 1) {
            $periodLabel = \Carbon\Carbon::createFromFormat('Y-m-d', $week)
                ->format('d/m/Y');
        }
    @endphp

    <div class="header">
        <h1 class="title">Informe diario de fichajes</h1>
        <div class="subtitle">{{ $company?->displayName() ?? config('app.name') }}</div>
    </div>

    <table class="meta">
        <tr>
            <td class="meta-label">Generado:</td>
            <td>{{ now()->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Periodo:</td>
            <td>{{ $periodLabel }}</td>
        </tr>
        @if ($company?->legal_name)
            <tr>
                <td class="meta-label">Razón social:</td>
                <td>{{ $company->legal_name }}</td>
            </tr>
        @endif
        @if ($company?->tax_id)
            <tr>
                <td class="meta-label">CIF/NIF:</td>
                <td>{{ $company->tax_id }}</td>
            </tr>
        @endif
        @if ($company?->fullAddress())
            <tr>
                <td class="meta-label">Dirección:</td>
                <td>{{ $company->fullAddress() }}</td>
            </tr>
        @endif
    </table>

    <h2>Resumen</h2>

    <table>
        <thead>
            <tr>
                <th>Empleado</th>
                <th class="text-right">Esperadas</th>
                <th class="text-right">Computadas</th>
                <th class="text-right">Trabajadas</th>
                <th class="text-right">Justificadas</th>
                <th class="text-right">Sin justificar</th>
                <th class="text-right">Diferencia</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report as $row)
                <tr>
                    <td>{{ $row['usuario'] }}</td>
                    <td class="text-right">{{ $row['esperadas'] }}</td>
                    <td class="text-right">{{ $row['computables'] }}</td>
                    <td class="text-right">{{ $row['trabajadas'] }}</td>
                    <td class="text-right">{{ $row['justificadas'] }}</td>
                    <td class="text-right">{{ $row['injustificadas'] }}</td>
                    <td class="text-right {{ str_starts_with($row['diferencia'], '-') ? 'negative' : 'positive' }}">
                        {{ $row['diferencia'] }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty">
                        No hay datos para el periodo seleccionado.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($corrections->isNotEmpty())
        <h2>Correcciones aplicadas</h2>

        <table>
            <thead>
                <tr>
                    <th>Empleado</th>
                    <th>Original</th>
                    <th>Corregido</th>
                    <th>Motivo</th>
                    <th>Revisión</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($corrections as $correction)
                    <tr>
                        <td>{{ $correction['usuario'] }}</td>
                        <td>
                            <div>
                                Inicio:
                                {{ $correction['original_inicio'] ? \Carbon\Carbon::parse($correction['original_inicio'])->format('d/m/Y H:i') : '-' }}
                            </div>
                            <div>
                                Fin:
                                {{ $correction['original_fin'] ? \Carbon\Carbon::parse($correction['original_fin'])->format('d/m/Y H:i') : '-' }}
                            </div>
                        </td>
                        <td>
                            <div>
                                Inicio:
                                {{ $correction['corregido_inicio'] ? \Carbon\Carbon::parse($correction['corregido_inicio'])->format('d/m/Y H:i') : '-' }}
                            </div>
                            <div>
                                Fin:
                                {{ $correction['corregido_fin'] ? \Carbon\Carbon::parse($correction['corregido_fin'])->format('d/m/Y H:i') : '-' }}
                            </div>
                        </td>
                        <td>{{ $correction['motivo'] }}</td>
                        <td>
                            <div>{{ $correction['revisado_por'] }}</div>
                            <div class="small">
                                {{ $correction['fecha_revision'] ? \Carbon\Carbon::parse($correction['fecha_revision'])->format('d/m/Y H:i') : '-' }}
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="correction-box">
            Las horas corregidas se han usado para calcular este informe. El fichaje original se conserva para auditoría.
        </div>
    @endif

    <div class="footer">
        Informe generado desde fichaje.elcos.es
    </div>
</body>
</html>
