@php
    use App\Models\WorkTimeRecord;

    $brandPalettes = config('branding.palettes');
    $brand = $brandPalettes[config('branding.primary_color')] ?? $brandPalettes['red'];

    $statusLabel = $activeRecord
        ? match ($activeRecord->record_type) {
            WorkTimeRecord::TYPE_WORK => 'Trabajando',
            WorkTimeRecord::TYPE_JUSTIFIED_EXIT => 'Salida justificada',
            WorkTimeRecord::TYPE_UNJUSTIFIED_EXIT => 'Salida sin justificar',
            default => 'Estado desconocido',
        }
        : 'Sin jornada abierta';

    $statusClass = $activeRecord
        ? match ($activeRecord->record_type) {
            WorkTimeRecord::TYPE_WORK => 'border-emerald-300 bg-emerald-50 text-emerald-900',
            WorkTimeRecord::TYPE_JUSTIFIED_EXIT => 'border-amber-300 bg-amber-50 text-amber-900',
            WorkTimeRecord::TYPE_UNJUSTIFIED_EXIT => 'border-red-300 bg-red-50 text-red-900',
            default => 'border-gray-300 bg-gray-50 text-gray-900',
        }
        : 'brand-status';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Fichajes</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            :root {
                --brand-50: {{ $brand['50'] }};
                --brand-300: {{ $brand['300'] }};
                --brand-500: {{ $brand['500'] }};
                --brand-600: {{ $brand['600'] }};
                --brand-700: {{ $brand['700'] }};
                --brand-900: {{ $brand['900'] }};
            }
            .brand-status { border-color: var(--brand-300); background: var(--brand-50); color: var(--brand-900); }
            .brand-focus:focus { border-color: var(--brand-500); --tw-ring-color: var(--brand-500); }
            .brand-button { background: var(--brand-600); }
            .brand-button:hover { background: var(--brand-700); }
            .brand-button:focus { --tw-ring-color: var(--brand-500); }
        </style>
    </head>

    <body class="min-h-screen bg-gray-50 text-gray-950 antialiased" style="font-family: Inter, ui-sans-serif, system-ui, sans-serif;">
        <main class="mx-auto flex min-h-screen w-full max-w-4xl flex-col px-4 py-3 sm:px-6 sm:py-4">
            <header class="flex items-center justify-between gap-3 border-b border-gray-200 pb-3">
                <x-application-logo class="h-10 w-auto min-w-0 shrink sm:h-12" />

                <div class="flex shrink-0 items-center gap-2">
                    <a
                        href="{{ route('login') }}"
                        class="brand-focus inline-flex min-h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 sm:px-4"
                    >
                        Mi panel
                    </a>

                    <a
                        href="{{ url('/admin') }}"
                        class="brand-focus hidden min-h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 sm:inline-flex"
                    >
                        Admin
                    </a>
                </div>
            </header>

            <div class="border-b border-gray-200 py-3 font-semibold uppercase">
                <h1 class="text-sm leading-5 tracking-wide text-gray-950 sm:text-base">
                    Fichajes
                </h1>
                <p class="mt-0.5 text-xs leading-5 tracking-wide text-gray-500 sm:text-sm">
                    {{ now()->format('d/m/Y H:i') }}
                </p>
            </div>

            @if (session('success'))
                <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-900">
                    {{ session('error') }}
                </div>
            @endif

            <section class="flex items-start justify-center py-4 sm:flex-1 sm:items-center sm:py-6">
                <div class="w-full rounded-lg border border-gray-200 bg-white p-4 shadow-sm ring-1 ring-gray-950/5 sm:p-6">
                    @if (! $selectedUser)
                        <form method="POST" action="{{ route('kiosk.verify') }}" class="mx-auto max-w-xl space-y-5">
                            @csrf

                            <div>
                                <label for="user_id" class="block text-sm font-semibold text-gray-900">
                                    Empleado
                                </label>

                                <select
                                    id="user_id"
                                    name="user_id"
                                    class="brand-focus mt-2 block min-h-12 w-full rounded-lg border-gray-300 bg-white text-base shadow-sm sm:text-lg"
                                    required
                                    autofocus
                                >
                                    <option value="">
                                        Selecciona tu nombre
                                    </option>

                                    @foreach ($users as $user)
                                        <option
                                            value="{{ $user->id }}"
                                            @selected((string) old('user_id') === (string) $user->id)
                                        >
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('user_id')
                                    <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="pin" class="block text-sm font-semibold text-gray-900">
                                    PIN
                                </label>

                                <input
                                    id="pin"
                                    name="pin"
                                    type="password"
                                    inputmode="numeric"
                                    autocomplete="off"
                                    pattern="[0-9]*"
                                    class="brand-focus mt-2 block min-h-12 w-full rounded-lg border-gray-300 text-center text-2xl tracking-widest shadow-sm sm:text-3xl"
                                    required
                                >

                                @error('pin')
                                    <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <button
                                type="submit"
                                class="brand-button inline-flex min-h-12 w-full items-center justify-center rounded-lg px-5 text-base font-semibold text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 sm:min-h-14 sm:text-lg"
                            >
                                Continuar
                            </button>
                        </form>
                    @else
                        <div class="mx-auto max-w-2xl space-y-5">
                            <div class="text-center">
                                <p class="text-xs font-semibold uppercase text-gray-500">
                                    Empleado
                                </p>
                                <p class="mt-1 text-2xl font-semibold leading-8 sm:text-4xl">
                                    {{ $selectedUser->name }}
                                </p>
                            </div>

                            <div class="rounded-lg border p-4 text-center sm:p-5 {{ $statusClass }}">
                                <p class="text-xs font-semibold uppercase opacity-80">
                                    Estado actual
                                </p>
                                <p class="mt-2 text-2xl font-bold sm:text-4xl">
                                    {{ $statusLabel }}
                                </p>

                                @if ($activeRecord)
                                    <p class="mt-3 text-sm font-medium sm:text-base">
                                        {{ $activeRecord->record_type === WorkTimeRecord::TYPE_WORK ? 'Entrada' : 'Salida desde' }}:
                                        {{ $activeRecord->started_at->format('d/m/Y H:i') }}
                                    </p>
                                @endif
                            </div>

                            @if ($activeRecord && $activeRecord->started_at->isBefore(today()))
                                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-900">
                                    Fichaje abierto desde un día anterior. Finaliza la jornada o contacta con administración.
                                </div>
                            @endif

                            @if (! $activeRecord)
                                <form method="POST" action="{{ route('kiosk.clock-in', $token) }}">
                                    @csrf

                                    <button
                                        type="submit"
                                        class="inline-flex min-h-16 w-full items-center justify-center rounded-lg bg-emerald-600 px-5 text-xl font-semibold text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 sm:min-h-20 sm:text-2xl"
                                    >
                                        Fichar entrada
                                    </button>
                                </form>
                            @elseif ($activeRecord->record_type === WorkTimeRecord::TYPE_WORK)
                                <form method="POST" action="{{ route('kiosk.clock-out', $token) }}" class="space-y-4">
                                    @csrf

                                    <select
                                        id="end_type"
                                        name="end_type"
                                        class="brand-focus block min-h-12 w-full rounded-lg border-gray-300 bg-white text-base shadow-sm sm:text-lg"
                                        required
                                    >
                                        <option value="">
                                            Selecciona tipo de salida
                                        </option>
                                        <option value="end_shift">Finalizar jornada</option>
                                        <option value="justified_exit">Salida justificada</option>
                                        <option value="unjustified_exit">Salida sin justificar</option>
                                    </select>

                                    <button
                                        type="submit"
                                        class="inline-flex min-h-16 w-full items-center justify-center rounded-lg bg-red-600 px-5 text-xl font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:min-h-20 sm:text-2xl"
                                    >
                                        Registrar salida
                                    </button>
                                </form>
                            @elseif (in_array($activeRecord->record_type, [WorkTimeRecord::TYPE_JUSTIFIED_EXIT, WorkTimeRecord::TYPE_UNJUSTIFIED_EXIT], true))
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <form method="POST" action="{{ route('kiosk.clock-in', $token) }}">
                                        @csrf

                                        <button
                                            type="submit"
                                            class="inline-flex min-h-16 w-full items-center justify-center rounded-lg bg-emerald-600 px-5 text-lg font-semibold text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 sm:min-h-20 sm:text-xl"
                                        >
                                            Volver al trabajo
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('kiosk.finish-exit', $token) }}">
                                        @csrf

                                        <button
                                            type="submit"
                                            class="inline-flex min-h-16 w-full items-center justify-center rounded-lg bg-red-600 px-5 text-lg font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:min-h-20 sm:text-xl"
                                        >
                                            Finalizar jornada
                                        </button>
                                    </form>
                                </div>
                            @endif

                            <a
                                href="{{ route('kiosk.index') }}"
                                class="brand-focus inline-flex min-h-12 w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-base font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2"
                            >
                                Cambiar empleado
                            </a>
                        </div>
                    @endif
                </div>
            </section>
        </main>
    </body>
</html>
