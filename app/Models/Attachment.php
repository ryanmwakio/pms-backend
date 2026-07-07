<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'issue_id',
        'uploaded_by',
        'filename',
        'disk',
        'path',
        'mime_type',
        'size',
    ];

    protected $appends = ['url'];

    // ──────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ──────────────────────────────────────────
    // Accessors
    // ──────────────────────────────────────────

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}
