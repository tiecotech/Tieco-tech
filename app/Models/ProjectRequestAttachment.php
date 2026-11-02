<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectRequestAttachment extends Model
{
    protected $guarded = ['id'];

    public function request()
    {
        return $this->belongsTo(ProjectRequest::class, 'project_request_id');
    }
}
