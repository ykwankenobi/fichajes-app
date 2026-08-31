<div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
    <div {{ $attributes->merge([
        'class' => 'max-w-xl'
    ]) }}>
        {{ $slot }}
    </div>
</div>