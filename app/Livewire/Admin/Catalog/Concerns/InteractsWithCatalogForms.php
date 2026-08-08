<?php

namespace App\Livewire\Admin\Catalog\Concerns;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Str;

trait InteractsWithCatalogForms
{
    protected function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    protected function normalizedSlug(?string $slug, string $fallback): string
    {
        return Str::slug($this->nullableString($slug) ?? $fallback);
    }

    protected function nullableInteger(mixed $value): ?int
    {
        return filled($value) ? (int) $value : null;
    }

    protected function integerValue(mixed $value): int
    {
        return filled($value) ? (int) $value : 0;
    }

    protected function booleanValue(mixed $value): bool
    {
        return (bool) $value;
    }

    protected function nullableDecimal(mixed $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }

    protected function nullableDateTime(mixed $value): ?string
    {
        return filled($value) ? (string) $value : null;
    }

    protected function dateTimeInput(mixed $date): string
    {
        if ($date instanceof CarbonInterface || $date instanceof DateTimeInterface) {
            return $date->format('Y-m-d\TH:i');
        }

        if (is_string($date) && $date !== '') {
            return Carbon::parse($date)->format('Y-m-d\TH:i');
        }

        return '';
    }

    /**
     * @param  array<int|string, mixed>  $ids
     * @return list<int>
     */
    protected function integerIds(array $ids): array
    {
        return array_values(array_unique(array_map(
            static fn (mixed $id): int => (int) $id,
            array_filter($ids, static fn (mixed $id): bool => filled($id)),
        )));
    }
}
