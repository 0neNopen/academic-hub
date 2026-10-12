<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'whatsapp_number',
        'notification_channel',
        'schedule_reminder_time',
        'task_reminder_early',
        'task_reminder_late',
        'campus_name',
        'campus_portal_url',
        'campus_portals',
        'telegram_chat_id',
        'telegram_connect_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'campus_portals' => 'array',
        ];
    }

    /**
     * Get normalized list of campus portals for backward compatibility.
     *
     * @return array<int, array{name: string, url: string}>
     */
    public function getAllCampusPortalsAttribute(): array
    {
        if (!empty($this->campus_portals) && is_array($this->campus_portals)) {
            return array_values(array_filter($this->campus_portals, function ($p) {
                return is_array($p) && !empty($p['url']);
            }));
        }

        if (!empty($this->campus_portal_url)) {
            return [
                [
                    'name' => 'Portal Kampus',
                    'url' => $this->campus_portal_url,
                ],
            ];
        }

        return [];
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }
}
