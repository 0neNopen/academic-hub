<?php

namespace App\Console\Commands;

use App\Jobs\SendAssignmentReminderJob;
use App\Models\Assignment;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendAssignmentReminders extends Command
{
    protected $signature = 'assignments:send-reminders';
    protected $description = 'Pindai deadline tugas dan jadwalkan antrean pengingat multi-kanal otomatis';

    public function handle(): void
    {
        Carbon::setLocale('id');
        $tz = config('app.timezone', 'Asia/Jakarta');
        $now = Carbon::now($tz);
        $this->info("Memeriksa deadline tugas pada: " . $now->isoFormat('dddd, D MMMM YYYY - HH:mm') . " WIB");

        $assignments = Assignment::with(['course.user'])
            ->where('status', '!=', 'completed')
            ->where('deadline', '>', $now)
            ->where('deadline', '<=', $now->copy()->addDay())
            ->where(function ($query) {
                $query->whereNull('reminder_h24_sent_at')
                      ->orWhereNull('reminder_h3_sent_at');
            })
            ->orderBy('deadline', 'asc')
            ->get();

        $dispatchedCount = 0;

        foreach ($assignments as $assignment) {
            $user = $assignment->course?->user;
            if (!$user) {
                continue;
            }

            $deadline = Carbon::parse($assignment->deadline)->locale('id')->timezone($tz);
            $diffInMinutes = (int) $now->diffInMinutes($deadline, false);

            if ($diffInMinutes <= 0) {
                continue;
            }

            // Evaluasi Kategori Pengingat:
            // 1. Pengingat Rutin H-24 (antara 180 s/d 1440 menit)
            $isH24 = ($diffInMinutes <= 1440 && $diffInMinutes > 180 && is_null($assignment->reminder_h24_sent_at));
            // 2. Peringatan Mendesak H-3 (<= 180 menit)
            $isH3 = ($diffInMinutes <= 180 && is_null($assignment->reminder_h3_sent_at));

            if (!$isH24 && !$isH3) {
                continue;
            }

            // Kirim job ke antrean background worker (non-blocking)
            SendAssignmentReminderJob::dispatch($assignment->id, $isH3);
            $dispatchedCount++;

            $type = $isH3 ? 'H-3 Jam' : 'H-24 Jam';
            $this->info("[Antrean Dipicu] Tugas '{$assignment->title}' ({$type}) dijadwalkan untuk {$user->name}.");
        }

        $this->info("Pemeriksaan selesai. Total antrean dipicu: {$dispatchedCount} tugas.");
    }
}