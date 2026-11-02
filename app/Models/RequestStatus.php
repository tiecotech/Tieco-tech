<?php

namespace App\Models;

use App\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;

class RequestStatus extends Model
{
    use Translatable;

    protected $guarded = ['id'];

    protected $casts = ['name' => 'array', 'is_default' => 'boolean', 'is_final' => 'boolean'];

    public static function default(): self
    {
        return static::where('is_default', true)->firstOrFail();
    }
}
