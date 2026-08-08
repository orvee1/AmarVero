@props([
    'tone' => 'neutral',
])

@php
    $tones = [
        'neutral' => 'border-zinc-200 bg-white text-zinc-700 dark:border-white/10 dark:bg-white/5 dark:text-zinc-200',
        'teal' => 'border-teal-200 bg-teal-50 text-teal-800 dark:border-teal-400/20 dark:bg-teal-400/10 dark:text-teal-100',
        'amber' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-100',
        'rose' => 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-400/20 dark:bg-rose-400/10 dark:text-rose-100',
    ];
@endphp

<span {{ $attributes->class('inline-flex min-h-6 items-center rounded-full border px-2.5 py-0.5 text-xs font-medium '.($tones[$tone] ?? $tones['neutral'])) }}>
    {{ $slot }}
</span>
