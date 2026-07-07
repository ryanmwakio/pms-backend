<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workspace_id',
        'team_id',
        'lead_id',
        'name',
        'key',
        'description',
        'color',
        'health',
        'progress',
        'start_date',
        'target_date',
        'is_archived',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'start_date'  => 'date',
            'target_date' => 'date',
            'is_archived' => 'boolean',
            'settings'    => 'array',
            'progress'    => 'integer',
        ];
    }

    // ──────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot('role', 'is_favorite')
            ->withTimestamps();
    }

    public function epics(): HasMany
    {
        return $this->hasMany(Epic::class);
    }

    public function sprints(): HasMany
    {
        return $this->hasMany(Sprint::class)->orderBy('start_date');
    }

    public function activeSprint(): HasOne
    {
        return $this->hasOne(Sprint::class)->where('status', 'active');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(Status::class)->orderBy('position');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    // ──────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────

    public function nextIssueKey(): string
    {
        $max = $this->issues()->withTrashed()->count();

        return $this->key.'-'.($max + 1);
    }
}
