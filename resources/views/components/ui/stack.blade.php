@props([
    'space' => '8',
])

@php
    $spaces = [
        '2' => 'space-y-2',
        '4' => 'space-y-4',
        '6' => 'space-y-6',
        '8' => 'space-y-8',
        '10' => 'space-y-10',
        '12' => 'space-y-12',
    ];

    $spaceClass = $spaces[$space] ?? 'space-y-8';
@endphp

<div {{ $attributes->merge([
    'class' => $spaceClass
]) }}>
    {{ $slot }}
</div>
