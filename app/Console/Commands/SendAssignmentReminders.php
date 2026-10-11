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
            ->where('deadline', '<=', $now->copy()->addDays(2))
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

            // Ambil batas preferensi jam pengingat user (0 berarti dinonaktifkan)
            $earlyHours = ($user->task_reminder_early !== null && is_numeric($user->task_reminder_early))
                ? (int) $user->task_reminder_early
                : 24;
            $lateHours = ($user->task_reminder_late !== null && is_numeric($user->task_reminder_late))
                ? (int) $user->task_reminder_late
                : 3;

            $earlyLimitMinutes = $earlyHours * 60;
            $lateLimitMinutes = $lateHours * 60;

            // 1. Pengingat Rutin Awal (H-Early, default H-24 Jam)
            $isEarly = ($earlyLimitMinutes > 0 && $diffInMinutes <= $earlyLimitMinutes && $diffInMinutes > $lateLimitMinutes && is_null($assignment->reminder_h24_sent_at));
            // 2. Peringatan Mendesak Akhir (H-Late, default H-3 Jam)
            $isLate = ($lateLimitMinutes > 0 && $diffInMinutes <= $lateLimitMinutes && is_null($assignment->reminder_h3_sent_at));

            if (!$isEarly && !$isLate) {
                continue;
            }

            // Kirim job ke antrean background worker (non-blocking)
            SendAssignmentReminderJob::dispatch($assignment->id, $isLate);
            $dispatchedCount++;

            $type = $isLate ? "H-{$lateHours} Jam" : "H-{$earlyHours} Jam";
            $this->info("[Antrean Dipicu] Tugas '{$assignment->title}' ({$type}) dijadwalkan untuk {$user->name}.");
        }

        $this->info("Pemeriksaan selesai. Total antrean dipicu: {$dispatchedCount} tugas.");
    }
}