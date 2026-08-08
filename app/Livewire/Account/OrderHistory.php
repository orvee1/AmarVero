<?php

namespace App\Livewire\Account;

use App\Enums\OrderStatus;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class OrderHistory extends Component
{
    use WithPagination;

    public string $status = '';

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.account.order-history', [
            'orders' => $this->user()
                ->orders()
                ->with(['items', 'shippingMethod'])
                ->when($this->status !== '', fn (Builder $query) => $query->where('status', $this->status))
                ->latest('placed_at')
                ->latest('id')
                ->paginate(10),
            'statuses' => OrderStatus::cases(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::in(array_map(static fn (OrderStatus $status): string => $status->value, OrderStatus::cases()))],
        ];
    }

    protected function user(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
