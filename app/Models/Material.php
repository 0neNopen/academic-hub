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
            $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
            if ($material->file_path && \Illuminate\Support\Facades\Storage::disk($disk)->exists($material->file_path)) {
                \Illuminate\Support\Facades\Storage::disk($disk)->delete($material->file_path);
            }
        });
    }
}