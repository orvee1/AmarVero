@props([
    'label',
    'scrollHint' => null,
])

<div {{ $attributes->class('overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900') }}>
    @if ($scrollHint)
        <p class="border-b border-zinc-200 px-4 py-2 text-xs font-medium text-zinc-500 md:hidden dark:border-white/10 dark:text-zinc-400">
            {{ $scrollHint }}
        </p>
    @endif

    <div
        role="region"
        aria-label="{{ $label }}"
        tabindex="0"
        class="overflow-x-auto focus-visible:outline-2 focus-visible:outline-inset focus-visible:outline-teal-500"
    >
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="border-t border-zinc-200 px-4 py-3 dark:border-white/10">
            {{ $footer }}
        </div>
    @endisset
</div>
