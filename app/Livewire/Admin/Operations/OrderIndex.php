<?php

namespace App\Livewire\Admin\Operations;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderNote;
use App\Models\User;
use App\Support\Admin\AdminOrderManager;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class OrderIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public string $paymentStatus = '';

    public ?int $selectedOrderId = null;

    /**
     * @var array{status: string, payment_status: string, note: string}
     */
    public array $form = [
        'status' => '',
        'payment_status' => '',
        'note' => '',
    ];

    public string $noteBody = '';

    public bool $noteVisibleToCustomer = false;

    public function mount(): void
    {
        Gate::authorize('viewAny', Order::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedPaymentStatus(): void
    {
        $this->resetPage();
    }

    public function selectOrder(int $orderId): void
    {
        $order = Order::query()->findOrFail($orderId);

        Gate::authorize('view', $order);

        $this->selectedOrderId = $order->id;
        $this->form = [
            'status' => $this->orderStatus($order)->value,
            'payment_status' => $this->paymentStatusForOrder($order)->value,
            'note' => '',
        ];
    }

    public function updateStatus(): void
    {
        $order = $this->selectedOrder();

        Gate::authorize('updateStatus', $order);

        $validated = $this->validate($this->statusRules())['form'];
        $orderManager = app(AdminOrderManager::class);

        $orderManager->updateOrderStatus(
            $order,
            OrderStatus::from($validated['status']),
            $this->user(),
            $this->nullableString($validated['note'] ?? null),
        );

        $this->selectOrder($order->id);
        Flux::toast(variant: 'success', text: __('Order status updated.'));
    }

    public function updatePayment(): void
    {
        $order = $this->selectedOrder();

        Gate::authorize('updatePayment', $order);

        $validated = $this->validate($this->paymentRules())['form'];
        $orderManager = app(AdminOrderManager::class);

        $orderManager->updatePaymentStatus(
            $order,
            PaymentStatus::from($validated['payment_status']),
            $this->user(),
            $this->nullableString($validated['note'] ?? null),
        );

        $this->selectOrder($order->id);
        Flux::toast(variant: 'success', text: __('Payment status updated.'));
    }

    public function addNote(): void
    {
        $order = $this->selectedOrder();

        Gate::authorize('create', OrderNote::class);

        $validated = $this->validate([
            'noteBody' => ['required', 'string', 'max:2000'],
            'noteVisibleToCustomer' => ['boolean'],
        ]);

        $orderManager = app(AdminOrderManager::class);

        $orderManager->addNote($order, $this->user(), $validated['noteBody'], (bool) $validated['noteVisibleToCustomer']);

        $this->noteBody = '';
        $this->noteVisibleToCustomer = false;
        Flux::toast(variant: 'success', text: __('Order note saved.'));
    }

    public function render(): View
    {
        $orders = Order::query()
            ->with(['user', 'items', 'shippingMethod'])
            ->when($this->search !== '', function (Builder $query): void {
                $search = trim($this->search);

                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('order_number', 'like', '%'.$search.'%')
                        ->orWhere('customer_name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%');
                });
            })
            ->when($this->status !== '', fn (Builder $query) => $query->where('status', $this->status))
            ->when($this->paymentStatus !== '', fn (Builder $query) => $query->where('payment_status', $this->paymentStatus))
            ->latest('placed_at')
            ->latest('id')
            ->paginate(12);

        $selectedOrder = $this->selectedOrderId === null
            ? null
            : Order::query()
                ->with(['addresses', 'items', 'notes.user', 'payments.events.user', 'shippingMethod', 'statusEvents.user', 'user'])
                ->find($this->selectedOrderId);

        return view('livewire.admin.operations.order-index', [
            'orders' => $orders,
            'selectedOrder' => $selectedOrder,
            'orderStatuses' => OrderStatus::cases(),
            'paymentStatuses' => PaymentStatus::cases(),
        ])->layout('components.layouts.admin', [
            'title' => __('Orders'),
            'breadcrumbs' => [
                __('Admin') => route('admin.dashboard'),
                __('Operations') => null,
                __('Orders') => null,
            ],
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function statusRules(): array
    {
        return [
            'form.status' => ['required', Rule::in(array_map(static fn (OrderStatus $status): string => $status->value, OrderStatus::cases()))],
            'form.note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function paymentRules(): array
    {
        return [
            'form.payment_status' => ['required', Rule::in(array_map(static fn (PaymentStatus $status): string => $status->value, PaymentStatus::cases()))],
            'form.note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function selectedOrder(): Order
    {
        abort_unless($this->selectedOrderId !== null, 404);

        return Order::query()->findOrFail($this->selectedOrderId);
    }

    protected function user(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    protected function orderStatus(Order $order): OrderStatus
    {
        $status = $order->getAttribute('status');

        return $status instanceof OrderStatus ? $status : OrderStatus::from((string) $status);
    }

    protected function paymentStatusForOrder(Order $order): PaymentStatus
    {
        $status = $order->getAttribute('payment_status');

        return $status instanceof PaymentStatus ? $status : PaymentStatus::from((string) $status);
    }

    protected function nullableString(?string $value): ?string
    {
        return filled($value) ? trim((string) $value) : null;
    }
}
