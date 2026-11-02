<?php

namespace App\Models;

use App\Concerns\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes, Translatable;

    protected $guarded = ['id'];

    protected $casts = [
        'name' => 'array', 'summary' => 'array', 'description' => 'array', 'problems' => 'array',
        'deliverables' => 'array', 'features' => 'array', 'steps' => 'array', 'faqs' => 'array',
        'seo' => 'array', 'is_visible' => 'boolean',
    ];

    public function scopeVisible(Builder $q): Builder
    {
        return $q->where('is_visible', true)->orderBy('sort_order');
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}
