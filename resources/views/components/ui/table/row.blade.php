<div {{ $attributes->merge([
    'class' => 'flex items-center justify-between py-6 border-b border-gray-100 hover:bg-gray-50'
]) }}>
    {{ $slot }}
</div>
