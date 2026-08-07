<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function methods(): HasMany
    {
        return $this->hasMany(ShippingMethod::class);
    }
}
