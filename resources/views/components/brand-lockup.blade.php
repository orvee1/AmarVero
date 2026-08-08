@props([
    'href' => null,
    'compact' => false,
])

@php
    $href ??= route('home');
@endphp

<a
    href="{{ $href }}"
    {{ $attributes->class('group inline-flex min-w-0 items-center gap-3 rounded-lg focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-teal-600') }}
>
    <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-zinc-950 text-base font-semibold text-white shadow-sm">
        A
    </span>

    @unless ($compact)
        <span class="grid min-w-0 leading-tight">
            <span class="truncate text-base font-semibold text-zinc-950 dark:text-white">{{ config('app.name', 'Amarvero') }}</span>
            <span class="truncate text-xs font-medium text-teal-700 dark:text-teal-300">{{ __('Footwear commerce') }}</span>
        </span>
    @endunless
</a>
