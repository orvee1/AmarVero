<?php

namespace App\Livewire\Account;

use App\Enums\AddressType;
use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AddressBook extends Component
{
    public ?int $editingAddressId = null;

    /**
     * @var array{type: string, name: string, phone: string, line_one: string, line_two: string, area: string, city: string, region: string, postal_code: string, country_code: string, is_default_shipping: bool, is_default_billing: bool}
     */
    public array $form = [
        'type' => 'shipping',
        'name' => '',
        'phone' => '',
        'line_one' => '',
        'line_two' => '',
        'area' => '',
        'city' => '',
        'region' => '',
        'postal_code' => '',
        'country_code' => 'BD',
        'is_default_shipping' => false,
        'is_default_billing' => false,
    ];

    public function create(): void
    {
        $this->editingAddressId = null;
        $this->resetForm();
        $this->resetValidation();
    }

    public function edit(int $addressId): void
    {
        $address = $this->addressForUser($addressId);

        $this->editingAddressId = $address->id;
        $this->form = [
            'type' => $this->addressType($address)->value,
            'name' => $address->name,
            'phone' => $address->phone,
            'line_one' => $address->line_one,
            'line_two' => (string) $address->line_two,
            'area' => (string) $address->area,
            'city' => $address->city,
            'region' => (string) $address->region,
            'postal_code' => (string) $address->postal_code,
            'country_code' => $address->country_code,
            'is_default_shipping' => $address->is_default_shipping,
            'is_default_billing' => $address->is_default_billing,
        ];
        $this->resetValidation();
    }

    public function save(): void
    {
        $validated = $this->validate();
        $user = $this->user();
        $address = $this->editingAddressId === null
            ? new CustomerAddress(['user_id' => $user->id])
            : $this->addressForUser($this->editingAddressId);

        if ($validated['form']['is_default_shipping']) {
            $user->addresses()->whereKeyNot($address->id ?? 0)->update(['is_default_shipping' => false]);
        }

        if ($validated['form']['is_default_billing']) {
            $user->addresses()->whereKeyNot($address->id ?? 0)->update(['is_default_billing' => false]);
        }

        $address->forceFill([
            ...$validated['form'],
            'country_code' => strtoupper($validated['form']['country_code']),
        ])->save();

        $this->editingAddressId = $address->id;
        session()->flash('status', __('Address saved.'));
    }

    public function setDefaultShipping(int $addressId): void
    {
        $address = $this->addressForUser($addressId);
        $this->user()->addresses()->whereKeyNot($address->id)->update(['is_default_shipping' => false]);

        $address->forceFill([
            'is_default_shipping' => true,
        ])->save();

        session()->flash('status', __('Default shipping address updated.'));
    }

    public function setDefaultBilling(int $addressId): void
    {
        $address = $this->addressForUser($addressId);
        $this->user()->addresses()->whereKeyNot($address->id)->update(['is_default_billing' => false]);

        $address->forceFill([
            'is_default_billing' => true,
        ])->save();

        session()->flash('status', __('Default billing address updated.'));
    }

    public function delete(int $addressId): void
    {
        $this->addressForUser($addressId)->delete();

        if ($this->editingAddressId === $addressId) {
            $this->create();
        }

        session()->flash('status', __('Address removed.'));
    }

    public function render(): View
    {
        return view('livewire.account.address-book', [
            'addresses' => $this->user()
                ->addresses()
                ->orderByDesc('is_default_shipping')
                ->orderByDesc('is_default_billing')
                ->latest()
                ->get(),
            'addressTypes' => AddressType::cases(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'form.type' => ['required', 'string', Rule::in(array_map(static fn (AddressType $type): string => $type->value, AddressType::cases()))],
            'form.name' => ['required', 'string', 'max:255'],
            'form.phone' => ['required', 'string', 'max:40'],
            'form.line_one' => ['required', 'string', 'max:255'],
            'form.line_two' => ['nullable', 'string', 'max:255'],
            'form.area' => ['nullable', 'string', 'max:255'],
            'form.city' => ['required', 'string', 'max:120'],
            'form.region' => ['nullable', 'string', 'max:120'],
            'form.postal_code' => ['nullable', 'string', 'max:30'],
            'form.country_code' => ['required', 'string', 'size:2'],
            'form.is_default_shipping' => ['boolean'],
            'form.is_default_billing' => ['boolean'],
        ];
    }

    protected function addressForUser(int $addressId): CustomerAddress
    {
        return $this->user()->addresses()->whereKey($addressId)->firstOrFail();
    }

    protected function addressType(CustomerAddress $address): AddressType
    {
        $type = $address->getAttribute('type');

        if ($type instanceof AddressType) {
            return $type;
        }

        return AddressType::from((string) $type);
    }

    protected function user(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    protected function resetForm(): void
    {
        $this->form = [
            'type' => AddressType::Shipping->value,
            'name' => '',
            'phone' => '',
            'line_one' => '',
            'line_two' => '',
            'area' => '',
            'city' => '',
            'region' => '',
            'postal_code' => '',
            'country_code' => 'BD',
            'is_default_shipping' => false,
            'is_default_billing' => false,
        ];
    }
}
