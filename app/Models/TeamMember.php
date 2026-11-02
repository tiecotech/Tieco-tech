<?php

namespace App\Models;

use App\Concerns\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    use Translatable;

    protected $guarded = ['id'];

    protected $casts = ['name' => 'array', 'role' => 'array', 'socials' => 'array', 'is_visible' => 'boolean'];

    public function scopeVisible(Builder $q): Builder
    {
        return $q->where('is_visible', true)->orderBy('sort_order');
    }
}
