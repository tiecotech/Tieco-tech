<?php

namespace App\Models;

use App\Concerns\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes, Translatable;

    protected $guarded = ['id'];

    protected $casts = [
        'title' => 'array', 'summary' => 'array', 'description' => 'array', 'tech' => 'array',
        'is_featured' => 'boolean', 'is_visible' => 'boolean', 'is_demo' => 'boolean', 'published_at' => 'datetime',
    ];

    public function scopeVisible(Builder $q): Builder
    {
        return $q->where('is_visible', true)->orderBy('sort_order');
    }

    public function scopeFeatured(Builder $q): Builder
    {
        return $q->where('is_featured', true);
    }

    public function category()
    {
        return $this->belongsTo(ProjectCategory::class, 'project_category_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function images()
    {
        return $this->hasMany(ProjectImage::class)->orderBy('sort_order');
    }
}
