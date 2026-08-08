<section class="w-full space-y-8">
    <x-ui.section-heading
        :title="__('Product reviews')"
        :description="__('Review items from your orders and track moderation status.')"
    >
        <x-slot:actions>
            <x-ui.button :href="route('account.orders')" variant="secondary" wire:navigate>{{ __('Order history') }}</x-ui.button>
            <x-ui.button :href="route('dashboard')" variant="subtle" wire:navigate>{{ __('Dashboard') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.section-heading>

    @if (session('status'))
        <div class="rounded-lg border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-medium text-teal-800 dark:border-teal-400/20 dark:bg-teal-400/10 dark:text-teal-100">
            {{ session('status') }}
        </div>
    @endif

    @error('reviews')
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 dark:border-rose-400/20 dark:bg-rose-400/10 dark:text-rose-100">
            {{ $message }}
        </div>
    @enderror

    <div class="grid gap-6 xl:grid-cols-[1fr_24rem] xl:items-start">
        <section class="space-y-6">
            <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Purchased products') }}</h2>
                <div class="mt-5 grid gap-3">
                    @forelse ($purchasedItems as $item)
                        <div class="flex flex-col gap-3 rounded-lg border border-zinc-200 p-4 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold text-zinc-950 dark:text-white">{{ $item->product?->name ?? $item->product_name }}</p>
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $item->variant_name ?: $item->productVariant?->option_label }}</p>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Order :number', ['number' => $item->order?->order_number]) }}</p>
                            </div>
                            <x-ui.button type="button" variant="secondary" size="sm" wire:click="startFromOrderItem({{ $item->id }})">{{ __('Review') }}</x-ui.button>
                        </div>
                    @empty
                        <x-ui.empty-state
                            :title="__('No purchased products yet')"
                            :description="__('Products from your account orders will appear here for review.')"
                        />
                    @endforelse
                </div>
            </article>

            <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Your reviews') }}</h2>
                <div class="mt-5 grid gap-4">
                    @forelse ($reviews as $review)
                        <div class="rounded-lg border border-zinc-200 p-4 dark:border-white/10">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold text-zinc-950 dark:text-white">{{ $review->product?->name ?? __('Product') }}</p>
                                        <x-ui.badge tone="{{ $review->status === \App\Enums\ReviewStatus::Approved ? 'teal' : ($review->status === \App\Enums\ReviewStatus::Rejected ? 'rose' : 'amber') }}">
                                            {{ str($review->status->value)->title() }}
                                        </x-ui.badge>
                                        @if ($review->is_verified_purchase)
                                            <x-ui.badge>{{ __('Verified purchase') }}</x-ui.badge>
                                        @endif
                                    </div>
                                    <p class="mt-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __(':rating/5', ['rating' => $review->rating]) }} @if ($review->title) · {{ $review->title }} @endif</p>
                                    <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $review->body }}</p>
                                </div>
                                <div class="flex flex-wrap gap-2 sm:justify-end">
                                    <x-ui.button type="button" size="sm" variant="secondary" wire:click="edit({{ $review->id }})">{{ __('Edit') }}</x-ui.button>
                                    <x-ui.button type="button" size="sm" variant="danger" wire:click="delete({{ $review->id }})" wire:confirm="{{ __('Remove this review?') }}">{{ __('Delete') }}</x-ui.button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('Submitted reviews will appear here.') }}</p>
                    @endforelse
                </div>
            </article>
        </section>

        <form wire:submit="save" class="space-y-5 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
            <div>
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $editingReviewId ? __('Edit review') : __('Write review') }}</h2>
                @php($selectedItem = $purchasedItems->firstWhere('product_id', $form['product_id']))
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                    @if ($selectedItem)
                        {{ __('Selected: :product', ['product' => $selectedItem->product?->name ?? $selectedItem->product_name]) }}
                    @else
                        {{ __('Choose a purchased product to begin.') }}
                    @endif
                </p>
            </div>

            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                {{ __('Rating') }}
                <select wire:model="form.rating" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                    @for ($rating = 5; $rating >= 1; $rating--)
                        <option value="{{ $rating }}">{{ __(':rating stars', ['rating' => $rating]) }}</option>
                    @endfor
                </select>
                <x-ui.input-error for="form.rating" />
            </label>

            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                {{ __('Title') }}
                <input wire:model="form.title" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                <x-ui.input-error for="form.title" />
            </label>

            <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                {{ __('Review') }}
                <textarea wire:model="form.body" rows="6" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"></textarea>
                <x-ui.input-error for="form.body" />
            </label>

            <x-ui.input-error for="form.product_id" />
            <x-ui.input-error for="form.order_id" />

            <div class="flex justify-end">
                <x-ui.button type="submit" :disabled="$form['product_id'] === null">{{ __('Submit review') }}</x-ui.button>
            </div>
        </form>
    </div>
</section>
