<div class="overflow-x-auto">
    <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
        <thead>
            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                <th class="px-3 py-3">Empleado</th>
                <th class="px-3 py-3">Tipo</th>
                <th class="px-3 py-3">Entrada</th>
                <th class="px-3 py-3">Salida</th>
                <th class="px-3 py-3">Duración</th>
                <th class="px-3 py-3">Estado</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
            @forelse ($records as $record)
                <tr class="text-gray-700 dark:text-gray-200">
                    <td class="whitespace-nowrap px-3 py-3 font-medium">{{ $record['employee'] }}</td>
                    <td class="whitespace-nowrap px-3 py-3">{{ $record['type'] }}</td>
                    <td class="whitespace-nowrap px-3 py-3">{{ $record['started_at'] }}</td>
                    <td class="whitespace-nowrap px-3 py-3">{{ $record['ended_at'] }}</td>
                    <td class="whitespace-nowrap px-3 py-3">{{ $record['duration'] }}</td>
                    <td class="whitespace-nowrap px-3 py-3">
                        {{ $record['status'] }}
                        @if ($record['corrected'])
                            <span class="ml-1 text-xs text-primary-600 dark:text-primary-400">· Corregido</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">
                        No hay fichajes para este día.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
