<?php

namespace App\Livewire\Account;

use App\Enums\ReviewStatus;
use App\Models\OrderItem;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ReviewManager extends Component
{
    public ?int $editingReviewId = null;

    /**
     * @var array{product_id: int|null, product_variant_id: int|null, order_id: int|null, rating: int, title: string, body: string}
     */
    public array $form = [
        'product_id' => null,
        'product_variant_id' => null,
        'order_id' => null,
        'rating' => 5,
        'title' => '',
        'body' => '',
    ];

    public function startFromOrderItem(int $orderItemId): void
    {
        $item = $this->orderItemForUser($orderItemId);

        if ($item->product_id === null) {
            $this->addError('reviews', __('This item is no longer available for review.'));

            return;
        }

        $existingReview = $this->user()
            ->productReviews()
            ->where('product_id', $item->product_id)
            ->first();

        if ($existingReview instanceof ProductReview) {
            $this->fillFromReview($existingReview);

            return;
        }

        $this->editingReviewId = null;
        $this->form = [
            'product_id' => $item->product_id,
            'product_variant_id' => $item->product_variant_id,
            'order_id' => $item->order_id,
            'rating' => 5,
            'title' => '',
            'body' => '',
        ];
        $this->resetValidation();
    }

    public function edit(int $reviewId): void
    {
        $this->fillFromReview($this->reviewForUser($reviewId));
    }

    public function save(): void
    {
        $validated = $this->validate();
        $this->ensurePurchasedProduct($validated['form']);

        $review = ProductReview::query()->updateOrCreate(
            [
                'product_id' => $validated['form']['product_id'],
                'user_id' => $this->user()->id,
            ],
            [
                'product_variant_id' => $validated['form']['product_variant_id'],
                'order_id' => $validated['form']['order_id'],
                'rating' => $validated['form']['rating'],
                'title' => $this->nullableValue($validated['form']['title']),
                'body' => $validated['form']['body'],
                'status' => ReviewStatus::Pending,
                'is_verified_purchase' => true,
                'approved_at' => null,
            ],
        );

        $this->editingReviewId = $review->id;
        session()->flash('status', __('Review submitted for moderation.'));
    }

    public function delete(int $reviewId): void
    {
        $this->reviewForUser($reviewId)->delete();

        if ($this->editingReviewId === $reviewId) {
            $this->resetForm();
        }

        session()->flash('status', __('Review removed.'));
    }

    public function render(): View
    {
        return view('livewire.account.review-manager', [
            'reviews' => $this->user()
                ->productReviews()
                ->with(['product.images', 'productVariant', 'order'])
                ->latest()
                ->get(),
            'purchasedItems' => $this->purchasedItems(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'form.product_id' => ['required', 'integer', 'exists:products,id'],
            'form.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'form.order_id' => ['required', 'integer', 'exists:orders,id'],
            'form.rating' => ['required', 'integer', 'min:1', 'max:5'],
            'form.title' => ['nullable', 'string', 'max:120'],
            'form.body' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    protected function fillFromReview(ProductReview $review): void
    {
        $this->editingReviewId = $review->id;
        $this->form = [
            'product_id' => $review->product_id,
            'product_variant_id' => $review->product_variant_id,
            'order_id' => $review->order_id,
            'rating' => $review->rating,
            'title' => (string) $review->title,
            'body' => $review->body,
        ];
        $this->resetValidation();
    }

    /**
     * @return Collection<int, OrderItem>
     */
    protected function purchasedItems(): Collection
    {
        return OrderItem::query()
            ->with(['order', 'product.images', 'productVariant'])
            ->whereNotNull('product_id')
            ->whereHas('order', fn (Builder $query) => $query->where('user_id', $this->user()->id))
            ->latest('id')
            ->get()
            ->filter(fn (OrderItem $item): bool => $item->product !== null)
            ->unique('product_id')
            ->values();
    }

    protected function orderItemForUser(int $orderItemId): OrderItem
    {
        return OrderItem::query()
            ->with(['order', 'product'])
            ->whereKey($orderItemId)
            ->whereHas('order', fn (Builder $query) => $query->where('user_id', $this->user()->id))
            ->firstOrFail();
    }

    protected function reviewForUser(int $reviewId): ProductReview
    {
        return $this->user()
            ->productReviews()
            ->whereKey($reviewId)
            ->firstOrFail();
    }

    /**
     * @param  array{product_id: int, product_variant_id: int|null, order_id: int, rating: int, title: string, body: string}  $form
     */
    protected function ensurePurchasedProduct(array $form): void
    {
        $hasPurchasedProduct = OrderItem::query()
            ->where('order_id', $form['order_id'])
            ->where('product_id', $form['product_id'])
            ->when($form['product_variant_id'] !== null, fn (Builder $query) => $query->where('product_variant_id', $form['product_variant_id']))
            ->whereHas('order', fn (Builder $query) => $query->where('user_id', $this->user()->id))
            ->exists();

        if (! $hasPurchasedProduct) {
            throw ValidationException::withMessages([
                'reviews' => __('You can review only products from your own orders.'),
            ]);
        }
    }

    protected function resetForm(): void
    {
        $this->editingReviewId = null;
        $this->form = [
            'product_id' => null,
            'product_variant_id' => null,
            'order_id' => null,
            'rating' => 5,
            'title' => '',
            'body' => '',
        ];
        $this->resetValidation();
    }

    protected function nullableValue(string $value): ?string
    {
        return trim($value) === '' ? null : $value;
    }

    protected function user(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
