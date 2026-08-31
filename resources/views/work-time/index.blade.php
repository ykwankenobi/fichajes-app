<x-app-layout>
    <x-slot name="header">
        <x-ui.page.title>
            Mi Zona
        </x-ui.page.title>
    </x-slot>

    <x-page.container>
	@php
		$hasOldOpenRecord = $activeRecord
			&& $activeRecord->started_at->isBefore(today());
	@endphp
		@if ($hasOldOpenRecord)
			<x-ui.card>
				<div class="space-y-2">
					<p class="text-red-600 font-semibold">
						Tienes un fichaje abierto desde un día anterior.
					</p>

					<p class="text-sm text-gray-600">
						Esto puede afectar al cálculo de horas trabajadas.
						Finaliza la jornada o contacta con administración.
					</p>
				</div>
			</x-ui.card>
		@endif

        @if ($activeRecord)
            <x-ui.grid cols="2">
                <x-ui.stat-card
                    title="Estado actual"
                    :value="match ($activeRecord->record_type) {
                        \App\Models\WorkTimeRecord::TYPE_WORK => 'Trabajando',
                        \App\Models\WorkTimeRecord::TYPE_JUSTIFIED_EXIT => 'Salida justificada',
                        \App\Models\WorkTimeRecord::TYPE_UNJUSTIFIED_EXIT => 'Salida sin justificar',
                        default => 'Estado desconocido',
                    }"
                    :color="match ($activeRecord->record_type) {
                        \App\Models\WorkTimeRecord::TYPE_WORK => 'success',
                        \App\Models\WorkTimeRecord::TYPE_JUSTIFIED_EXIT => 'warning',
                        \App\Models\WorkTimeRecord::TYPE_UNJUSTIFIED_EXIT => 'danger',
                        default => 'default',
                    }"
                />

                <x-ui.stat-card
                    title="{{ $activeRecord->record_type === \App\Models\WorkTimeRecord::TYPE_WORK ? 'Entrada' : 'Salida desde' }}"
                    :value="$activeRecord->started_at->format('d/m/Y H:i')"
                    color="default"
                />
            </x-ui.grid>
        @endif

        <x-ui.card>
            <x-ui.form.stack>

                {{-- Mensajes --}}
                @if (session('success'))
                    <div class="text-green-600">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="text-red-600">
                        {{ session('error') }}
                    </div>
                @endif

                @if (! $activeRecord)

                    <p class="text-gray-700">
                        No tienes ninguna jornada abierta.
                    </p>

                    <form method="POST" action="{{ route('work-time.clock-in') }}">
                        @csrf

                        <x-ui.primary-button
                            type="submit"
                            class="w-full sm:w-auto py-4 text-lg justify-center text-center"
                        >
                            Fichar entrada
                        </x-ui.primary-button>
                    </form>

                @elseif ($activeRecord->record_type === \App\Models\WorkTimeRecord::TYPE_WORK)

                    {{-- Finalizar jornada / salir --}}
                    <div class="relative w-full sm:w-auto">
                        <form method="POST" action="{{ route('work-time.clock-out') }}">
                            @csrf

                            <x-danger-button
                                type="button"
                                class="w-full sm:w-auto py-4 text-lg justify-center text-center pointer-events-none"
                            >
                                Registrar Salida
                            </x-danger-button>

                            <select
                                name="end_type"
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                required
                                onchange="this.form.submit()"
                            >
                                <option value="" disabled selected>
                                    Selecciona tipo de salida
                                </option>

                                <option value="end_shift">
                                    Finalizar jornada
                                </option>

                                <option value="justified_exit">
                                    Salida justificada
                                </option>

                                <option value="unjustified_exit">
                                    Salida sin justificar
                                </option>
                            </select>
                        </form>
                    </div>

                @elseif ($activeRecord->record_type === \App\Models\WorkTimeRecord::TYPE_JUSTIFIED_EXIT)

                    <p class="text-gray-700">
                        Estás en una salida justificada desde las
                        {{ $activeRecord->started_at->format('H:i') }}.
                    </p>

                    <form method="POST" action="{{ route('work-time.clock-in') }}">
                        @csrf

                        <x-ui.primary-button
                            type="submit"
                            class="w-full sm:w-auto py-4 text-lg justify-center text-center"
                        >
                            Volver al trabajo
                        </x-ui.primary-button>
                    </form>

					<form method="POST" action="{{ route('work-time.finish-exit') }}">
						@csrf

						<x-danger-button
							type="submit"
							class="w-full sm:w-auto py-4 text-lg justify-center text-center"
						>
							Finalizar jornada
						</x-danger-button>
					</form>

                @elseif ($activeRecord->record_type === \App\Models\WorkTimeRecord::TYPE_UNJUSTIFIED_EXIT)

                    <p class="text-gray-700">
                        Estás en una salida sin justificar desde las
                        {{ $activeRecord->started_at->format('H:i') }}.
                    </p>

                    <form method="POST" action="{{ route('work-time.clock-in') }}">
                        @csrf

                        <x-ui.primary-button
                            type="submit"
                            class="w-full sm:w-auto py-4 text-lg justify-center text-center"
                        >
                            Volver al trabajo
                        </x-ui.primary-button>
                    </form>

					<form method="POST" action="{{ route('work-time.finish-exit') }}">
						@csrf

						<x-danger-button
							type="submit"
							class="w-full sm:w-auto py-4 text-lg justify-center text-center"
						>
							Finalizar jornada
						</x-danger-button>
					</form>

                @endif

            </x-ui.form.stack>
        </x-ui.card>

        <x-ui.card>
			<x-ui.form.stack>

				<x-ui.page.title>
					Ausencias
				</x-ui.page.title>

				<p class="text-gray-700">
					Gestiona tus solicitudes de vacaciones y ausencias.
				</p>

				<div class="rounded-lg border border-blue-100 bg-blue-50 p-4">
					<p class="text-sm text-blue-700">
						Vacaciones disponibles
					</p>

					<p class="mt-1 text-3xl font-bold text-blue-900">
						{{ auth()->user()->vacationDaysAvailableForYear(now()->year) }}
						días
					</p>

					<p class="mt-1 text-xs text-blue-600">
						Disponibles para {{ now()->year }}
					</p>
				</div>

				<div class="flex flex-col sm:flex-row gap-3">
					<x-ui.link
						href="{{ route('absence-requests.index') }}"
						class="w-full sm:w-auto py-4 text-lg justify-center text-center"
					>
						Ver mis solicitudes
					</x-ui.link>

					<x-ui.link
						href="{{ route('absence-requests.create') }}"
						class="w-full sm:w-auto py-4 text-lg justify-center text-center"
					>
						Solicitar ausencia
					</x-ui.link>
				</div>

			</x-ui.form.stack>
		</x-ui.card>

    </x-page.container>
</x-app-layout>
