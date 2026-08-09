<?php

namespace App\Livewire\Admin\Marketing;

use App\Enums\ContentStatus;
use App\Enums\CouponType;
use App\Enums\DiscountType;
use App\Models\Campaign;
use App\Models\Coupon;
use App\Models\NewsletterSubscriber;
use App\Models\Product;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class MarketingIndex extends Component
{
    public string $panel = 'campaigns';

    public string $productSearch = '';

    public string $subscriberSearch = '';

    public ?int $editingCampaignId = null;

    public ?int $editingCouponId = null;

    /**
     * @var array{name: string, slug: string, description: string, status: string, starts_at: string, ends_at: string, banner_path: string}
     */
    public array $campaignForm = [
        'name' => '',
        'slug' => '',
        'description' => '',
        'status' => 'draft',
        'starts_at' => '',
        'ends_at' => '',
        'banner_path' => '',
    ];

    /**
     * @var array{campaign_id: string, code: string, name: string, type: string, discount_type: string, value: string, minimum_order_amount: string, maximum_discount_amount: string, starts_at: string, ends_at: string, total_usage_limit: string, per_customer_usage_limit: string, first_order_only: bool, is_active: bool}
     */
    public array $couponForm = [
        'campaign_id' => '',
        'code' => '',
        'name' => '',
        'type' => 'cart',
        'discount_type' => 'fixed',
        'value' => '',
        'minimum_order_amount' => '',
        'maximum_discount_amount' => '',
        'starts_at' => '',
        'ends_at' => '',
        'total_usage_limit' => '',
        'per_customer_usage_limit' => '',
        'first_order_only' => false,
        'is_active' => true,
    ];

    public function mount(): void
    {
        abort_unless(
            Gate::allows('viewAny', Campaign::class)
            || Gate::allows('viewAny', Coupon::class)
            || Gate::allows('viewAny', NewsletterSubscriber::class),
            403,
        );

        $this->resetCampaignForm();
        $this->resetCouponForm();
    }

    public function createCampaign(): void
    {
        Gate::authorize('create', Campaign::class);

        $this->resetCampaignForm();
        $this->panel = 'campaigns';
    }

    public function editCampaign(int $campaignId): void
    {
        $campaign = Campaign::query()->findOrFail($campaignId);

        Gate::authorize('update', $campaign);

        $this->editingCampaignId = $campaign->id;
        $this->campaignForm = [
            'name' => $campaign->name,
            'slug' => $campaign->slug,
            'description' => (string) $campaign->description,
            'status' => $this->contentStatus($campaign)->value,
            'starts_at' => $this->dateTimeInput($campaign->starts_at),
            'ends_at' => $this->dateTimeInput($campaign->ends_at),
            'banner_path' => (string) $campaign->banner_path,
        ];
        $this->panel = 'campaigns';
    }

    public function saveCampaign(): void
    {
        $campaign = $this->editingCampaignId
            ? Campaign::query()->findOrFail($this->editingCampaignId)
            : new Campaign;

        Gate::authorize($campaign->exists ? 'update' : 'create', $campaign->exists ? $campaign : Campaign::class);

        $validated = $this->validate($this->campaignRules())['campaignForm'];

        $campaign->forceFill([
            'name' => trim($validated['name']),
            'slug' => $this->normalizedSlug($validated['slug'] ?? null, $validated['name']),
            'description' => $this->nullableString($validated['description'] ?? null),
            'status' => ContentStatus::from($validated['status']),
            'starts_at' => $this->nullableDateTime($validated['starts_at'] ?? null),
            'ends_at' => $this->nullableDateTime($validated['ends_at'] ?? null),
            'banner_path' => $this->nullableString($validated['banner_path'] ?? null),
        ])->save();

        $this->resetCampaignForm();
        Flux::toast(variant: 'success', text: __('Campaign saved.'));
    }

    public function deleteCampaign(int $campaignId): void
    {
        $campaign = Campaign::query()->findOrFail($campaignId);

        Gate::authorize('delete', $campaign);

        $campaign->delete();
        $this->resetCampaignForm();
        Flux::toast(variant: 'success', text: __('Campaign deleted.'));
    }

    public function createCoupon(): void
    {
        Gate::authorize('create', Coupon::class);

        $this->resetCouponForm();
        $this->panel = 'coupons';
    }

    public function editCoupon(int $couponId): void
    {
        $coupon = Coupon::query()->findOrFail($couponId);

        Gate::authorize('update', $coupon);

        $this->editingCouponId = $coupon->id;
        $this->couponForm = [
            'campaign_id' => (string) $coupon->campaign_id,
            'code' => $coupon->code,
            'name' => $coupon->name,
            'type' => $this->couponType($coupon)->value,
            'discount_type' => $this->discountType($coupon)->value,
            'value' => (string) $coupon->value,
            'minimum_order_amount' => (string) $coupon->minimum_order_amount,
            'maximum_discount_amount' => (string) $coupon->maximum_discount_amount,
            'starts_at' => $this->dateTimeInput($coupon->starts_at),
            'ends_at' => $this->dateTimeInput($coupon->ends_at),
            'total_usage_limit' => (string) $coupon->total_usage_limit,
            'per_customer_usage_limit' => (string) $coupon->per_customer_usage_limit,
            'first_order_only' => $coupon->first_order_only,
            'is_active' => $coupon->is_active,
        ];
        $this->panel = 'coupons';
    }

    public function saveCoupon(): void
    {
        $coupon = $this->editingCouponId
            ? Coupon::query()->findOrFail($this->editingCouponId)
            : new Coupon;

        Gate::authorize($coupon->exists ? 'update' : 'create', $coupon->exists ? $coupon : Coupon::class);

        $validated = $this->validate($this->couponRules())['couponForm'];

        $coupon->forceFill([
            'campaign_id' => $this->nullableInteger($validated['campaign_id'] ?? null),
            'code' => strtoupper(trim($validated['code'])),
            'name' => trim($validated['name']),
            'type' => CouponType::from($validated['type']),
            'discount_type' => DiscountType::from($validated['discount_type']),
            'value' => $this->decimalString($validated['value']),
            'minimum_order_amount' => $this->nullableDecimal($validated['minimum_order_amount'] ?? null),
            'maximum_discount_amount' => $this->nullableDecimal($validated['maximum_discount_amount'] ?? null),
            'starts_at' => $this->nullableDateTime($validated['starts_at'] ?? null),
            'ends_at' => $this->nullableDateTime($validated['ends_at'] ?? null),
            'total_usage_limit' => $this->nullableInteger($validated['total_usage_limit'] ?? null),
            'per_customer_usage_limit' => $this->nullableInteger($validated['per_customer_usage_limit'] ?? null),
            'first_order_only' => (bool) $validated['first_order_only'],
            'is_active' => (bool) $validated['is_active'],
        ])->save();

        $this->resetCouponForm();
        Flux::toast(variant: 'success', text: __('Coupon saved.'));
    }

    public function deleteCoupon(int $couponId): void
    {
        $coupon = Coupon::query()->findOrFail($couponId);

        Gate::authorize('delete', $coupon);

        $coupon->delete();
        $this->resetCouponForm();
        Flux::toast(variant: 'success', text: __('Coupon deleted.'));
    }

    public function updateSubscriberStatus(int $subscriberId, string $status): void
    {
        $subscriber = NewsletterSubscriber::query()->findOrFail($subscriberId);

        Gate::authorize('update', $subscriber);

        abort_unless(in_array($status, ['subscribed', 'unsubscribed', 'bounced'], true), 422);

        $subscriber->forceFill([
            'status' => $status,
            'unsubscribed_at' => $status === 'unsubscribed' ? now() : null,
            'subscribed_at' => $status === 'subscribed' ? ($subscriber->subscribed_at ?? now()) : $subscriber->subscribed_at,
        ])->save();

        Flux::toast(variant: 'success', text: __('Subscriber updated.'));
    }

    public function deleteSubscriber(int $subscriberId): void
    {
        $subscriber = NewsletterSubscriber::query()->findOrFail($subscriberId);

        Gate::authorize('delete', $subscriber);

        $subscriber->delete();
        Flux::toast(variant: 'success', text: __('Subscriber deleted.'));
    }

    public function toggleFeaturedProduct(int $productId): void
    {
        Gate::authorize('manage', Campaign::class);

        $product = Product::query()->findOrFail($productId);

        $product->forceFill([
            'is_featured' => ! $product->is_featured,
        ])->save();

        Flux::toast(variant: 'success', text: __('Featured product updated.'));
    }

    public function render(): View
    {
        return view('livewire.admin.marketing.marketing-index', [
            'campaigns' => Campaign::query()->withCount('coupons')->latest()->get(),
            'coupons' => Coupon::query()->with(['campaign'])->withCount('redemptions')->latest()->get(),
            'couponRedemptions' => Coupon::query()->whereHas('redemptions')->with(['redemptions.order', 'redemptions.user'])->latest()->limit(10)->get(),
            'subscribers' => NewsletterSubscriber::query()
                ->when($this->subscriberSearch !== '', function (Builder $query): void {
                    $search = trim($this->subscriberSearch);

                    $query->where(function (Builder $query) use ($search): void {
                        $query
                            ->where('email', 'like', '%'.$search.'%')
                            ->orWhere('name', 'like', '%'.$search.'%');
                    });
                })
                ->latest()
                ->limit(25)
                ->get(),
            'featuredProducts' => Product::query()
                ->published()
                ->when($this->productSearch !== '', function (Builder $query): void {
                    $search = trim($this->productSearch);

                    $query->where(function (Builder $query) use ($search): void {
                        $query
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('base_sku', 'like', '%'.$search.'%');
                    });
                })
                ->orderByDesc('is_featured')
                ->latest()
                ->limit(20)
                ->get(),
            'statuses' => ContentStatus::cases(),
            'couponTypes' => CouponType::cases(),
            'discountTypes' => DiscountType::cases(),
        ])->layout('components.layouts.admin', [
            'title' => __('Marketing'),
            'breadcrumbs' => [
                __('Admin') => route('admin.dashboard'),
                __('Marketing') => null,
            ],
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function campaignRules(): array
    {
        return [
            'campaignForm.name' => ['required', 'string', 'max:255'],
            'campaignForm.slug' => ['nullable', 'string', 'max:255', Rule::unique('campaigns', 'slug')->ignore($this->editingCampaignId)],
            'campaignForm.description' => ['nullable', 'string', 'max:1000'],
            'campaignForm.status' => ['required', Rule::in(array_map(static fn (ContentStatus $status): string => $status->value, ContentStatus::cases()))],
            'campaignForm.starts_at' => ['nullable', 'date'],
            'campaignForm.ends_at' => ['nullable', 'date', 'after_or_equal:campaignForm.starts_at'],
            'campaignForm.banner_path' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function couponRules(): array
    {
        return [
            'couponForm.campaign_id' => ['nullable', 'integer', 'exists:campaigns,id'],
            'couponForm.code' => ['required', 'string', 'max:80', Rule::unique('coupons', 'code')->ignore($this->editingCouponId)],
            'couponForm.name' => ['required', 'string', 'max:255'],
            'couponForm.type' => ['required', Rule::in(array_map(static fn (CouponType $type): string => $type->value, CouponType::cases()))],
            'couponForm.discount_type' => ['required', Rule::in(array_map(static fn (DiscountType $type): string => $type->value, DiscountType::cases()))],
            'couponForm.value' => ['required', 'numeric', 'min:0'],
            'couponForm.minimum_order_amount' => ['nullable', 'numeric', 'min:0'],
            'couponForm.maximum_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'couponForm.starts_at' => ['nullable', 'date'],
            'couponForm.ends_at' => ['nullable', 'date', 'after_or_equal:couponForm.starts_at'],
            'couponForm.total_usage_limit' => ['nullable', 'integer', 'min:1'],
            'couponForm.per_customer_usage_limit' => ['nullable', 'integer', 'min:1'],
            'couponForm.first_order_only' => ['boolean'],
            'couponForm.is_active' => ['boolean'],
        ];
    }

    protected function resetCampaignForm(): void
    {
        $this->editingCampaignId = null;
        $this->campaignForm = [
            'name' => '',
            'slug' => '',
            'description' => '',
            'status' => ContentStatus::Draft->value,
            'starts_at' => '',
            'ends_at' => '',
            'banner_path' => '',
        ];
        $this->resetValidation();
    }

    protected function resetCouponForm(): void
    {
        $this->editingCouponId = null;
        $this->couponForm = [
            'campaign_id' => '',
            'code' => '',
            'name' => '',
            'type' => CouponType::Cart->value,
            'discount_type' => DiscountType::Fixed->value,
            'value' => '',
            'minimum_order_amount' => '',
            'maximum_discount_amount' => '',
            'starts_at' => '',
            'ends_at' => '',
            'total_usage_limit' => '',
            'per_customer_usage_limit' => '',
            'first_order_only' => false,
            'is_active' => true,
        ];
        $this->resetValidation();
    }

    protected function contentStatus(Campaign $campaign): ContentStatus
    {
        $status = $campaign->getAttribute('status');

        return $status instanceof ContentStatus ? $status : ContentStatus::from((string) $status);
    }

    protected function couponType(Coupon $coupon): CouponType
    {
        $type = $coupon->getAttribute('type');

        return $type instanceof CouponType ? $type : CouponType::from((string) $type);
    }

    protected function discountType(Coupon $coupon): DiscountType
    {
        $type = $coupon->getAttribute('discount_type');

        return $type instanceof DiscountType ? $type : DiscountType::from((string) $type);
    }

    protected function normalizedSlug(?string $slug, string $fallback): string
    {
        return Str::slug(filled($slug) ? $slug : $fallback);
    }

    protected function nullableString(?string $value): ?string
    {
        return filled($value) ? trim((string) $value) : null;
    }

    protected function nullableInteger(mixed $value): ?int
    {
        return filled($value) ? (int) $value : null;
    }

    protected function decimalString(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    protected function nullableDecimal(mixed $value): ?string
    {
        return filled($value) ? $this->decimalString($value) : null;
    }

    protected function nullableDateTime(?string $value): ?string
    {
        return filled($value) ? (string) $value : null;
    }

    protected function dateTimeInput(mixed $value): string
    {
        if (! $value instanceof \DateTimeInterface) {
            return '';
        }

        return $value->format('Y-m-d\TH:i');
    }
}
