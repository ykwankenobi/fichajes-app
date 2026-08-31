@props([
    'name',
    'id' => null,
    'type' => 'text',
    'value' => null,
])

@php
    $id ??= $name;

    $baseClasses = 'w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
    $errorClasses = $errors->has($name)
        ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
        : '';
@endphp

<input
    id="{{ $id }}"
    name="{{ $name }}"
    type="{{ $type }}"
    value="{{ $value }}"
    {{ $attributes->merge(['class' => trim($baseClasses . ' ' . $errorClasses)]) }}
>