<?php

namespace App\Livewire\Admin\Catalog;

use App\Livewire\Admin\Catalog\Concerns\InteractsWithCatalogForms;
use App\Models\AttributeValue;
use App\Models\ProductAttribute;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Livewire\Component;
use Livewire\WithPagination;

class AttributeIndex extends Component
{
    use InteractsWithCatalogForms, WithPagination;

    public string $search = '';

    public string $valueSearch = '';

    public string $status = 'all';

    public string $selectedAttributeId = '';

    public bool $showAttributeForm = false;

    public bool $showValueForm = false;

    public ?int $editingAttributeId = null;

    public ?int $editingValueId = null;

    /**
     * @var array<string, mixed>
     */
    public array $attributeForm = [];

    /**
     * @var array<string, mixed>
     */
    public array $valueForm = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', ProductAttribute::class);

        $this->resetAttributeForm();
        $this->resetValueForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedValueSearch(): void
    {
        $this->resetPage('valuesPage');
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
        $this->resetPage('valuesPage');
    }

    public function updatedSelectedAttributeId(): void
    {
        $this->resetPage('valuesPage');
    }

    public function createAttribute(): void
    {
        Gate::authorize('create', ProductAttribute::class);

        $this->resetAttributeForm();
        $this->showAttributeForm = true;
    }

    public function editAttribute(int $attributeId): void
    {
        $attribute = ProductAttribute::query()->findOrFail($attributeId);

        Gate::authorize('update', $attribute);

        $this->editingAttributeId = $attribute->id;
        $this->attributeForm = [
            'name' => $attribute->name,
            'slug' => $attribute->slug,
            'type' => $attribute->type,
            'is_variant_option' => $attribute->is_variant_option,
            'is_filterable' => $attribute->is_filterable,
            'is_active' => $attribute->is_active,
            'sort_order' => $attribute->sort_order,
        ];
        $this->showAttributeForm = true;
    }

    public function saveAttribute(): void
    {
        $attribute = $this->editingAttributeId
            ? ProductAttribute::query()->findOrFail($this->editingAttributeId)
            : new ProductAttribute;

        Gate::authorize($attribute->exists ? 'update' : 'create', $attribute->exists ? $attribute : ProductAttribute::class);

        $validated = $this->validate($this->attributeRules())['attributeForm'];
        $validated['slug'] = $this->normalizedSlug($validated['slug'] ?? null, $validated['name']);

        $attribute->fill([
            'name' => $this->nullableString($validated['name']),
            'slug' => $validated['slug'],
            'type' => $validated['type'],
            'is_variant_option' => $this->booleanValue($validated['is_variant_option'] ?? false),
            'is_filterable' => $this->booleanValue($validated['is_filterable'] ?? false),
            'is_active' => $this->booleanValue($validated['is_active'] ?? false),
            'sort_order' => $this->integerValue($validated['sort_order'] ?? 0),
        ])->save();

        $this->selectedAttributeId = (string) $attribute->id;
        $this->resetAttributeForm();
        $this->showAttributeForm = false;

        Flux::toast(variant: 'success', text: __('Attribute saved.'));
    }

    public function deleteAttribute(int $attributeId): void
    {
        $attribute = ProductAttribute::query()->findOrFail($attributeId);

        Gate::authorize('delete', $attribute);

        $attribute->delete();

        if ($this->selectedAttributeId === (string) $attributeId) {
            $this->selectedAttributeId = '';
        }

        $this->resetAttributeForm();
        $this->showAttributeForm = false;

        Flux::toast(variant: 'success', text: __('Attribute deleted.'));
    }

    public function createValue(?int $attributeId = null): void
    {
        Gate::authorize('create', AttributeValue::class);

        $this->resetValueForm();
        $this->valueForm['product_attribute_id'] = $attributeId ?: $this->selectedAttributeId;
        $this->showValueForm = true;
    }

    public function editValue(int $valueId): void
    {
        $value = AttributeValue::query()->findOrFail($valueId);

        Gate::authorize('update', $value);

        $this->editingValueId = $value->id;
        $this->valueForm = [
            'product_attribute_id' => $value->product_attribute_id,
            'value' => $value->value,
            'slug' => $value->slug,
            'display_value' => $value->display_value,
            'color_hex' => $value->color_hex,
            'image_path' => $value->image_path,
            'is_active' => $value->is_active,
            'sort_order' => $value->sort_order,
        ];
        $this->showValueForm = true;
    }

    public function saveValue(): void
    {
        $value = $this->editingValueId
            ? AttributeValue::query()->findOrFail($this->editingValueId)
            : new AttributeValue;

        Gate::authorize($value->exists ? 'update' : 'create', $value->exists ? $value : AttributeValue::class);

        $validated = $this->validate($this->valueRules())['valueForm'];
        $attributeId = (int) $validated['product_attribute_id'];
        $validated['slug'] = $this->normalizedSlug($validated['slug'] ?? null, $validated['value']);

        $value->fill([
            'product_attribute_id' => $attributeId,
            'value' => $this->nullableString($validated['value']),
            'slug' => $validated['slug'],
            'display_value' => $this->nullableString($validated['display_value'] ?? null),
            'color_hex' => $this->nullableString($validated['color_hex'] ?? null),
            'image_path' => $this->nullableString($validated['image_path'] ?? null),
            'is_active' => $this->booleanValue($validated['is_active'] ?? false),
            'sort_order' => $this->integerValue($validated['sort_order'] ?? 0),
        ])->save();

        $this->selectedAttributeId = (string) $attributeId;
        $this->resetValueForm();
        $this->showValueForm = false;

        Flux::toast(variant: 'success', text: __('Attribute value saved.'));
    }

    public function deleteValue(int $valueId): void
    {
        $value = AttributeValue::query()->findOrFail($valueId);

        Gate::authorize('delete', $value);

        $value->delete();

        $this->resetValueForm();
        $this->showValueForm = false;

        Flux::toast(variant: 'success', text: __('Attribute value deleted.'));
    }

    public function cancelAttribute(): void
    {
        $this->resetAttributeForm();
        $this->showAttributeForm = false;
    }

    public function cancelValue(): void
    {
        $this->resetValueForm();
        $this->showValueForm = false;
    }

    public function render(): View
    {
        Gate::authorize('viewAny', ProductAttribute::class);

        $attributes = ProductAttribute::query()
            ->withCount('values')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('slug', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(8);

        $values = AttributeValue::query()
            ->with('productAttribute')
            ->when($this->selectedAttributeId !== '', fn ($query) => $query->where('product_attribute_id', $this->selectedAttributeId))
            ->when($this->valueSearch !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('value', 'like', '%'.$this->valueSearch.'%')
                        ->orWhere('display_value', 'like', '%'.$this->valueSearch.'%')
                        ->orWhere('slug', 'like', '%'.$this->valueSearch.'%');
                });
            })
            ->when($this->status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('sort_order')
            ->orderBy('value')
            ->paginate(8, ['*'], 'valuesPage');

        return view('livewire.admin.catalog.attribute-index', [
            'attributes' => $attributes,
            'values' => $values,
            'attributeOptions' => ProductAttribute::query()->orderBy('name')->get(['id', 'name']),
            'types' => ['text', 'color', 'size', 'material'],
        ])->layout('components.layouts.admin', [
            'title' => __('Attributes'),
            'breadcrumbs' => [
                __('Admin') => route('admin.dashboard'),
                __('Catalog') => null,
                __('Attributes') => null,
            ],
        ]);
    }

    /**
     * @return array<string, list<string|Unique>>
     */
    protected function attributeRules(): array
    {
        return [
            'attributeForm.name' => ['required', 'string', 'max:255'],
            'attributeForm.slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('product_attributes', 'slug')->ignore($this->editingAttributeId),
            ],
            'attributeForm.type' => ['required', 'string', Rule::in(['text', 'color', 'size', 'material'])],
            'attributeForm.is_variant_option' => ['boolean'],
            'attributeForm.is_filterable' => ['boolean'],
            'attributeForm.is_active' => ['boolean'],
            'attributeForm.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, list<string|Unique>>
     */
    protected function valueRules(): array
    {
        $attributeId = $this->nullableInteger($this->valueForm['product_attribute_id'] ?? null);

        return [
            'valueForm.product_attribute_id' => ['required', 'integer', 'exists:product_attributes,id'],
            'valueForm.value' => ['required', 'string', 'max:255'],
            'valueForm.slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('attribute_values', 'slug')
                    ->where(fn ($query) => $query->where('product_attribute_id', $attributeId))
                    ->ignore($this->editingValueId),
            ],
            'valueForm.display_value' => ['nullable', 'string', 'max:255'],
            'valueForm.color_hex' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'valueForm.image_path' => ['nullable', 'string', 'max:255'],
            'valueForm.is_active' => ['boolean'],
            'valueForm.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function resetAttributeForm(): void
    {
        $this->editingAttributeId = null;
        $this->attributeForm = [
            'name' => '',
            'slug' => '',
            'type' => 'text',
            'is_variant_option' => false,
            'is_filterable' => true,
            'is_active' => true,
            'sort_order' => 0,
        ];

        $this->resetValidation();
    }

    protected function resetValueForm(): void
    {
        $this->editingValueId = null;
        $this->valueForm = [
            'product_attribute_id' => '',
            'value' => '',
            'slug' => '',
            'display_value' => '',
            'color_hex' => '',
            'image_path' => '',
            'is_active' => true,
            'sort_order' => 0,
        ];

        $this->resetValidation();
    }
}
