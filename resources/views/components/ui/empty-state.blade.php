@props([
    'title',
    'description' => null,
])

<div {{ $attributes->class('rounded-lg border border-dashed border-zinc-300 bg-white px-6 py-10 text-center dark:border-white/15 dark:bg-zinc-900') }}>
    <h3 class="text-sm font-semibold text-zinc-950 dark:text-white">{{ $title }}</h3>

    @if ($description)
        <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $description }}</p>
    @endif

    @isset($action)
        <div class="mt-5">
            {{ $action }}
        </div>
    @endisset
</div>
