@props([
    'size' => 'default',
])

@php
    $sizes = [
        'sm' => 'max-w-4xl',
        'default' => 'max-w-7xl',
        'wide' => 'max-w-[92rem]',
    ];
@endphp

<div {{ $attributes->class('mx-auto w-full px-4 sm:px-6 lg:px-8 '.($sizes[$size] ?? $sizes['default'])) }}>
    {{ $slot }}
</div>
