<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'meeting_number',
        'file_path',
        'external_link',
        'notes',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (Material $material) {
            if ($material->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($material->file_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($material->file_path);
            }
        });
    }
}