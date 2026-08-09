<x-layouts.storefront :title="$page->seo_title ?: $page->title" :seo="$seo">
    <article class="bg-white dark:bg-zinc-950">
        <x-ui.container class="max-w-3xl py-12 lg:py-16">
            <nav class="text-sm text-zinc-500 dark:text-zinc-400" aria-label="{{ __('Breadcrumbs') }}">
                <a class="hover:text-zinc-950 dark:hover:text-white" href="{{ route('home') }}">{{ __('Home') }}</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">{{ $page->title }}</span>
            </nav>

            <h1 class="mt-6 text-4xl font-semibold leading-tight text-zinc-950 dark:text-white">{{ $page->title }}</h1>

            <div class="mt-8 space-y-5 text-base leading-8 text-zinc-700 dark:text-zinc-200">
                @foreach (preg_split('/\R{2,}/', $page->body) ?: [] as $paragraph)
                    @if (trim($paragraph) !== '')
                        <p>{{ trim($paragraph) }}</p>
                    @endif
                @endforeach
            </div>
        </x-ui.container>
    </article>
</x-layouts.storefront>
