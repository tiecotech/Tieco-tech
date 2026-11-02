<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProjectRequest extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'project_type_snapshot' => 'array', 'pricing_snapshot' => 'array', 'whatsapp_opt_in' => 'boolean',
        'has_unpriced_items' => 'boolean', 'expected_date' => 'date',
        'subtotal_minor' => 'integer', 'discount_minor' => 'integer', 'total_minor' => 'integer',
    ];

    /** Human-friendly unique number, e.g. TT-261015-K7Q2M. */
    public static function generateNumber(): string
    {
        do {
            $number = 'TT-'.now()->format('ymd').'-'.strtoupper(Str::random(5));
        } while (static::withTrashed()->where('number', $number)->exists());

        return $number;
    }

    public function status()
    {
        return $this->belongsTo(RequestStatus::class, 'request_status_id');
    }

    public function projectType()
    {
        return $this->belongsTo(ProjectType::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function items()
    {
        return $this->hasMany(ProjectRequestItem::class);
    }

    public function attachments()
    {
        return $this->hasMany(ProjectRequestAttachment::class);
    }

    public function notes()
    {
        return $this->hasMany(ProjectRequestNote::class)->latest();
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class)->orderByDesc('version');
    }
}
