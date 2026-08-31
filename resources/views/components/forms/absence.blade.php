@props([
    'action',
    'method' => 'POST',
    'absenceRequest' => null,
    'submit' => 'Guardar',
])

<x-ui.card>
    <form method="POST" action="{{ $action }}">
        @csrf

        @if ($method !== 'POST')
            @method($method)
        @endif

        <div class="mb-6 rounded-lg border border-blue-100 bg-blue-50 p-4">
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

        <x-ui.form.stack>

            <x-ui.form.group>
                <x-ui.form.label for="type">
                    Tipo
                </x-ui.form.label>

                <x-ui.form.select name="type" id="type" required>
                    <option value="vacation" @selected(old('type', $absenceRequest?->type) === 'vacation')>
                        Vacaciones
                    </option>

                    <option value="medical_leave" @selected(old('type', $absenceRequest?->type) === 'medical_leave')>
                        Baja médica
                    </option>

                    <option value="leave_of_absence" @selected(old('type', $absenceRequest?->type) === 'leave_of_absence')>
                        Excedencia
                    </option>

                    <option value="personal_leave" @selected(old('type', $absenceRequest?->type) === 'personal_leave')>
                        Permiso personal
                    </option>

                    <option value="other" @selected(old('type', $absenceRequest?->type) === 'other')>
                        Otro
                    </option>
                </x-ui.form.select>

                <x-ui.form.error name="type" />
            </x-ui.form.group>

            <x-ui.form.field
                name="starts_at"
                label="Desde"
                type="date"
                :value="old('starts_at', $absenceRequest?->starts_at?->format('Y-m-d'))"
                required
            />

            <x-ui.form.field
                name="ends_at"
                label="Hasta"
                type="date"
                :value="old('ends_at', $absenceRequest?->ends_at?->format('Y-m-d'))"
                required
            />

            <x-ui.form.group>
                <x-ui.form.label for="reason">
                    Motivo
                </x-ui.form.label>

                <textarea
                    name="reason"
                    id="reason"
                    rows="4"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >{{ old('reason', $absenceRequest?->reason) }}</textarea>

                <x-ui.form.error name="reason" />
            </x-ui.form.group>

            <x-ui.form.actions>
                <x-ui.primary-button type="submit">
                    {{ $submit }}
                </x-ui.primary-button>
            </x-ui.form.actions>

        </x-ui.form.stack>
    </form>
</x-ui.card>
