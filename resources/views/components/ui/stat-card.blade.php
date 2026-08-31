@props([
    'title',
    'value',
    'color' => 'default',
    'href' => null,
])

@php
    $valueColors = [
        'success' => 'text-green-600',
        'danger' => 'text-red-600',
        'warning' => 'text-yellow-600',
        'info' => 'text-blue-600',
        'default' => 'text-gray-900',
    ];

    $iconColors = [
        'success' => 'bg-green-100 text-green-600',
        'danger' => 'bg-red-100 text-red-600',
        'warning' => 'bg-yellow-100 text-yellow-700',
        'info' => 'bg-blue-100 text-blue-600',
        'default' => 'bg-gray-100 text-gray-600',
    ];

    $valueColorClass = $valueColors[$color] ?? $valueColors['default'];
    $iconColorClass = $iconColors[$color] ?? $iconColors['default'];

    $hasIcon = trim($slot) !== '';
@endphp

@if($href)
    <a
        href="{{ $href }}"
        class="block transition hover:-translate-y-0.5"
    >
@endif

<x-ui.card>
    <div class="flex items-center justify-between gap-4">
        <div class="space-y-2">
            <p class="text-sm font-medium text-gray-500">
                {{ $title }}
            </p>

            <p class="text-3xl font-bold {{ $valueColorClass }}">
                {{ $value }}
            </p>
        </div>

        @if($hasIcon)
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full {{ $iconColorClass }}">
                {{ $slot }}
            </div>
        @endif
    </div>
</x-ui.card>

@if($href)
    </a>
@endif