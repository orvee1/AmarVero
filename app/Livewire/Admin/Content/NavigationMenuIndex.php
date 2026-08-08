<?php

namespace App\Livewire\Admin\Content;

use App\Livewire\Admin\Content\Concerns\InteractsWithContentForms;
use App\Models\Category;
use App\Models\NavigationMenu;
use App\Models\NavigationMenuItem;
use App\Models\ProductCollection;
use App\Models\StaticPage;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Livewire\Component;
use Livewire\WithPagination;

class NavigationMenuIndex extends Component
{
    use InteractsWithContentForms, WithPagination;

    public string $search = '';

    public bool $showMenuForm = false;

    public bool $showItemForm = false;

    public ?int $editingMenuId = null;

    public ?int $editingItemId = null;

    public string $selectedMenuId = '';

    /**
     * @var array<string, mixed>
     */
    public array $menuForm = [];

    /**
     * @var array<string, mixed>
     */
    public array $itemForm = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', NavigationMenu::class);

        $this->resetMenuForm();
        $this->resetItemForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function createMenu(): void
    {
        Gate::authorize('create', NavigationMenu::class);

        $this->resetMenuForm();
        $this->showMenuForm = true;
    }

    public function editMenu(int $menuId): void
    {
        $menu = NavigationMenu::query()->findOrFail($menuId);

        Gate::authorize('update', $menu);

        $this->editingMenuId = $menu->id;
        $this->menuForm = [
            'name' => $menu->name,
            'slug' => $menu->slug,
            'is_active' => $menu->is_active,
        ];
        $this->showMenuForm = true;
    }

    public function saveMenu(): void
    {
        $menu = $this->editingMenuId
            ? NavigationMenu::query()->findOrFail($this->editingMenuId)
            : new NavigationMenu;

        Gate::authorize($menu->exists ? 'update' : 'create', $menu->exists ? $menu : NavigationMenu::class);

        $validated = $this->validate($this->menuRules())['menuForm'];
        $validated['slug'] = $this->normalizedSlug($validated['slug'] ?? null, $validated['name']);

        $menu->fill([
            'name' => $this->nullableString($validated['name']),
            'slug' => $validated['slug'],
            'is_active' => $this->booleanValue($validated['is_active'] ?? false),
        ])->save();

        $this->selectedMenuId = (string) $menu->id;
        $this->resetMenuForm();
        $this->showMenuForm = false;

        Flux::toast(variant: 'success', text: __('Navigation menu saved.'));
    }

    public function deleteMenu(int $menuId): void
    {
        $menu = NavigationMenu::query()->findOrFail($menuId);

        Gate::authorize('delete', $menu);

        $menu->delete();

        if ($this->selectedMenuId === (string) $menuId) {
            $this->selectedMenuId = '';
        }

        $this->resetMenuForm();
        $this->showMenuForm = false;

        Flux::toast(variant: 'success', text: __('Navigation menu deleted.'));
    }

    public function createItem(?int $parentId = null): void
    {
        Gate::authorize('create', NavigationMenuItem::class);

        $this->resetItemForm();
        $this->itemForm['navigation_menu_id'] = $this->selectedMenuId;
        $this->itemForm['parent_id'] = $parentId ?: '';
        $this->showItemForm = true;
    }

    public function editItem(int $itemId): void
    {
        $item = NavigationMenuItem::query()->findOrFail($itemId);

        Gate::authorize('update', $item);

        $linkableType = $this->linkableAlias($item->linkable_type);
        $itemMeta = $item->getAttribute('meta');
        $meta = is_array($itemMeta) ? $itemMeta : [];

        $this->editingItemId = $item->id;
        $this->itemForm = [
            'navigation_menu_id' => $item->navigation_menu_id,
            'parent_id' => $item->parent_id,
            'label' => $item->label,
            'type' => $item->type,
            'url' => $item->url,
            'linkable_type' => $linkableType,
            'linkable_id' => $item->linkable_id,
            'opens_new_tab' => $item->opens_new_tab,
            'is_mega_menu' => $item->is_mega_menu,
            'is_active' => $item->is_active,
            'sort_order' => $item->sort_order,
            'image_path' => $meta['image_path'] ?? '',
            'promo_title' => $meta['promo_title'] ?? '',
            'promo_text' => $meta['promo_text'] ?? '',
            'desktop_visible' => $meta['desktop_visible'] ?? true,
            'mobile_visible' => $meta['mobile_visible'] ?? true,
        ];
        $this->selectedMenuId = (string) $item->navigation_menu_id;
        $this->showItemForm = true;
    }

    public function saveItem(): void
    {
        $item = $this->editingItemId
            ? NavigationMenuItem::query()->findOrFail($this->editingItemId)
            : new NavigationMenuItem;

        Gate::authorize($item->exists ? 'update' : 'create', $item->exists ? $item : NavigationMenuItem::class);

        $validated = $this->validate($this->itemRules())['itemForm'];
        $linkable = $this->linkableModel($validated['linkable_type'] ?? '', $validated['linkable_id'] ?? null);

        if ($item->exists && $this->nullableInteger($validated['parent_id'] ?? null) === $item->id) {
            $this->addError('itemForm.parent_id', __('A menu item cannot be its own parent.'));

            return;
        }

        DB::transaction(function () use ($item, $validated, $linkable): void {
            $item->fill([
                'navigation_menu_id' => (int) $validated['navigation_menu_id'],
                'parent_id' => $this->nullableInteger($validated['parent_id'] ?? null),
                'label' => $this->nullableString($validated['label']),
                'type' => $validated['type'],
                'url' => $this->nullableString($validated['url'] ?? null),
                'linkable_type' => $linkable?->getMorphClass(),
                'linkable_id' => $linkable?->getKey(),
                'opens_new_tab' => $this->booleanValue($validated['opens_new_tab'] ?? false),
                'is_mega_menu' => $this->booleanValue($validated['is_mega_menu'] ?? false),
                'is_active' => $this->booleanValue($validated['is_active'] ?? false),
                'sort_order' => $this->integerValue($validated['sort_order'] ?? 0),
                'meta' => [
                    'image_path' => $this->nullableString($validated['image_path'] ?? null),
                    'promo_title' => $this->nullableString($validated['promo_title'] ?? null),
                    'promo_text' => $this->nullableString($validated['promo_text'] ?? null),
                    'desktop_visible' => $this->booleanValue($validated['desktop_visible'] ?? true),
                    'mobile_visible' => $this->booleanValue($validated['mobile_visible'] ?? true),
                ],
            ])->save();
        });

        $this->selectedMenuId = (string) $validated['navigation_menu_id'];
        $this->resetItemForm();
        $this->showItemForm = false;

        Flux::toast(variant: 'success', text: __('Navigation item saved.'));
    }

    public function deleteItem(int $itemId): void
    {
        $item = NavigationMenuItem::query()->findOrFail($itemId);

        Gate::authorize('delete', $item);

        $item->delete();

        if ($this->editingItemId === $itemId) {
            $this->resetItemForm();
            $this->showItemForm = false;
        }

        Flux::toast(variant: 'success', text: __('Navigation item deleted.'));
    }

    public function cancelMenu(): void
    {
        $this->resetMenuForm();
        $this->showMenuForm = false;
    }

    public function cancelItem(): void
    {
        $this->resetItemForm();
        $this->showItemForm = false;
    }

    public function render(): View
    {
        Gate::authorize('viewAny', NavigationMenu::class);

        $menus = NavigationMenu::query()
            ->withCount('items')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('slug', 'like', '%'.$this->search.'%');
                });
            })
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(8);

        if ($this->selectedMenuId === '' && $menus->isNotEmpty()) {
            $this->selectedMenuId = (string) $menus->first()->id;
        }

        $selectedMenu = filled($this->selectedMenuId)
            ? NavigationMenu::query()
                ->with(['items' => fn ($query) => $query->with(['children', 'linkable'])->orderBy('sort_order')->orderBy('label')])
                ->find((int) $this->selectedMenuId)
            : null;

        return view('livewire.admin.content.navigation-menu-index', [
            'menus' => $menus,
            'selectedMenu' => $selectedMenu,
            'parentOptions' => $this->parentOptions(),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'collections' => ProductCollection::query()->orderBy('name')->get(['id', 'name']),
            'pages' => StaticPage::query()->orderBy('title')->get(['id', 'title']),
        ])->layout('components.layouts.admin', [
            'title' => __('Navigation menus'),
            'breadcrumbs' => [
                __('Admin') => route('admin.dashboard'),
                __('Content') => null,
                __('Navigation menus') => null,
            ],
        ]);
    }

    /**
     * @return array<string, list<string|Unique>>
     */
    protected function menuRules(): array
    {
        return [
            'menuForm.name' => ['required', 'string', 'max:255'],
            'menuForm.slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('navigation_menus', 'slug')->ignore($this->editingMenuId),
            ],
            'menuForm.is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function itemRules(): array
    {
        return [
            'itemForm.navigation_menu_id' => ['required', 'integer', 'exists:navigation_menus,id'],
            'itemForm.parent_id' => ['nullable', 'integer', 'exists:navigation_menu_items,id'],
            'itemForm.label' => ['required', 'string', 'max:255'],
            'itemForm.type' => ['required', Rule::in(['url', 'category', 'collection', 'page'])],
            'itemForm.url' => ['nullable', 'string', 'max:255', Rule::requiredIf(($this->itemForm['type'] ?? null) === 'url')],
            'itemForm.linkable_type' => ['nullable', Rule::in(['', 'category', 'collection', 'page'])],
            'itemForm.linkable_id' => ['nullable', 'integer'],
            'itemForm.opens_new_tab' => ['boolean'],
            'itemForm.is_mega_menu' => ['boolean'],
            'itemForm.is_active' => ['boolean'],
            'itemForm.sort_order' => ['nullable', 'integer', 'min:0'],
            'itemForm.image_path' => ['nullable', 'string', 'max:255'],
            'itemForm.promo_title' => ['nullable', 'string', 'max:255'],
            'itemForm.promo_text' => ['nullable', 'string', 'max:500'],
            'itemForm.desktop_visible' => ['boolean'],
            'itemForm.mobile_visible' => ['boolean'],
        ];
    }

    protected function resetMenuForm(): void
    {
        $this->editingMenuId = null;
        $this->menuForm = [
            'name' => '',
            'slug' => '',
            'is_active' => true,
        ];

        $this->resetValidation();
    }

    protected function resetItemForm(): void
    {
        $this->editingItemId = null;
        $this->itemForm = [
            'navigation_menu_id' => $this->selectedMenuId,
            'parent_id' => '',
            'label' => '',
            'type' => 'url',
            'url' => '',
            'linkable_type' => '',
            'linkable_id' => '',
            'opens_new_tab' => false,
            'is_mega_menu' => false,
            'is_active' => true,
            'sort_order' => 0,
            'image_path' => '',
            'promo_title' => '',
            'promo_text' => '',
            'desktop_visible' => true,
            'mobile_visible' => true,
        ];

        $this->resetValidation();
    }

    /**
     * @return Collection<int, NavigationMenuItem>
     */
    protected function parentOptions()
    {
        if (! filled($this->selectedMenuId)) {
            return new Collection;
        }

        return NavigationMenuItem::query()
            ->where('navigation_menu_id', (int) $this->selectedMenuId)
            ->when($this->editingItemId, fn ($query) => $query->whereKeyNot($this->editingItemId))
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get(['id', 'label']);
    }

    protected function linkableModel(mixed $type, mixed $id): ?Model
    {
        if (! filled($type) || ! filled($id)) {
            return null;
        }

        $modelClass = match ($type) {
            'category' => Category::class,
            'collection' => ProductCollection::class,
            'page' => StaticPage::class,
            default => null,
        };

        return $modelClass ? $modelClass::query()->find((int) $id) : null;
    }

    protected function linkableAlias(?string $class): string
    {
        return match ($class) {
            Category::class => 'category',
            ProductCollection::class => 'collection',
            StaticPage::class => 'page',
            default => '',
        };
    }
}
