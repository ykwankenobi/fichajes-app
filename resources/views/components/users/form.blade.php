@props([
    'action',
    'method' => 'POST',
    'user' => null,
    'submit' => 'Guardar',
])

<x-ui.card>
    <form method="POST" action="{{ $action }}">
        @csrf

        @if ($method !== 'POST')
            @method($method)
        @endif

        <x-ui.form.stack>

            <x-ui.form.field
                name="name"
                label="Nombre"
                :value="old('name', $user?->name)"
                required
            />

            <x-ui.form.field
                name="email"
                label="Email"
                type="email"
                :value="old('email', $user?->email)"
                required
            />

            <x-ui.form.field
                name="dni"
                label="DNI"
                :value="old('dni', $user?->dni)"
            />

            <x-ui.form.group>
                <x-ui.form.label for="activo">
                    Estado
                </x-ui.form.label>

                <x-ui.form.select name="activo" id="activo">
                    <option value="1" @selected(old('activo', $user?->activo ?? 1) == 1)>
                        Activo
                    </option>

                    <option value="0" @selected(old('activo', $user?->activo ?? 1) == 0)>
                        Inactivo
                    </option>
                </x-ui.form.select>

                <x-ui.form.error name="activo" />
            </x-ui.form.group>

            <x-ui.form.field
                name="horas_semanales"
                label="Horas semanales"
                type="number"
                min="0"
                :value="old('horas_semanales', $user?->horas_semanales)"
            />

            <x-ui.form.field
                name="fecha_alta"
                label="Fecha alta"
                type="date"
                :value="old('fecha_alta', $user?->fecha_alta?->format('Y-m-d'))"
            />

            <x-ui.form.field
                name="fecha_baja"
                label="Fecha baja"
                type="date"
                :value="old('fecha_baja', $user?->fecha_baja?->format('Y-m-d'))"
            />

            @unless ($user)
                <x-ui.form.field
                    name="password"
                    label="Contraseña"
                    type="password"
                    required
                />
            @endunless

            <x-ui.form.actions>
                <x-ui.primary-button type="submit">
                    {{ $submit }}
                </x-ui.primary-button>
            </x-ui.form.actions>

        </x-ui.form.stack>
    </form>
</x-ui.card>