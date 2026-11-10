<?php

namespace App\Models;

use App\Concerns\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Project extends Model
{
    use SoftDeletes, Translatable;

    protected $guarded = ['id'];

    protected $casts = [
        'title' => 'array', 'summary' => 'array', 'description' => 'array', 'tech' => 'array', 'meta' => 'array',
        'is_featured' => 'boolean', 'is_visible' => 'boolean', 'is_demo' => 'boolean', 'published_at' => 'datetime',
    ];

    public function scopeVisible(Builder $q): Builder
    {
        return $q->where('is_visible', true)->orderBy('sort_order');
    }

    public function scopeFeatured(Builder $q): Builder
    {
        return $q->where('is_featured', true);
    }

    /** Projects shown on the public site (demo content never is). */
    public function scopePublic(Builder $q): Builder
    {
        return $q->visible()->where('is_demo', false);
    }

    public function category()
    {
        return $this->belongsTo(ProjectCategory::class, 'project_category_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function images()
    {
        return $this->hasMany(ProjectImage::class)->orderBy('sort_order');
    }

    /** Paths starting with "/" (or a full URL) are public assets shipped with the app; anything else is on the public disk. */
    public static function mediaUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return str_starts_with($path, '/') || str_starts_with($path, 'http') ? $path : Storage::disk('public')->url($path);
    }

    /** Picks the active locale from every {ar, en} pair inside a nested array. */
    private static function localize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        $keys = array_keys($value);
        if ($keys && array_diff($keys, ['ar', 'en']) === []) {
            return $value[app()->getLocale()] ?? $value['en'] ?? reset($value);
        }

        return array_map(fn ($v) => self::localize($v), $value);
    }

    /** Data for the project cards (home page and the portfolio list). */
    public function card(): array
    {
        $m = self::localize($this->meta ?? []);

        return [
            'id' => $this->id, 'slug' => $this->slug,
            'title' => $this->tr('title'), 'summary' => $this->tr('summary'),
            'category' => $this->category?->tr('name'),
            'cover' => self::mediaUrl($this->cover_path),
            'url' => $this->url, 'host' => $this->url ? parse_url($this->url, PHP_URL_HOST) : null,
            'tech' => $this->tech ?? [],
            'accent' => $m['accent'] ?? null,
            'logo' => self::mediaUrl($m['logo'] ?? null), 'logo_light' => (bool) ($m['logo_on_light'] ?? false),
            'desktop' => self::mediaUrl($m['desktop'] ?? null), 'mobile' => self::mediaUrl($m['mobile'] ?? null),
            'stats' => $m['stats'] ?? [],
        ];
    }

    /** Data for the project page: the card plus the story, features and gallery. */
    public function detail(): array
    {
        $m = self::localize($this->meta ?? []);
        $paragraphs = array_values(array_filter(preg_split('/\n{2,}/', (string) $this->tr('description'))));

        return $this->card() + [
            'year' => $m['year'] ?? null,
            'role' => $m['role'] ?? [],
            'features' => $m['features'] ?? [],
            'paragraphs' => $paragraphs,
            'gallery' => $this->images->map(fn (ProjectImage $i) => [
                'src' => self::mediaUrl($i->path),
                'alt' => (self::localize($i->alt) ?? [])['text'] ?? null,
                'mobile' => str_contains($i->path, '/mobile-'),
            ])->values()->all(),
        ];
    }
}
