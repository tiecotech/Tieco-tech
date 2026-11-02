<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class Quote extends Model
{
    protected $guarded = ['id'];

    // The model must know its status before first save, or the immutability check can't tell draft from issued.
    protected $attributes = ['status' => 'draft'];

    protected $casts = ['valid_until' => 'date', 'issued_at' => 'datetime'];

    protected static function booted(): void
    {
        // An issued quote is a record: only its status may change. Revise by creating a new version.
        static::updating(function (Quote $q) {
            if ($q->getOriginal('status') !== 'draft' && count(array_diff(array_keys($q->getDirty()), ['status', 'updated_at'])) > 0) {
                throw new LogicException('Issued quotes are immutable; create a new version instead.');
            }
        });
    }

    public function request()
    {
        return $this->belongsTo(ProjectRequest::class, 'project_request_id');
    }

    public function items()
    {
        return $this->hasMany(QuoteItem::class)->orderBy('sort_order');
    }
}
