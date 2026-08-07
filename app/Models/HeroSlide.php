<?php

namespace App\Models;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Model;

class HeroSlide extends Model
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
            'sort_order' => 'integer',
            'starts_at' => 'datetime',
            'status' => ContentStatus::class,
        ];
    }
}
