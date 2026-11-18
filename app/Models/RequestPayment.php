<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestPayment extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['paid_at' => 'date', 'amount_minor' => 'integer'];

    public function request()
    {
        return $this->belongsTo(ProjectRequest::class, 'project_request_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
