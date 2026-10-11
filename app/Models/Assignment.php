<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'assignment_type',
        'description',
        'deadline',
        'submission_url',
        'links',
        'status',
        'submission_file_path',
        'reminder_h24_sent_at',
        'reminder_h3_sent_at',
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'links' => 'array',
        'reminder_h24_sent_at' => 'datetime',
        'reminder_h3_sent_at' => 'datetime',
    ];

    protected $appends = [
        'all_links',
    ];

    public function isGroup(): bool
    {
        return $this->assignment_type === 'group';
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->assignment_type === 'group' ? 'Kelompok' : 'Individu';
    }

    public function getAllLinksAttribute(): array
    {
        if (!empty($this->links) && is_array($this->links)) {
            return $this->links;
        }

        if (!empty($this->submission_url)) {
            return [
                [
                    'title' => 'Link Pengumpulan',
                    'url' => $this->submission_url,
                ],
            ];
        }

        return [];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}