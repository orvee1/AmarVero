<?php

namespace App\Support\Checkout;

use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ShippingRateResolver
{
    /**
     * @return Collection<int, ShippingMethod>
     */
    public function availableMethods(string $countryCode = 'BD', ?string $region = null): Collection
    {
        return ShippingMethod::query()
            ->with('shippingZone')
            ->where('shipping_methods.is_active', true)
            ->whereHas('shippingZone', fn ($query) => $query->where('shipping_zones.is_active', true))
            ->join('shipping_zones', 'shipping_methods.shipping_zone_id', '=', 'shipping_zones.id')
            ->orderBy('shipping_zones.sort_order')
            ->orderBy('shipping_methods.sort_order')
            ->orderBy('shipping_methods.name')
            ->select('shipping_methods.*')
            ->get()
            ->filter(fn (ShippingMethod $method): bool => $this->zoneMatches($method->shippingZone, $countryCode, $region))
            ->values();
    }

    /**
     * @return Collection<int, array{method: ShippingMethod, rate: float, base_rate: float}>
     */
    public function availableRates(string $countryCode = 'BD', ?string $region = null, float $subtotal = 0.0, bool $couponFreeShipping = false): Collection
    {
        return $this->availableMethods($countryCode, $region)
            ->map(fn (ShippingMethod $method): array => [
                'method' => $method,
                'rate' => $this->rateForMethod($method, $subtotal, $couponFreeShipping),
                'base_rate' => (float) $method->price,
            ])
            ->values();
    }

    /**
     * @return array{method: ShippingMethod, rate: float, base_rate: float}
     */
    public function resolve(int $shippingMethodId, string $countryCode, ?string $region, float $subtotal, bool $couponFreeShipping = false): array
    {
        $method = ShippingMethod::query()
            ->with('shippingZone')
            ->whereKey($shippingMethodId)
            ->where('shipping_methods.is_active', true)
            ->whereHas('shippingZone', fn ($query) => $query->where('shipping_zones.is_active', true))
            ->first();

        if (! $method instanceof ShippingMethod || ! $this->zoneMatches($method->shippingZone, $countryCode, $region)) {
            throw ValidationException::withMessages([
                'shippingMethodId' => __('Choose an available shipping method.'),
            ]);
        }

        return [
            'method' => $method,
            'rate' => $this->rateForMethod($method, $subtotal, $couponFreeShipping),
            'base_rate' => (float) $method->price,
        ];
    }

    public function rateForMethod(ShippingMethod $method, float $subtotal, bool $couponFreeShipping = false): float
    {
        if ($couponFreeShipping) {
            return 0.0;
        }

        if ($method->free_shipping_threshold !== null && $subtotal >= (float) $method->free_shipping_threshold) {
            return 0.0;
        }

        return round((float) $method->price, 2);
    }

    protected function zoneMatches(?ShippingZone $zone, string $countryCode, ?string $region): bool
    {
        if (! $zone instanceof ShippingZone || ! $zone->is_active) {
            return false;
        }

        $countries = $this->normalizedList($zone->countries, true);
        $regions = $this->normalizedList($zone->regions, false);
        $country = strtoupper(trim($countryCode));
        $region = strtolower(trim((string) $region));

        $countryMatches = $countries === [] || in_array($country, $countries, true);
        $regionMatches = $regions === [] || ($region !== '' && in_array($region, $regions, true));

        return $countryMatches && $regionMatches;
    }

    /**
     * @return list<string>
     */
    protected function normalizedList(mixed $values, bool $uppercase): array
    {
        if (! is_array($values)) {
            return [];
        }

        $normalized = [];

        foreach ($values as $value) {
            if (! is_scalar($value) || trim((string) $value) === '') {
                continue;
            }

            $normalized[] = $uppercase ? strtoupper(trim((string) $value)) : strtolower(trim((string) $value));
        }

        return $normalized;
    }
}
