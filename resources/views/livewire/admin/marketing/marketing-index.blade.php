<section class="space-y-6">
    <x-ui.section-heading
        :overline="__('Marketing')"
        :title="__('Campaigns and coupons')"
        :description="__('Manage campaign calendars, promo codes, coupon usage, subscriber status, and featured merchandising from one admin surface.')"
    >
        <x-slot:actions>
            @can('create', App\Models\Campaign::class)
                <x-ui.button wire:click="createCampaign">{{ __('New campaign') }}</x-ui.button>
            @endcan

            @can('create', App\Models\Coupon::class)
                <x-ui.button variant="secondary" wire:click="createCoupon">{{ __('New coupon') }}</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.section-heading>

    <div class="flex flex-wrap gap-2 rounded-lg border border-zinc-200 bg-white p-3 dark:border-white/10 dark:bg-zinc-900">
        <button type="button" wire:click="$set('panel', 'campaigns')" class="min-h-10 rounded-lg px-4 text-sm font-semibold {{ $panel === 'campaigns' ? 'bg-zinc-950 text-white dark:bg-white dark:text-zinc-950' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/10' }}">{{ __('Campaigns') }}</button>
        <button type="button" wire:click="$set('panel', 'coupons')" class="min-h-10 rounded-lg px-4 text-sm font-semibold {{ $panel === 'coupons' ? 'bg-zinc-950 text-white dark:bg-white dark:text-zinc-950' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/10' }}">{{ __('Coupons') }}</button>
        <button type="button" wire:click="$set('panel', 'newsletter')" class="min-h-10 rounded-lg px-4 text-sm font-semibold {{ $panel === 'newsletter' ? 'bg-zinc-950 text-white dark:bg-white dark:text-zinc-950' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/10' }}">{{ __('Newsletter') }}</button>
        <button type="button" wire:click="$set('panel', 'featured')" class="min-h-10 rounded-lg px-4 text-sm font-semibold {{ $panel === 'featured' ? 'bg-zinc-950 text-white dark:bg-white dark:text-zinc-950' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/10' }}">{{ __('Featured products') }}</button>
    </div>

    @if ($panel === 'campaigns')
        <div class="grid gap-6 xl:grid-cols-[minmax(360px,0.75fr)_minmax(0,1fr)]">
            @can('create', App\Models\Campaign::class)
                <form wire:submit="saveCampaign" class="space-y-4 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $editingCampaignId ? __('Edit campaign') : __('Create campaign') }}</h3>
                        <x-ui.badge>{{ __('Campaign') }}</x-ui.badge>
                    </div>

                    <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        {{ __('Name') }}
                        <input wire:model="campaignForm.name" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                        <x-ui.input-error for="campaignForm.name" />
                    </label>

                    <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        {{ __('Slug') }}
                        <input wire:model="campaignForm.slug" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                        <x-ui.input-error for="campaignForm.slug" />
                    </label>

                    <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        {{ __('Description') }}
                        <textarea wire:model="campaignForm.description" rows="3" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white"></textarea>
                        <x-ui.input-error for="campaignForm.description" />
                    </label>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Status') }}
                            <select wire:model="campaignForm.status" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}">{{ str($status->value)->replace('_', ' ')->title() }}</option>
                                @endforeach
                            </select>
                            <x-ui.input-error for="campaignForm.status" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Banner path') }}
                            <input wire:model="campaignForm.banner_path" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <x-ui.input-error for="campaignForm.banner_path" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Starts at') }}
                            <input wire:model="campaignForm.starts_at" type="datetime-local" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <x-ui.input-error for="campaignForm.starts_at" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Ends at') }}
                            <input wire:model="campaignForm.ends_at" type="datetime-local" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <x-ui.input-error for="campaignForm.ends_at" />
                        </label>
                    </div>

                    <div class="flex justify-end gap-2">
                        <x-ui.button type="button" variant="secondary" wire:click="createCampaign">{{ __('Reset') }}</x-ui.button>
                        <x-ui.button type="submit">{{ __('Save campaign') }}</x-ui.button>
                    </div>
                </form>
            @endcan

            <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
                @if ($campaigns->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-white/10">
                            <thead class="bg-zinc-50 text-left text-xs uppercase text-zinc-500 dark:bg-white/5 dark:text-zinc-400">
                                <tr>
                                    <th scope="col" class="px-4 py-3 font-semibold">{{ __('Campaign') }}</th>
                                    <th scope="col" class="px-4 py-3 font-semibold">{{ __('Status') }}</th>
                                    <th scope="col" class="px-4 py-3 font-semibold">{{ __('Coupons') }}</th>
                                    <th scope="col" class="px-4 py-3 text-right font-semibold">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-white/10">
                                @foreach ($campaigns as $campaign)
                                    <tr wire:key="campaign-{{ $campaign->id }}">
                                        <td class="px-4 py-4 align-top">
                                            <div class="font-semibold text-zinc-950 dark:text-white">{{ $campaign->name }}</div>
                                            <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $campaign->slug }}</div>
                                            <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $campaign->starts_at?->format('M j, Y H:i') ?? __('No start') }} - {{ $campaign->ends_at?->format('M j, Y H:i') ?? __('No end') }}</div>
                                        </td>
                                        <td class="px-4 py-4 align-top">
                                            <x-ui.badge :tone="$campaign->status === App\Enums\ContentStatus::Published ? 'teal' : ($campaign->status === App\Enums\ContentStatus::Scheduled ? 'amber' : 'neutral')">
                                                {{ str($campaign->status->value)->replace('_', ' ')->title() }}
                                            </x-ui.badge>
                                        </td>
                                        <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">{{ number_format($campaign->coupons_count) }}</td>
                                        <td class="px-4 py-4 align-top">
                                            <div class="flex justify-end gap-2">
                                                @can('update', $campaign)
                                                    <x-ui.button size="sm" variant="secondary" wire:click="editCampaign({{ $campaign->id }})">{{ __('Edit') }}</x-ui.button>
                                                @endcan
                                                @can('delete', $campaign)
                                                    <x-ui.button size="sm" variant="danger" wire:click="deleteCampaign({{ $campaign->id }})" wire:confirm="{{ __('Delete this campaign?') }}">{{ __('Delete') }}</x-ui.button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-ui.empty-state :title="__('No campaigns')" :description="__('Create a campaign to group seasonal coupons and merchandising.')"/>
                @endif
            </div>
        </div>
    @elseif ($panel === 'coupons')
        <div class="grid gap-6 xl:grid-cols-[minmax(360px,0.75fr)_minmax(0,1fr)]">
            @can('create', App\Models\Coupon::class)
                <form wire:submit="saveCoupon" class="space-y-4 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $editingCouponId ? __('Edit coupon') : __('Create coupon') }}</h3>
                        <x-ui.badge>{{ __('Promo') }}</x-ui.badge>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Code') }}
                            <input wire:model="couponForm.code" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm uppercase text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <x-ui.input-error for="couponForm.code" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Name') }}
                            <input wire:model="couponForm.name" type="text" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <x-ui.input-error for="couponForm.name" />
                        </label>
                    </div>

                    <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        {{ __('Campaign') }}
                        <select wire:model="couponForm.campaign_id" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <option value="">{{ __('No campaign') }}</option>
                            @foreach ($campaigns as $campaign)
                                <option value="{{ $campaign->id }}">{{ $campaign->name }}</option>
                            @endforeach
                        </select>
                        <x-ui.input-error for="couponForm.campaign_id" />
                    </label>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Type') }}
                            <select wire:model="couponForm.type" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                @foreach ($couponTypes as $type)
                                    <option value="{{ $type->value }}">{{ str($type->value)->replace('_', ' ')->title() }}</option>
                                @endforeach
                            </select>
                            <x-ui.input-error for="couponForm.type" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Discount') }}
                            <select wire:model="couponForm.discount_type" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                                @foreach ($discountTypes as $type)
                                    <option value="{{ $type->value }}">{{ str($type->value)->replace('_', ' ')->title() }}</option>
                                @endforeach
                            </select>
                            <x-ui.input-error for="couponForm.discount_type" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Value') }}
                            <input wire:model="couponForm.value" type="number" min="0" step="0.01" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <x-ui.input-error for="couponForm.value" />
                        </label>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Minimum order') }}
                            <input wire:model="couponForm.minimum_order_amount" type="number" min="0" step="0.01" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <x-ui.input-error for="couponForm.minimum_order_amount" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Maximum discount') }}
                            <input wire:model="couponForm.maximum_discount_amount" type="number" min="0" step="0.01" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <x-ui.input-error for="couponForm.maximum_discount_amount" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Starts at') }}
                            <input wire:model="couponForm.starts_at" type="datetime-local" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <x-ui.input-error for="couponForm.starts_at" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Ends at') }}
                            <input wire:model="couponForm.ends_at" type="datetime-local" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <x-ui.input-error for="couponForm.ends_at" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Total usage limit') }}
                            <input wire:model="couponForm.total_usage_limit" type="number" min="1" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <x-ui.input-error for="couponForm.total_usage_limit" />
                        </label>

                        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Per-customer limit') }}
                            <input wire:model="couponForm.per_customer_usage_limit" type="number" min="1" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
                            <x-ui.input-error for="couponForm.per_customer_usage_limit" />
                        </label>
                    </div>

                    <div class="flex flex-wrap gap-4">
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            <input wire:model="couponForm.first_order_only" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                            {{ __('First order only') }}
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            <input wire:model="couponForm.is_active" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                            {{ __('Active') }}
                        </label>
                    </div>

                    <div class="flex justify-end gap-2">
                        <x-ui.button type="button" variant="secondary" wire:click="createCoupon">{{ __('Reset') }}</x-ui.button>
                        <x-ui.button type="submit">{{ __('Save coupon') }}</x-ui.button>
                    </div>
                </form>
            @endcan

            <div class="space-y-4">
                <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
                    @if ($coupons->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-white/10">
                                <thead class="bg-zinc-50 text-left text-xs uppercase text-zinc-500 dark:bg-white/5 dark:text-zinc-400">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 font-semibold">{{ __('Coupon') }}</th>
                                        <th scope="col" class="px-4 py-3 font-semibold">{{ __('Value') }}</th>
                                        <th scope="col" class="px-4 py-3 font-semibold">{{ __('Usage') }}</th>
                                        <th scope="col" class="px-4 py-3 text-right font-semibold">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 dark:divide-white/10">
                                    @foreach ($coupons as $coupon)
                                        <tr wire:key="coupon-{{ $coupon->id }}">
                                            <td class="px-4 py-4 align-top">
                                                <div class="font-semibold text-zinc-950 dark:text-white">{{ $coupon->code }}</div>
                                                <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $coupon->name }} / {{ $coupon->campaign?->name ?? __('No campaign') }}</div>
                                                <div class="mt-2">
                                                    <x-ui.badge :tone="$coupon->is_active ? 'teal' : 'neutral'">{{ $coupon->is_active ? __('Active') : __('Inactive') }}</x-ui.badge>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                                                {{ str($coupon->discount_type->value)->replace('_', ' ')->title() }} / {{ number_format((float) $coupon->value, 2) }}
                                            </td>
                                            <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                                                {{ number_format($coupon->usage_count) }}
                                                <span class="text-xs text-zinc-500 dark:text-zinc-400">/ {{ $coupon->total_usage_limit ? number_format($coupon->total_usage_limit) : __('Unlimited') }}</span>
                                            </td>
                                            <td class="px-4 py-4 align-top">
                                                <div class="flex justify-end gap-2">
                                                    @can('update', $coupon)
                                                        <x-ui.button size="sm" variant="secondary" wire:click="editCoupon({{ $coupon->id }})">{{ __('Edit') }}</x-ui.button>
                                                    @endcan
                                                    @can('delete', $coupon)
                                                        <x-ui.button size="sm" variant="danger" wire:click="deleteCoupon({{ $coupon->id }})" wire:confirm="{{ __('Delete this coupon?') }}">{{ __('Delete') }}</x-ui.button>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <x-ui.empty-state :title="__('No coupons')" :description="__('Create a coupon for cart discounts, product discounts, or free shipping.')" />
                    @endif
                </div>

                <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                    <h3 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Recent redemptions') }}</h3>
                    <div class="mt-4 space-y-3 text-sm">
                        @forelse ($couponRedemptions as $coupon)
                            @foreach ($coupon->redemptions as $redemption)
                                <div class="flex items-center justify-between gap-3 rounded-lg bg-zinc-50 p-3 dark:bg-white/5" wire:key="redemption-{{ $redemption->id }}">
                                    <div>
                                        <div class="font-medium text-zinc-950 dark:text-white">{{ $coupon->code }}</div>
                                        <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $redemption->user?->email ?? $redemption->order?->email ?? __('Guest') }} / {{ $redemption->redeemed_at?->format('M j, Y H:i') }}</div>
                                    </div>
                                    <span class="font-semibold text-zinc-950 dark:text-white">BDT {{ number_format((float) $redemption->discount_amount, 2) }}</span>
                                </div>
                            @endforeach
                        @empty
                            <p class="text-zinc-500 dark:text-zinc-400">{{ __('No redemptions yet.') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @elseif ($panel === 'newsletter')
        <div class="space-y-4">
            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-zinc-900">
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Search subscribers') }}
                    <input type="search" wire:model.live.debounce.300ms="subscriberSearch" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white" placeholder="{{ __('Email or name') }}">
                </label>
            </div>

            <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
                @if ($subscribers->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-white/10">
                            <thead class="bg-zinc-50 text-left text-xs uppercase text-zinc-500 dark:bg-white/5 dark:text-zinc-400">
                                <tr>
                                    <th scope="col" class="px-4 py-3 font-semibold">{{ __('Subscriber') }}</th>
                                    <th scope="col" class="px-4 py-3 font-semibold">{{ __('Status') }}</th>
                                    <th scope="col" class="px-4 py-3 font-semibold">{{ __('Dates') }}</th>
                                    <th scope="col" class="px-4 py-3 text-right font-semibold">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-white/10">
                                @foreach ($subscribers as $subscriber)
                                    <tr wire:key="subscriber-{{ $subscriber->id }}">
                                        <td class="px-4 py-4 align-top">
                                            <div class="font-semibold text-zinc-950 dark:text-white">{{ $subscriber->email }}</div>
                                            <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $subscriber->name ?: __('No name') }}</div>
                                        </td>
                                        <td class="px-4 py-4 align-top">
                                            <x-ui.badge :tone="$subscriber->status === 'subscribed' ? 'teal' : ($subscriber->status === 'bounced' ? 'rose' : 'neutral')">{{ str($subscriber->status)->title() }}</x-ui.badge>
                                        </td>
                                        <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                                            <span class="block">{{ __('Joined') }}: {{ $subscriber->subscribed_at?->format('M j, Y') ?? __('Unknown') }}</span>
                                            <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ __('Unsubscribed') }}: {{ $subscriber->unsubscribed_at?->format('M j, Y') ?? __('No') }}</span>
                                        </td>
                                        <td class="px-4 py-4 align-top">
                                            <div class="flex flex-wrap justify-end gap-2">
                                                @can('update', $subscriber)
                                                    <x-ui.button size="sm" variant="secondary" wire:click="updateSubscriberStatus({{ $subscriber->id }}, 'subscribed')">{{ __('Subscribe') }}</x-ui.button>
                                                    <x-ui.button size="sm" variant="secondary" wire:click="updateSubscriberStatus({{ $subscriber->id }}, 'unsubscribed')">{{ __('Unsubscribe') }}</x-ui.button>
                                                    <x-ui.button size="sm" variant="secondary" wire:click="updateSubscriberStatus({{ $subscriber->id }}, 'bounced')">{{ __('Bounce') }}</x-ui.button>
                                                @endcan
                                                @can('delete', $subscriber)
                                                    <x-ui.button size="sm" variant="danger" wire:click="deleteSubscriber({{ $subscriber->id }})" wire:confirm="{{ __('Delete this subscriber?') }}">{{ __('Delete') }}</x-ui.button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-ui.empty-state :title="__('No subscribers')" :description="__('Newsletter subscribers will appear here as customers opt in.')" />
                @endif
            </div>
        </div>
    @else
        <div class="space-y-4">
            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-zinc-900">
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    {{ __('Search products') }}
                    <input type="search" wire:model.live.debounce.300ms="productSearch" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white" placeholder="{{ __('Name or SKU') }}">
                </label>
            </div>

            <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
                @if ($featuredProducts->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-white/10">
                            <thead class="bg-zinc-50 text-left text-xs uppercase text-zinc-500 dark:bg-white/5 dark:text-zinc-400">
                                <tr>
                                    <th scope="col" class="px-4 py-3 font-semibold">{{ __('Product') }}</th>
                                    <th scope="col" class="px-4 py-3 font-semibold">{{ __('Status') }}</th>
                                    <th scope="col" class="px-4 py-3 font-semibold">{{ __('Price') }}</th>
                                    <th scope="col" class="px-4 py-3 text-right font-semibold">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-white/10">
                                @foreach ($featuredProducts as $product)
                                    <tr wire:key="featured-product-{{ $product->id }}">
                                        <td class="px-4 py-4 align-top">
                                            <div class="font-semibold text-zinc-950 dark:text-white">{{ $product->name }}</div>
                                            <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $product->base_sku ?: $product->slug }}</div>
                                        </td>
                                        <td class="px-4 py-4 align-top">
                                            <x-ui.badge :tone="$product->is_featured ? 'teal' : 'neutral'">{{ $product->is_featured ? __('Featured') : __('Standard') }}</x-ui.badge>
                                        </td>
                                        <td class="px-4 py-4 align-top text-zinc-600 dark:text-zinc-300">
                                            BDT {{ number_format((float) ($product->sale_price ?: $product->regular_price), 2) }}
                                        </td>
                                        <td class="px-4 py-4 align-top">
                                            <div class="flex justify-end">
                                                @can('manage', App\Models\Campaign::class)
                                                    <x-ui.button size="sm" variant="secondary" wire:click="toggleFeaturedProduct({{ $product->id }})">
                                                        {{ $product->is_featured ? __('Remove featured') : __('Feature') }}
                                                    </x-ui.button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-ui.empty-state :title="__('No products found')" :description="__('Published products will appear here for featured merchandising.')" />
                @endif
            </div>
        </div>
    @endif
</section>
