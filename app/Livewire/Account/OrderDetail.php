<?php

namespace App\Livewire\Account;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class OrderDetail extends Component
{
    public string $orderNumber;

    public function mount(Order $order): void
    {
        abort_unless($order->user_id === $this->user()->id, 404);

        $this->orderNumber = $order->order_number;
    }

    public function render(): View
    {
        return view('livewire.account.order-detail', [
            'order' => $this->order(),
        ]);
    }

    protected function order(): Order
    {
        return $this->user()
            ->orders()
            ->with([
                'addresses',
                'items.product',
                'payments.events',
                'shippingMethod',
                'statusEvents',
            ])
            ->where('order_number', $this->orderNumber)
            ->firstOrFail();
    }

    protected function user(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
