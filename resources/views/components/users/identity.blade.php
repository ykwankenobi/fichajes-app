@props([
    'user',
])

<div>
    <div class="font-medium text-gray-900">
        {{ $user->name }}
    </div>

    <div class="text-sm text-gray-500">
        {{ $user->email }}
    </div>
</div>
