@props([
    'overline' => null,
    'title',
    'description' => null,
])

<div {{ $attributes->class('flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between') }}>
    <div class="max-w-2xl">
        @if ($overline)
            <p class="text-xs font-semibold uppercase tracking-normal text-teal-700 dark:text-teal-300">{{ $overline }}</p>
        @endif

        <h2 class="mt-2 text-2xl font-semibold text-zinc-950 dark:text-white">{{ $title }}</h2>

        @if ($description)
            <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
