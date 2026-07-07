<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Issue extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'sprint_id',
        'epic_id',
        'status_id',
        'assignee_id',
        'reporter_id',
        'parent_id',
        'key',
        'title',
        'description',
        'type',
        'priority',
        'story_points',
        'time_estimate',
        'time_spent',
        'due_date',
        'started_at',
        'completed_at',
        'position',
        'custom_fields',
    ];

    protected function casts(): array
    {
        return [
            'due_date'      => 'date',
            'started_at'    => 'datetime',
            'completed_at'  => 'datetime',
            'story_points'  => 'integer',
            'time_estimate' => 'integer',
            'time_spent'    => 'integer',
            'position'      => 'integer',
            'custom_fields' => 'array',
        ];
    }

    // ──────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }

    public function epic(): BelongsTo
    {
        return $this->belongsTo(Epic::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Issue::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Issue::class, 'parent_id');
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'issue_labels')->withTimestamps();
    }

    public function watchers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'issue_watchers')->withTimestamps();
    }

    public function linkedIssues(): BelongsToMany
    {
        return $this->belongsToMany(Issue::class, 'issue_links', 'issue_id', 'linked_issue_id')
            ->withPivot('link_type')
            ->withTimestamps();
    }

    public function linkedByIssues(): BelongsToMany
    {
        return $this->belongsToMany(Issue::class, 'issue_links', 'linked_issue_id', 'issue_id')
            ->withPivot('link_type')
            ->withTimestamps();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id')->orderBy('created_at');
    }

    public function allComments(): HasMany
    {
        return $this->hasMany(Comment::class)->orderBy('created_at');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class)->orderByDesc('created_at');
    }

    // ──────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────

    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeInSprint($query, int $sprintId)
    {
        return $query->where('sprint_id', $sprintId);
    }

    public function scopeBacklog($query)
    {
        return $query->whereNull('sprint_id');
    }
}
