<?php

namespace App\Livewire\Admin\Settings;

use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\AdminPermissions;
use App\Support\Settings\SettingsManager;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class StoreSettingsIndex extends Component
{
    public string $panel = 'settings';

    /**
     * @var array<string, mixed>
     */
    public array $settingsForm = [];

    public ?int $editingZoneId = null;

    public ?int $editingMethodId = null;

    /**
     * @var array{name: string, countries: string, regions: string, is_active: bool, sort_order: int|string}
     */
    public array $zoneForm = [
        'name' => '',
        'countries' => 'BD',
        'regions' => '',
        'is_active' => true,
        'sort_order' => 0,
    ];

    /**
     * @var array{shipping_zone_id: int|string, name: string, code: string, price: int|string, free_shipping_threshold: int|string, estimated_days_min: int|string, estimated_days_max: int|string, is_active: bool, sort_order: int|string}
     */
    public array $methodForm = [
        'shipping_zone_id' => '',
        'name' => '',
        'code' => '',
        'price' => '0.00',
        'free_shipping_threshold' => '',
        'estimated_days_min' => '',
        'estimated_days_max' => '',
        'is_active' => true,
        'sort_order' => 0,
    ];

    public function mount(): void
    {
        abort_unless(
            Gate::allows('viewAny', SiteSetting::class)
            || Gate::allows('viewAny', ShippingZone::class)
            || Gate::allows('viewAny', ShippingMethod::class),
            403,
        );

        $settingsManager = app(SettingsManager::class);

        $this->settingsForm = $this->settingsFormFromValues($settingsManager->values());
        $this->resetZoneForm();
        $this->resetMethodForm();
    }

    public function showPanel(string $panel): void
    {
        abort_unless(in_array($panel, ['settings', 'shipping'], true), 404);

        $this->panel = $panel;
    }

    public function saveSettings(): void
    {
        Gate::authorize('update', new SiteSetting);

        $settingsManager = app(SettingsManager::class);
        $validated = $this->validate($this->settingsRules($settingsManager))['settingsForm'];

        $settingsManager->saveMany($this->settingsPayload($settingsManager, $validated));

        Flux::toast(variant: 'success', text: __('Store settings saved.'));
    }

    public function createZone(): void
    {
        $this->authorizeShippingUpdate(new ShippingZone);

        $this->resetZoneForm();
        $this->panel = 'shipping';
    }

    public function editZone(int $zoneId): void
    {
        $zone = ShippingZone::query()->findOrFail($zoneId);

        $this->authorizeShippingUpdate($zone);

        $this->editingZoneId = $zone->id;
        $this->zoneForm = [
            'name' => $zone->name,
            'countries' => $this->listInput($zone->countries),
            'regions' => $this->listInput($zone->regions),
            'is_active' => $zone->is_active,
            'sort_order' => $zone->sort_order,
        ];
        $this->panel = 'shipping';
    }

    public function saveZone(): void
    {
        $zone = $this->editingZoneId
            ? ShippingZone::query()->findOrFail($this->editingZoneId)
            : new ShippingZone;

        $this->authorizeShippingUpdate($zone);

        $validated = $this->validate($this->zoneRules())['zoneForm'];

        $zone->forceFill([
            'name' => trim($validated['name']),
            'countries' => $this->csvList($validated['countries'] ?? null),
            'regions' => $this->csvList($validated['regions'] ?? null),
            'is_active' => (bool) $validated['is_active'],
            'sort_order' => (int) $validated['sort_order'],
        ])->save();

        $this->resetZoneForm();
        Flux::toast(variant: 'success', text: __('Shipping zone saved.'));
    }

    public function deleteZone(int $zoneId): void
    {
        $zone = ShippingZone::query()->with('methods.orders')->findOrFail($zoneId);

        $this->authorizeShippingUpdate($zone);

        if ($zone->methods->contains(fn (ShippingMethod $method): bool => $method->orders->isNotEmpty())) {
            $this->addError('zoneForm.name', __('Shipping zones with historical orders cannot be deleted.'));

            return;
        }

        $zone->delete();
        $this->resetZoneForm();
        Flux::toast(variant: 'success', text: __('Shipping zone deleted.'));
    }

    public function createMethod(): void
    {
        $this->authorizeShippingUpdate(new ShippingMethod);

        $this->resetMethodForm();
        $this->panel = 'shipping';
    }

    public function editMethod(int $methodId): void
    {
        $method = ShippingMethod::query()->findOrFail($methodId);

        $this->authorizeShippingUpdate($method);

        $this->editingMethodId = $method->id;
        $this->methodForm = [
            'shipping_zone_id' => $method->shipping_zone_id,
            'name' => $method->name,
            'code' => $method->code,
            'price' => (string) $method->price,
            'free_shipping_threshold' => (string) $method->free_shipping_threshold,
            'estimated_days_min' => (string) $method->estimated_days_min,
            'estimated_days_max' => (string) $method->estimated_days_max,
            'is_active' => $method->is_active,
            'sort_order' => $method->sort_order,
        ];
        $this->panel = 'shipping';
    }

    public function saveMethod(): void
    {
        $method = $this->editingMethodId
            ? ShippingMethod::query()->findOrFail($this->editingMethodId)
            : new ShippingMethod;

        $this->authorizeShippingUpdate($method);

        $validated = $this->validate($this->methodRules())['methodForm'];

        $minimumDays = $this->nullableInteger($validated['estimated_days_min'] ?? null);
        $maximumDays = $this->nullableInteger($validated['estimated_days_max'] ?? null);

        if ($minimumDays !== null && $maximumDays !== null && $maximumDays < $minimumDays) {
            $this->addError('methodForm.estimated_days_max', __('Maximum days must be greater than or equal to minimum days.'));

            return;
        }

        $method->forceFill([
            'shipping_zone_id' => (int) $validated['shipping_zone_id'],
            'name' => trim($validated['name']),
            'code' => Str::slug($validated['code']),
            'price' => $this->decimalString($validated['price']),
            'free_shipping_threshold' => $this->nullableDecimal($validated['free_shipping_threshold'] ?? null),
            'estimated_days_min' => $minimumDays,
            'estimated_days_max' => $maximumDays,
            'is_active' => (bool) $validated['is_active'],
            'sort_order' => (int) $validated['sort_order'],
        ])->save();

        $this->resetMethodForm();
        Flux::toast(variant: 'success', text: __('Shipping method saved.'));
    }

    public function deleteMethod(int $methodId): void
    {
        $method = ShippingMethod::query()->withCount('orders')->findOrFail($methodId);

        $this->authorizeShippingUpdate($method);

        if ($method->orders_count > 0) {
            $this->addError('methodForm.name', __('Shipping methods with historical orders cannot be deleted.'));

            return;
        }

        $method->delete();
        $this->resetMethodForm();
        Flux::toast(variant: 'success', text: __('Shipping method deleted.'));
    }

    public function render(): View
    {
        $settingsManager = app(SettingsManager::class);

        return view('livewire.admin.settings.store-settings-index', [
            'settingsGroups' => $this->settingsGroups($settingsManager),
            'shippingZones' => ShippingZone::query()
                ->with(['methods' => fn ($query) => $query->orderBy('sort_order')->orderBy('name')])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'shippingMethods' => ShippingMethod::query()
                ->with('shippingZone')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ])->layout('components.layouts.admin', [
            'title' => __('Store settings'),
            'breadcrumbs' => [
                __('Admin') => route('admin.dashboard'),
                __('Settings') => null,
                __('Store settings') => null,
            ],
        ]);
    }

    /**
     * @return array<string, array<int, array{key: string, form_key: string, label: string, type: string, public: bool}>>
     */
    protected function settingsGroups(SettingsManager $settingsManager): array
    {
        $groups = [];

        foreach ($settingsManager->definitions() as $key => $definition) {
            if ($definition['group'] === 'payments' && ! $this->canManagePaymentSettings()) {
                continue;
            }

            $groups[$definition['group']][] = [
                'key' => $key,
                'form_key' => $this->formKey($key),
                'label' => $definition['label'],
                'type' => $definition['type'],
                'public' => $definition['public'],
            ];
        }

        return $groups;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function settingsRules(SettingsManager $settingsManager): array
    {
        $rules = [];

        foreach ($settingsManager->definitions() as $key => $definition) {
            if ($definition['group'] === 'payments' && ! $this->canManagePaymentSettings()) {
                continue;
            }

            $formKey = 'settingsForm.'.$this->formKey($key);

            $rules[$formKey] = match ($definition['type']) {
                'boolean' => ['boolean'],
                'integer' => ['required', 'integer', 'min:0', 'max:3650'],
                default => ['nullable', 'string', 'max:1000'],
            };

            if ($key === 'contact.email') {
                $rules[$formKey] = ['nullable', 'email', 'max:255'];
            }
        }

        return $rules;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function zoneRules(): array
    {
        return [
            'zoneForm.name' => ['required', 'string', 'max:255'],
            'zoneForm.countries' => ['nullable', 'string', 'max:500'],
            'zoneForm.regions' => ['nullable', 'string', 'max:500'],
            'zoneForm.is_active' => ['boolean'],
            'zoneForm.sort_order' => ['required', 'integer', 'min:0', 'max:999999'],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function methodRules(): array
    {
        return [
            'methodForm.shipping_zone_id' => ['required', 'integer', 'exists:shipping_zones,id'],
            'methodForm.name' => ['required', 'string', 'max:255'],
            'methodForm.code' => ['required', 'string', 'max:255', Rule::unique('shipping_methods', 'code')->ignore($this->editingMethodId)],
            'methodForm.price' => ['required', 'numeric', 'min:0'],
            'methodForm.free_shipping_threshold' => ['nullable', 'numeric', 'min:0'],
            'methodForm.estimated_days_min' => ['nullable', 'integer', 'min:0', 'max:365'],
            'methodForm.estimated_days_max' => ['nullable', 'integer', 'min:0', 'max:365'],
            'methodForm.is_active' => ['boolean'],
            'methodForm.sort_order' => ['required', 'integer', 'min:0', 'max:999999'],
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    protected function settingsFormFromValues(array $values): array
    {
        $form = [];

        foreach ($values as $key => $value) {
            $form[$this->formKey($key)] = $value;
        }

        return $form;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function settingsPayload(SettingsManager $settingsManager, array $validated): array
    {
        $payload = [];

        foreach ($settingsManager->definitions() as $key => $definition) {
            if ($definition['group'] === 'payments' && ! $this->canManagePaymentSettings()) {
                continue;
            }

            $formKey = $this->formKey($key);

            if (array_key_exists($formKey, $validated)) {
                $payload[$key] = $validated[$formKey];
            }
        }

        return $payload;
    }

    protected function resetZoneForm(): void
    {
        $this->editingZoneId = null;
        $this->zoneForm = [
            'name' => '',
            'countries' => 'BD',
            'regions' => '',
            'is_active' => true,
            'sort_order' => 0,
        ];

        $this->resetValidation();
    }

    protected function resetMethodForm(): void
    {
        $this->editingMethodId = null;
        $this->methodForm = [
            'shipping_zone_id' => '',
            'name' => '',
            'code' => '',
            'price' => '0.00',
            'free_shipping_threshold' => '',
            'estimated_days_min' => '',
            'estimated_days_max' => '',
            'is_active' => true,
            'sort_order' => 0,
        ];

        $this->resetValidation();
    }

    protected function authorizeShippingUpdate(ShippingMethod|ShippingZone $model): void
    {
        Gate::authorize('update', $model);
    }

    protected function canManagePaymentSettings(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can(AdminPermissions::permission('payment-settings', 'update'));
    }

    protected function formKey(string $key): string
    {
        return str_replace('.', '__', $key);
    }

    /**
     * @param  list<string>|null  $values
     */
    protected function listInput(?array $values): string
    {
        return $values === null ? '' : implode(', ', $values);
    }

    /**
     * @return list<string>|null
     */
    protected function csvList(?string $value): ?array
    {
        if (blank($value)) {
            return null;
        }

        $values = [];

        foreach (explode(',', $value) as $item) {
            $item = trim($item);

            if ($item !== '') {
                $values[] = $item;
            }
        }

        return $values === [] ? null : $values;
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
}
