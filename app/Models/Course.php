<?php

namespace App\Models;

use App\Enums\CourseStatus;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory, SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'title',
        'slug',
        'summary',
        'description',
        'instructor_name',
        'level',
        'duration_minutes',
        'thumbnail_path',
        'published_at',
        'category_id',
        'user_id',
        'code',
        'language',
        'currency',
        'price',
        'learning_outcomes',
        'requirements',
        'capacity',
        'self_enroll',
        'allow_discussion',
        'status',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'duration_minutes' => 'integer',
            'price' => 'integer',
            'capacity' => 'integer',
            'self_enroll' => 'boolean',
            'allow_discussion' => 'boolean',
            'status' => CourseStatus::class,
        ];
    }

    /** @return HasMany<Module, $this> */
    public function modules(): HasMany
    {
        return $this->hasMany(Module::class)->orderBy('position');
    }

    /** @return BelongsToMany<User, $this> */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_user')
            ->withPivot(['status', 'progress', 'enrolled_at', 'completed_at', 'last_accessed_at', 'grade'])
            ->withTimestamps();
    }

    /** @return BelongsToMany<Tag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'course_tag');
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Announcement, $this> */
    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function isPublished(): bool
    {
        return $this->status === CourseStatus::Published
            && $this->published_at !== null
            && $this->published_at->isPast();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @param Builder<Course> $query */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', CourseStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /** @param Builder<Course> $query */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    /** @param Builder<Course> $query */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || $term === '') {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($term): void {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';
            $inner->where('title', 'like', $like)
                ->orWhere('summary', 'like', $like)
                ->orWhere('code', 'like', $like)
                ->orWhere('instructor_name', 'like', $like);
        });
    }
}
