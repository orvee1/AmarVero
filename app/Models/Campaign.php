<?php

namespace App\Models;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
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
            'ends_at' => 'datetime',
            'meta' => 'array',
            'starts_at' => 'datetime',
            'status' => ContentStatus::class,
        ];
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    public function promotionalBanners(): HasMany
    {
        return $this->hasMany(PromotionalBanner::class);
    }
}
