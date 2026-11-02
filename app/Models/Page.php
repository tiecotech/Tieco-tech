<?php

namespace App\Models;

use App\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use Translatable;

    protected $guarded = ['id'];

    protected $casts = ['title' => 'array', 'body' => 'array', 'seo' => 'array', 'is_published' => 'boolean'];
}
