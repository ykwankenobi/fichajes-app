<x-app-layout>
    <x-slot name="header">
        <x-ui.page.title>
            Dashboard
        </x-ui.page.title>
    </x-slot>

    <x-page.container>
        @can('view-admin-panel')
            <div class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-ui.stat-card
                        href="{{ url('/admin/work-time-incidents') }}"
                        title="Incidencias"
                        :value="$pendingWorkTimeIncidentsCount"
                        color="danger"
                    >
                        <svg
                            class="h-6 w-6"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
                            <path d="M12 9v4" />
                            <path d="M12 17h.01" />
                        </svg>
                    </x-ui.stat-card>

                    <x-ui.stat-card
                        href="{{ url('/admin/absence-requests') }}"
                        title="Ausencias pendientes"
                        :value="$pendingAbsenceRequestsCount"
                        color="info"
                    >
                        <svg
                            class="h-6 w-6"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <path d="M8 2v4" />
                            <path d="M16 2v4" />
                            <rect width="18" height="18" x="3" y="4" rx="2" />
                            <path d="M3 10h18" />
                            <path d="M9 16h6" />
                        </svg>
                    </x-ui.stat-card>
                </div>

                <x-ui.card>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <div class="space-y-2">
                                <p class="text-sm font-medium text-gray-500">
                                    Trabajando ahora
                                </p>

                                <p class="text-3xl font-bold text-gray-900">
                                    {{ $workingUsers->count() }}
                                </p>
                            </div>

                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600">
                                <svg
                                    class="h-6 w-6"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                    <circle cx="9" cy="7" r="4" />
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                </svg>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 pt-4">
                            @forelse($workingUsers as $user)
                                <div class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0">
                                    <div class="font-medium text-gray-900">
                                        {{ $user->name }}
                                    </div>

                                    <div class="text-sm text-gray-500">
                                        Desde {{ $user->activeWorkTimeRecord?->started_at?->format('H:i') }}
                                    </div>
                                </div>

                                @if(! $loop->last)
                                    <div class="h-px bg-gray-200"></div>
                                @endif
                            @empty
                                <p class="text-sm text-gray-500">
                                    No hay empleados trabajando ahora.
                                </p>
                            @endforelse
                        </div>
                    </div>
                </x-ui.card>
            </div>
        @endcan
    </x-page.container>
</x-app-layout>
