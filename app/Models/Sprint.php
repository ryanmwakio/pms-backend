<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sprint extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'name',
        'goal',
        'status',
        'start_date',
        'end_date',
        'started_at',
        'completed_at',
        'velocity',
    ];

    protected function casts(): array
    {
        return [
            'start_date'    => 'date',
            'end_date'      => 'date',
            'started_at'    => 'datetime',
            'completed_at'  => 'datetime',
            'velocity'      => 'integer',
        ];
    }

    // ──────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    // ──────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────

    public function totalPoints(): int
    {
        return (int) $this->issues()->sum('story_points');
    }

    public function completedPoints(): int
    {
        return (int) $this->issues()
            ->whereHas('status', fn ($q) => $q->where('category', 'done'))
            ->sum('story_points');
    }
}
