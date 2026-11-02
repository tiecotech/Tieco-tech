<?php

namespace App\Models;

use App\Concerns\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ServiceOption extends Model
{
    use Translatable;

    protected $guarded = ['id'];

    protected $casts = ['name' => 'array', 'description' => 'array', 'is_active' => 'boolean', 'amount' => 'integer'];

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('sort_order');
    }

    public function projectTypes()
    {
        return $this->belongsToMany(ProjectType::class, 'project_type_service_option');
    }
}
