<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FooterLink extends Model
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
            'is_active' => 'boolean',
            'opens_new_tab' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function footerSection(): BelongsTo
    {
        return $this->belongsTo(FooterSection::class);
    }
}
