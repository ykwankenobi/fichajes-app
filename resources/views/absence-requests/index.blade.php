<x-app-layout>
    <x-slot name="header">
        <x-ui.page.title>
            Mis ausencias
        </x-ui.page.title>

        <div class="flex items-center gap-3">

            <x-ui.link href="{{ route('dashboard') }}">
                Volver
            </x-ui.link>

            <x-ui.link href="{{ route('absence-requests.create') }}">
                Nueva solicitud
            </x-ui.link>

        </div>
    </x-slot>

    <x-page.container>

        @if(session('success'))
            <div class="mb-4 text-green-600">
                {{ session('success') }}
            </div>
        @endif

        <x-ui.card>

            <x-ui.page.title>
                Solicitudes realizadas
            </x-ui.page.title>

            @forelse($absenceRequests as $absence)

                <x-ui.table.row>

                    <div>

                        <div class="font-semibold">
							{{ match ($absence->type) {
								'vacation' => 'Vacaciones',
								'medical_leave' => 'Baja médica',
								'leave_of_absence' => 'Excedencia',
								'personal_leave' => 'Permiso personal',
								default => 'Otro',
							} }}
						</div>

                        <div class="text-sm text-gray-500">
                            {{ $absence->starts_at->format('d/m/Y') }}
                            -
                            {{ $absence->ends_at->format('d/m/Y') }}
                        </div>

                        @if($absence->reason)
                            <div class="text-sm text-gray-500">
                                {{ $absence->reason }}
                            </div>
                        @endif

                    </div>

                    <div>

                        @if($absence->status === 'pending')

                            <x-ui.badge color="yellow">
                                Pendiente
                            </x-ui.badge>

                        @elseif($absence->status === 'approved')

                            <x-ui.badge color="green">
                                Aprobada
                            </x-ui.badge>

                        @elseif($absence->status === 'rejected')

                            <x-ui.badge color="red">
                                Rechazada
                            </x-ui.badge>

                        @endif

                    </div>

                </x-ui.table.row>

            @empty

                <p class="text-gray-500">
                    No hay solicitudes.
                </p>

            @endforelse

        </x-ui.card>

        <x-ui.pagination>
            {{ $absenceRequests->links() }}
        </x-ui.pagination>

    </x-page.container>
</x-app-layout>
