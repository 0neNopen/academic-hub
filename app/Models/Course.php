<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'code',
        'lecturer_name',
        'room',
        'pj_name',
        'pj_phone',
        'attendance_url',
        'day_of_week',
        'start_time',
        'end_time',
        'color',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function materials(): HasMany
    {
        return $this->hasMany(Material::class)->chaperone();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class)->chaperone();
    }

    protected static function booted(): void
    {
        static::deleting(function (Course $course) {
            // Trigger deleting on each related material to clean up physical storage files
            $course->materials()->each(fn ($material) => $material->delete());
        });
    }
}