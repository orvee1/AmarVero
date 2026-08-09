<?php

namespace App\Livewire\Admin\Operations;

use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $selectedCustomerId = null;

    /**
     * @var array{name: string, email: string}
     */
    public array $form = [
        'name' => '',
        'email' => '',
    ];

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function selectCustomer(int $customerId): void
    {
        $customer = $this->customerForAdmin($customerId);

        Gate::authorize('view', $customer);

        $this->selectedCustomerId = $customer->id;
        $this->form = [
            'name' => $customer->name,
            'email' => $customer->email,
        ];
    }

    public function updateCustomer(): void
    {
        $customer = $this->selectedCustomer();

        Gate::authorize('update', $customer);

        $validated = $this->validate([
            'form.name' => ['required', 'string', 'max:255'],
            'form.email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($customer->id)],
        ])['form'];

        $customer->forceFill([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
        ])->save();

        Flux::toast(variant: 'success', text: __('Customer updated.'));
    }

    public function render(): View
    {
        $customers = User::query()
            ->whereDoesntHave('roles')
            ->withCount(['orders', 'addresses', 'productReviews'])
            ->withSum('orders as orders_sum_grand_total', 'grand_total')
            ->when($this->search !== '', function (Builder $query): void {
                $search = trim($this->search);

                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->latest()
            ->paginate(12);

        return view('livewire.admin.operations.customer-index', [
            'customers' => $customers,
            'selectedCustomer' => $this->selectedCustomerId === null ? null : $this->selectedCustomerWithRelations(),
        ])->layout('components.layouts.admin', [
            'title' => __('Customers'),
            'breadcrumbs' => [
                __('Admin') => route('admin.dashboard'),
                __('Operations') => null,
                __('Customers') => null,
            ],
        ]);
    }

    protected function selectedCustomer(): User
    {
        abort_unless($this->selectedCustomerId !== null, 404);

        return $this->customerForAdmin($this->selectedCustomerId);
    }

    protected function selectedCustomerWithRelations(): User
    {
        return User::query()
            ->whereDoesntHave('roles')
            ->with([
                'addresses',
                'orders' => fn ($query) => $query->latest('placed_at')->latest('id')->limit(5),
                'productReviews.product',
                'wishlists.items',
            ])
            ->whereKey($this->selectedCustomerId)
            ->firstOrFail();
    }

    protected function customerForAdmin(int $customerId): User
    {
        return User::query()
            ->whereDoesntHave('roles')
            ->whereKey($customerId)
            ->firstOrFail();
    }
}
