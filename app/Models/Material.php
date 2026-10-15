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
        'links',
        'notes',
    ];

    protected $casts = [
        'links' => 'array',
    ];

    protected $appends = [
        'all_links',
    ];

    public function getAllLinksAttribute(): array
    {
        if (!empty($this->links) && is_array($this->links)) {
            return $this->links;
        }

        if (!empty($this->external_link)) {
            return [
                [
                    'title' => 'Link Materi',
                    'url' => $this->external_link,
                ],
            ];
        }

        return [];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (Material $material) {
            if (!$material->file_path) {
                return;
            }
            $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
            if (\Illuminate\Support\Facades\Storage::disk($disk)->exists($material->file_path)) {
                \Illuminate\Support\Facades\Storage::disk($disk)->delete($material->file_path);
            } elseif ($disk !== 'public' && \Illuminate\Support\Facades\Storage::disk('public')->exists($material->file_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($material->file_path);
            }
        });
    }
}