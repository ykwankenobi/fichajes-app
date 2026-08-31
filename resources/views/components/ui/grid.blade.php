@props([
    'cols' => '4',
])

@php
    $grids = [
        '1' => 'grid-cols-1',
        '2' => 'grid-cols-1 md:grid-cols-2',
        '3' => 'grid-cols-1 md:grid-cols-3',
        '4' => 'grid-cols-1 md:grid-cols-2 xl:grid-cols-4',
    ];

    $gridClass = $grids[$cols] ?? $grids['4'];
@endphp

<div {{ $attributes->merge([
    'class' => "grid gap-6 $gridClass"
]) }}>
    {{ $slot }}
</div>
