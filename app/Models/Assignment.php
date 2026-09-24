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
        'description',
        'deadline',
        'submission_url',
        'status',
        'submission_file_path',
        'reminder_h24_sent_at',
        'reminder_h3_sent_at',
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'reminder_h24_sent_at' => 'datetime',
        'reminder_h3_sent_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}