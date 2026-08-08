@props([
    'for',
])

@error($for)
    <p {{ $attributes->class('mt-1 text-sm text-rose-600 dark:text-rose-300') }}>{{ $message }}</p>
@enderror
