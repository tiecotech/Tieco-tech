<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectRequestNote extends Model
{
    protected $guarded = ['id'];

    public function request()
    {
        return $this->belongsTo(ProjectRequest::class, 'project_request_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
