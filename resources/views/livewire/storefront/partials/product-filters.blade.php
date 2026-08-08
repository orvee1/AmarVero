<div class="space-y-6">
    <fieldset>
        <legend class="text-sm font-semibold text-zinc-950 dark:text-white">{{ __('Merchandising') }}</legend>
        <div class="mt-3 grid gap-2">
            <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200"><input wire:model.live="sale" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">{{ __('Sale') }}</label>
            <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200"><input wire:model.live="featured" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">{{ __('Featured') }}</label>
            <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200"><input wire:model.live="newArrival" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">{{ __('New arrivals') }}</label>
            <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200"><input wire:model.live="best" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">{{ __('Best sellers') }}</label>
        </div>
    </fieldset>

    <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
        {{ __('Brand') }}
        <select wire:model.live="brand" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
            <option value="">{{ __('All brands') }}</option>
            @foreach ($brands as $brandOption)
                <option value="{{ $brandOption->id }}">{{ $brandOption->name }}</option>
            @endforeach
        </select>
    </label>

    <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
        {{ __('Category') }}
        <select wire:model.live="category" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
            <option value="">{{ __('All categories') }}</option>
            @foreach ($categories as $categoryOption)
                <option value="{{ $categoryOption->id }}">{{ $categoryOption->name }}</option>
            @endforeach
        </select>
    </label>

    <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
        {{ __('Collection') }}
        <select wire:model.live="collection" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
            <option value="">{{ __('All collections') }}</option>
            @foreach ($collections as $collectionOption)
                <option value="{{ $collectionOption->id }}">{{ $collectionOption->name }}</option>
            @endforeach
        </select>
    </label>

    <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
        {{ __('Gender') }}
        <select wire:model.live="gender" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
            <option value="">{{ __('All') }}</option>
            @foreach ($genders as $genderOption)
                <option value="{{ $genderOption }}">{{ str($genderOption)->title() }}</option>
            @endforeach
        </select>
    </label>

    <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
        {{ __('Material') }}
        <select wire:model.live="material" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
            <option value="">{{ __('All materials') }}</option>
            @foreach ($materials as $materialOption)
                <option value="{{ $materialOption }}">{{ $materialOption }}</option>
            @endforeach
        </select>
    </label>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
            {{ __('Minimum price') }}
            <input wire:model.live.debounce.400ms="minPrice" type="number" min="0" step="0.01" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
        </label>

        <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
            {{ __('Maximum price') }}
            <input wire:model.live.debounce.400ms="maxPrice" type="number" min="0" step="0.01" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
        </label>
    </div>

    <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
        {{ __('Availability') }}
        <select wire:model.live="availability" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-950 dark:border-white/15 dark:bg-zinc-950 dark:text-white">
            <option value="">{{ __('Any') }}</option>
            <option value="in_stock">{{ __('In stock') }}</option>
            <option value="out_of_stock">{{ __('Out of stock') }}</option>
        </select>
    </label>

    @foreach ($attributeGroups as $attributeValues)
        @php($attribute = $attributeValues->first()?->productAttribute)
        @if ($attribute)
            <fieldset>
                <legend class="text-sm font-semibold text-zinc-950 dark:text-white">{{ $attribute->name }}</legend>
                <div class="mt-3 grid max-h-44 gap-2 overflow-y-auto pr-1">
                    @foreach ($attributeValues as $attributeValue)
                        <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                            <input wire:model.live="attributeValueIds" type="checkbox" value="{{ $attributeValue->id }}" class="size-4 rounded border-zinc-300 text-teal-700 focus:ring-teal-600">
                            <span>{{ $attributeValue->display_value ?: $attributeValue->value }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endif
    @endforeach
</div>
