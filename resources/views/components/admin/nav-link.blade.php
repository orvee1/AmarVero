@props([
    'href',
    'active' => false,
])

<a
    href="{{ $href }}"
    @class([
        'flex min-h-10 items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-500',
        'bg-zinc-950 text-white shadow-sm dark:bg-white dark:text-zinc-950' => $active,
        'text-zinc-700 hover:bg-zinc-100 hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-white/10 dark:hover:text-white' => ! $active,
    ])
>
    {{ $slot }}
</a>
