<?php

namespace App\Livewire\Admin\Content\Concerns;

use App\Enums\ContentStatus;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Str;

trait InteractsWithContentForms
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

        return number_format((float) $value, 7, '.', '');
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

    /**
     * @return list<string>
     */
    protected function contentStatusValues(): array
    {
        return array_map(fn (ContentStatus $status): string => $status->value, ContentStatus::cases());
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, string|null>
     */
    protected function scheduledContentFields(array $validated): array
    {
        $status = ContentStatus::from($validated['status']);
        $startsAt = $this->nullableDateTime($validated['starts_at'] ?? null);

        if ($status === ContentStatus::Published && $startsAt === null) {
            $startsAt = now()->format('Y-m-d H:i:s');
        }

        if ($status === ContentStatus::Draft || $status === ContentStatus::Archived) {
            $startsAt = null;
        }

        return [
            'status' => $status->value,
            'starts_at' => $startsAt,
            'ends_at' => $this->nullableDateTime($validated['ends_at'] ?? null),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    protected function keyValueTextToArray(mixed $value): array
    {
        $text = $this->nullableString($value);

        if ($text === null) {
            return [];
        }

        $items = collect(preg_split('/\R/', $text) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->mapWithKeys(function (string $line): array {
                $parts = array_map('trim', explode(':', $line, 2));

                return [$parts[0] => $parts[1] ?? null];
            })
            ->all();

        return $items;
    }

    protected function keyValueArrayToText(mixed $value): string
    {
        if (! is_array($value)) {
            return '';
        }

        return collect($value)
            ->map(fn (mixed $item, string|int $key): string => is_scalar($item) && filled($item) ? $key.': '.$item : (string) $key)
            ->implode("\n");
    }
}
