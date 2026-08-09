<?php

namespace App\Support\Settings;

use App\Models\SiteSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SettingsManager
{
    private const string ValuesCacheKey = 'site_settings.defined_values';

    /**
     * @return array<string, array{group: string, type: string, default: mixed, public: bool, label: string}>
     */
    public function definitions(): array
    {
        return [
            'brand.name' => ['group' => 'general', 'type' => 'string', 'default' => 'Amarvero', 'public' => true, 'label' => __('Brand name')],
            'brand.logo_path' => ['group' => 'general', 'type' => 'string', 'default' => '', 'public' => true, 'label' => __('Logo path')],
            'brand.dark_logo_path' => ['group' => 'general', 'type' => 'string', 'default' => '', 'public' => true, 'label' => __('Dark logo path')],
            'brand.light_logo_path' => ['group' => 'general', 'type' => 'string', 'default' => '', 'public' => true, 'label' => __('Light logo path')],
            'brand.favicon_path' => ['group' => 'general', 'type' => 'string', 'default' => '', 'public' => true, 'label' => __('Favicon path')],
            'contact.email' => ['group' => 'general', 'type' => 'string', 'default' => '', 'public' => true, 'label' => __('Contact email')],
            'contact.support_phone' => ['group' => 'general', 'type' => 'string', 'default' => '', 'public' => true, 'label' => __('Support phone')],
            'seo.default_title' => ['group' => 'seo', 'type' => 'string', 'default' => '', 'public' => true, 'label' => __('SEO default title')],
            'seo.default_description' => ['group' => 'seo', 'type' => 'string', 'default' => '', 'public' => true, 'label' => __('SEO default description')],
            'seo.open_graph_image' => ['group' => 'seo', 'type' => 'string', 'default' => '', 'public' => true, 'label' => __('Open Graph image')],
            'analytics.placeholder_id' => ['group' => 'analytics', 'type' => 'string', 'default' => '', 'public' => false, 'label' => __('Analytics placeholder')],
            'maintenance.enabled' => ['group' => 'operations', 'type' => 'boolean', 'default' => false, 'public' => false, 'label' => __('Maintenance enabled')],
            'newsletter.enabled' => ['group' => 'marketing', 'type' => 'boolean', 'default' => true, 'public' => true, 'label' => __('Newsletter enabled')],
            'invoice.from_name' => ['group' => 'orders', 'type' => 'string', 'default' => 'Amarvero', 'public' => false, 'label' => __('Invoice name')],
            'orders.return_window_days' => ['group' => 'orders', 'type' => 'integer', 'default' => 7, 'public' => true, 'label' => __('Return window days')],
            'orders.cancellation_window_hours' => ['group' => 'orders', 'type' => 'integer', 'default' => 12, 'public' => true, 'label' => __('Cancellation window hours')],
            'reviews.verified_purchase_only' => ['group' => 'reviews', 'type' => 'boolean', 'default' => true, 'public' => true, 'label' => __('Verified purchase reviews only')],
            'payments.cash_on_delivery_enabled' => ['group' => 'payments', 'type' => 'boolean', 'default' => true, 'public' => false, 'label' => __('Cash on delivery enabled')],
            'payments.bank_transfer_instructions' => ['group' => 'payments', 'type' => 'string', 'default' => '', 'public' => false, 'label' => __('Bank transfer instructions')],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function values(): array
    {
        return Cache::remember(self::ValuesCacheKey, now()->addMinutes(10), fn (): array => $this->uncachedValues());
    }

    /**
     * @return array<string, mixed>
     */
    protected function uncachedValues(): array
    {
        $settings = SiteSetting::query()
            ->whereIn('key', array_keys($this->definitions()))
            ->get()
            ->keyBy('key');

        $values = [];

        foreach ($this->definitions() as $key => $definition) {
            $setting = $settings->get($key);
            $values[$key] = $setting instanceof SiteSetting
                ? $this->storedValue($setting, $definition['default'])
                : $definition['default'];
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function saveMany(array $values): void
    {
        foreach ($this->definitions() as $key => $definition) {
            if (! array_key_exists($key, $values)) {
                continue;
            }

            $this->save($key, $values[$key]);
        }
    }

    public function save(string $key, mixed $value): SiteSetting
    {
        $definition = $this->definitions()[$key];
        $normalizedValue = $this->normalizedValue($value, $definition['type']);

        $setting = SiteSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'group' => $definition['group'],
                'type' => $definition['type'],
                'value' => ['value' => $normalizedValue],
                'is_public' => $definition['public'],
            ],
        );

        Cache::forget(self::ValuesCacheKey);

        return $setting;
    }

    /**
     * @return Collection<int, SiteSetting>
     */
    public function settings(): Collection
    {
        return SiteSetting::query()
            ->whereIn('key', array_keys($this->definitions()))
            ->orderBy('group')
            ->orderBy('key')
            ->get();
    }

    protected function storedValue(SiteSetting $setting, mixed $default): mixed
    {
        $payload = $setting->getAttribute('value');

        if (! is_array($payload) || ! array_key_exists('value', $payload)) {
            return $default;
        }

        return $payload['value'];
    }

    protected function normalizedValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => (bool) $value,
            'integer' => max(0, (int) $value),
            default => is_scalar($value) ? trim((string) $value) : '',
        };
    }
}
