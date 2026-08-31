<x-app-layout>
    <x-slot name="header">
        <x-ui.page.title>
            Informes
        </x-ui.page.title>
    </x-slot>

    <x-page.container>
        @php
			$singleUser = ! auth()->user()->is_admin || filled($selectedUserId);
			$selectedUser = null;

			if ($singleUser && $report->count()) {
				$selectedUser = $report->first()['usuario'];
			}

			[$year, $weekNumber] = explode('-W', $week);

			$weekStart = \Carbon\Carbon::now()
				->setISODate((int) $year, (int) $weekNumber)
				->startOfWeek();

			$weekEnd = $weekStart->copy()->endOfWeek();

			$weekRange = $weekStart->format('d/m/Y') . ' - ' . $weekEnd->format('d/m/Y');
		@endphp

        <x-ui.card>
            <form
				method="GET"
				class="mb-6"
				x-data
				x-ref="filterForm"
			>
				<div
					class="flex flex-col sm:flex-row sm:flex-wrap items-stretch sm:items-end gap-4"
					x-data="{ reportType: '{{ request('report_type', 'month') }}' }"
				>
					<div class="w-full sm:w-auto">
						<label class="block text-sm font-medium mb-1">
							Tipo de informe
						</label>

						<select
							name="report_type"
							x-model="reportType"
							class="w-full border-gray-300 rounded-md shadow-sm"
						>
							<option value="month">Mes</option>
							<option value="week">Semana</option>
						</select>
					</div>

					<div class="w-full sm:w-auto" x-show="reportType === 'week'">
						<label class="block text-sm font-medium mb-1">
							Seleccione semana
						</label>

						<input
							type="week"
							name="week"
							value="{{ $week }}"
							class="w-full border-gray-300 rounded-md shadow-sm"
							@change="$refs.filterForm.submit()"
						>
					</div>

					<div class="w-full sm:w-auto" x-show="reportType === 'month'">
						<label class="block text-sm font-medium mb-1">
							Seleccione mes
						</label>

						<input
							type="month"
							name="month"
							value="{{ request('month', now()->format('Y-m')) }}"
							class="w-full border-gray-300 rounded-md shadow-sm"
						>
					</div>

					@if(auth()->user()->is_admin)
						<div class="w-full sm:w-auto">
							<label class="block text-sm font-medium mb-1">
								Seleccione Empleado
							</label>

							<select
								name="user_id"
								class="w-full border-gray-300 rounded-md shadow-sm"
								@change="$refs.filterForm.submit()"
							>
								<option value="">Todos</option>

								@foreach($users as $user)
									<option
										value="{{ $user->id }}"
										@selected((string) $selectedUserId === (string) $user->id)
									>
										{{ $user->name }}
									</option>
								@endforeach
							</select>
						</div>
					@endif

					<label class="w-full sm:w-auto flex items-center gap-2 h-[42px] text-sm">
						<input
							type="checkbox"
							name="include_daily"
							value="1"
							@checked(request()->boolean('include_daily'))
							class="rounded border-gray-300"
						>
						<span>Incluir detalle diario en el PDF</span>
					</label>

					<div class="w-full sm:w-auto flex items-center gap-2">
						{{--
						<x-ui.link href="{{ route('reports.weekly.export', request()->query()) }}">
							Exportar CSV
						</x-ui.link>
						--}}

						<x-ui.link
							x-bind:href="reportType === 'month'
								? '{{ route('reports.monthly.export.pdf', request()->query()) }}'
								: '{{ route('reports.weekly.export.pdf', request()->query()) }}'"
							class="h-[42px] min-h-[42px] px-4 py-0 items-center justify-center leading-none"
						>
							Exportar PDF
						</x-ui.link>
					</div>
				</div>
			</form>
        </x-ui.card>

        <x-ui.card>
			<x-ui.page.title>
				Resumen semanal
			</x-ui.page.title>

			@forelse($report as $row)
				<div class="py-4 border-b last:border-b-0">
					<div class="text-sm text-gray-700">
						<span class="font-semibold">
							Empleado:
						</span>
						{{ $row['usuario'] }}

						<span class="hidden sm:inline"> | </span>
						<br class="sm:hidden">

						<span class="font-semibold">
							Horas contrato semanal:
						</span>
						{{ $row['esperadas'] }}
					</div>

					<div class="mt-2 text-sm text-gray-700">
						<span class="font-semibold">
							Semana {{ (int) $weekNumber }}:
						</span>

						<span class="text-gray-500">
							{{ $weekRange }}
						</span>

						<span class="hidden sm:inline"> | </span>
						<br class="sm:hidden">

						<span class="font-semibold">
							Horas computadas:
						</span>
						<strong>{{ $row['computables'] }}</strong>

						<span class="hidden sm:inline"> | </span>
						<br class="sm:hidden">

						Horas trabajadas: {{ $row['trabajadas'] }}

						<span class="hidden sm:inline"> | </span>
						<br class="sm:hidden">

						Salidas justificadas: {{ $row['justificadas'] }}

						<span class="hidden sm:inline"> | </span>
						<br class="sm:hidden">

						Salidas sin justificar: {{ $row['injustificadas'] }}

						<span class="hidden sm:inline"> | </span>
						<br class="sm:hidden">

						Total semanal:

						<span class="
							font-semibold
							{{ str_starts_with($row['diferencia'], '-')
								? 'text-red-600'
								: 'text-green-600' }}
						">
							{{ $row['diferencia'] }}
						</span>
					</div>
				</div>
			@empty
				<div class="text-sm text-gray-500">
					No hay datos para la semana seleccionada.
				</div>
			@endforelse
		</x-ui.card>

        @if(filled($selectedUserId) || !auth()->user()->is_admin)
			<x-ui.card>
				<x-ui.page.title>
					Detalle diario
				</x-ui.page.title>

				@forelse($dailyReport as $row)
					<div class="py-4 border-b last:border-b-0">
						<div class="text-sm text-gray-700">
							<span class="font-semibold">
								Fecha:
							</span>

							{{ \Carbon\Carbon::parse($row['fecha'])->format('d/m/Y') }}

							@if (($row['tipo'] ?? null) === 'Vacaciones')
								<span class="ml-2 font-semibold text-amber-600">Vacaciones</span>
							@endif

							<span class="hidden sm:inline"> | </span>
							<br class="sm:hidden">

							<span class="font-semibold">
								Horas computadas:
							</span>

							<strong>{{ $row['computables'] }}</strong>

							<span class="hidden sm:inline"> | </span>
							<br class="sm:hidden">

							Horas trabajadas: {{ $row['trabajadas'] }}

							<span class="hidden sm:inline"> | </span>
							<br class="sm:hidden">

							Salidas justificadas: {{ $row['justificadas'] }}

							<span class="hidden sm:inline"> | </span>
							<br class="sm:hidden">

							Salidas sin justificar: {{ $row['injustificadas'] }}
						</div>
					</div>
				@empty
					<div class="text-sm text-gray-500">
						No hay detalle diario para la semana seleccionada.
					</div>
				@endforelse
			</x-ui.card>
		@endif
    </x-page.container>
</x-app-layout>
