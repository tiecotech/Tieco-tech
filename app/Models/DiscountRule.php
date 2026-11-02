<?php

namespace App\Models;

use App\Concerns\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DiscountRule extends Model
{
    use Translatable;

    protected $guarded = ['id'];

    protected $casts = [
        'name' => 'array', 'description' => 'array', 'stackable' => 'boolean', 'is_active' => 'boolean',
        'value' => 'integer', 'condition_value' => 'integer', 'max_discount_minor' => 'integer',
        'starts_at' => 'datetime', 'ends_at' => 'datetime',
    ];

    public function scopeCurrentlyActive(Builder $q): Builder
    {
        return $q->where('is_active', true)
            ->where(fn ($w) => $w->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($w) => $w->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderByDesc('priority')->orderBy('id');
    }
}
