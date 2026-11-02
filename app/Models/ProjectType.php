<?php

namespace App\Models;

use App\Concerns\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProjectType extends Model
{
    use Translatable;

    protected $guarded = ['id'];

    protected $casts = [
        'name' => 'array', 'description' => 'array', 'supports_pages' => 'boolean', 'is_active' => 'boolean',
        'base_price_minor' => 'integer', 'extra_page_price_minor' => 'integer',
    ];

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('sort_order');
    }

    public function options()
    {
        return $this->belongsToMany(ServiceOption::class, 'project_type_service_option');
    }
}
