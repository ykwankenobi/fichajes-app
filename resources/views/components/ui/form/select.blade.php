@props([
    'name',
    'id' => null,
])

@php
    $id ??= $name;

    $baseClasses = 'w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
    $errorClasses = $errors->has($name)
        ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
        : '';
@endphp

<select
    id="{{ $id }}"
    name="{{ $name }}"
    {{ $attributes->merge(['class' => trim($baseClasses . ' ' . $errorClasses)]) }}
>
    {{ $slot }}
</select>
