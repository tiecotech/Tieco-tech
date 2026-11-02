<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectRequestItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['name' => 'array', 'meta' => 'array', 'line_total_minor' => 'integer', 'unit_price_minor' => 'integer'];

    public function request()
    {
        return $this->belongsTo(ProjectRequest::class, 'project_request_id');
    }
}
