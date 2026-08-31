@props(['class' => 'h-20 w-auto'])

@php
    $logo = config('branding.logo');
    $logoPath = $logo ? public_path($logo) : null;
@endphp

@if ($logoPath && file_exists($logoPath))
    <img
        src="{{ asset($logo) }}?v={{ filemtime($logoPath) }}"
        alt="{{ config('app.name') }}"
        {{ $attributes->except('class') }}
        class="{{ $class }} object-contain"
    >
@else
    <span {{ $attributes->except('class') }} class="{{ $class }} inline-flex items-center font-semibold">
        {{ config('app.name') }}
    </span>
@endif
