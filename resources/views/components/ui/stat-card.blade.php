@props([
    'label',
    'value',
    'description' => null,
    'tone' => 'neutral',
])

@php
    $tones = [
        'neutral' => 'border-zinc-200 bg-white dark:border-white/10 dark:bg-zinc-900',
        'teal' => 'border-teal-200 bg-teal-50 dark:border-teal-400/20 dark:bg-teal-400/10',
        'amber' => 'border-amber-200 bg-amber-50 dark:border-amber-400/20 dark:bg-amber-400/10',
        'rose' => 'border-rose-200 bg-rose-50 dark:border-rose-400/20 dark:bg-rose-400/10',
    ];
@endphp

<article {{ $attributes->class('rounded-lg border p-5 shadow-sm '.($tones[$tone] ?? $tones['neutral'])) }}>
    <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ $label }}</p>
    <p class="mt-3 text-3xl font-semibold text-zinc-950 dark:text-white">{{ $value }}</p>

    @if ($description)
        <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $description }}</p>
    @endif
</article>
