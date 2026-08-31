@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'id' => null,
])

@php
    $id ??= $name;
@endphp

<x-ui.form.group>
    <x-ui.form.label :for="$id">
        {{ $label }}
    </x-ui.form.label>

    <x-ui.form.input
        :id="$id"
        :name="$name"
        :type="$type"
        :value="$value"
        {{ $attributes }}
    />

    <x-ui.form.error :name="$name" />
</x-ui.form.group>
