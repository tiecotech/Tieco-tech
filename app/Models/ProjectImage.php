<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectImage extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['alt' => 'array'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
