<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property list<string>|null $countries
 * @property list<string>|null $regions
 * @property bool $is_active
 * @property int $sort_order
 */
class ShippingZone extends Model
{
    /**
     * @var list<string>
     */
    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'countries' => 'array',
            'is_active' => 'boolean',
            'regions' => 'array',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<ShippingMethod, $this>
     */
    public function methods(): HasMany
    {
        return $this->hasMany(ShippingMethod::class);
    }
}
