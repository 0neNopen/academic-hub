<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendDailySchedule extends Command
{
    protected $signature = 'courses:send-daily-schedule {--user= : ID user spesifik (opsional)} {--day= : Nama hari spesifik (opsional, contoh: Senin)}';
    protected $description = 'Kirim ringkasan jadwal kuliah dan deadline tugas hari ini via Telegram';

    public function handle(TelegramService $telegramService): void
    {
        Carbon::setLocale('id');
        $tz = config('app.timezone', 'Asia/Jakarta');
        $now = Carbon::now($tz);

        $indonesianDays = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $todayDay = $this->option('day') ?: $indonesianDays[$now->dayOfWeek];

        $this->info("Memulai pengiriman Jadwal Kuliah Harian untuk hari {$todayDay} (" . $now->isoFormat('D MMMM YYYY') . ")...");

        $query = User::with([
            'courses' => function ($q) use ($todayDay) {
                $q->where('day_of_week', $todayDay)
                  ->orderBy('start_time', 'asc');
            },
            'courses.assignments' => function ($q) use ($now) {
                $q->where('status', '!=', 'completed')
                  ->whereDate('deadline', $now->toDateString())
                  ->orderBy('deadline', 'asc');
            }
        ])
        ->whereNotNull('telegram_chat_id');

        if ($userId = $this->option('user')) {
            $query->where('id', $userId);
        }

        $users = $query->get();
        $sentCount = 0;

        foreach ($users as $user) {
            $todayCourses = $user->courses;
            $todayAssignments = $user->courses->flatMap->assignments->sortBy('deadline');

            $message = "☀️ <b>JADWAL KULIAH HARI INI</b>\n";
            $message .= "Hari: <b>{$todayDay}, " . $now->isoFormat('D MMMM YYYY') . "</b>\n\n";
            $message .= "Selamat pagi <b>" . htmlspecialchars($user->name) . "</b>! Berikut agenda perkuliahanmu hari ini:\n\n";

            // Seksi 1: Jadwal Kuliah
            if ($todayCourses->isEmpty()) {
                $message .= "🏖️ <i>Tidak ada jadwal kuliah hari ini! Manfaatkan waktu untuk istirahat atau mengulang materi.</i>\n\n";
            } else {
                $message .= "📚 <b>Kelas Hari Ini (" . $todayCourses->count() . " Matkul):</b>\n";
                $idx = 1;
                foreach ($todayCourses as $course) {
                    $codeText = $course->code ? " [{$course->code}]" : "";
                    $message .= "{$idx}. <b>" . htmlspecialchars($course->name) . "</b>{$codeText}\n";
                    if ($course->start_time) {
                        $start = substr($course->start_time, 0, 5);
                        $end = $course->end_time ? substr($course->end_time, 0, 5) : 'Selesai';
                        $message .= "   🕒 Waktu: {$start} - {$end} WIB\n";
                    } else {
                        $message .= "   🕒 Waktu: Menyesuaikan Dosen\n";
                    }
                    if ($course->lecturer_name) {
                        $message .= "   👨‍🏫 Dosen: " . htmlspecialchars($course->lecturer_name) . "\n";
                    }
                    $idx++;
                }
                $message .= "\n";
            }

            // Seksi 2: Deadline Tugas Hari Ini
            if ($todayAssignments->isNotEmpty()) {
                $message .= "⚠️ <b>Deadline Tugas Hari Ini (" . $todayAssignments->count() . " Tugas):</b>\n";
                $tIdx = 1;
                foreach ($todayAssignments as $task) {
                    $dl = Carbon::parse($task->deadline)->locale('id')->timezone($tz);
                    $cName = $task->course?->name ?? 'Mata Kuliah';
                    $message .= "{$tIdx}. <b>" . htmlspecialchars($task->title) . "</b> ({$cName})\n";
                    $message .= "   ⏰ Jam: " . $dl->format('H:i') . " WIB\n";
                    if ($task->submission_url) {
                        $message .= "   🔗 <a href=\"" . htmlspecialchars($task->submission_url) . "\">Link Pengumpulan</a>\n";
                    }
                    $tIdx++;
                }
                $message .= "\n";
            }

            $message .= "🔗 Dashboard: " . config('app.url', 'https://academic-hub-ocw2.onrender.com') . "/dashboard";

            $success = $telegramService->sendMessage($user->telegram_chat_id, $message);
            if ($success) {
                $sentCount++;
                $this->info("✓ Jadwal harian terkirim ke: {$user->name} ({$user->email})");
            } else {
                $this->warn("✗ Gagal mengirim jadwal harian ke: {$user->name}");
            }
        }

        $this->info("Selesai! Total jadwal harian terkirim: {$sentCount} pengguna.");
    }
}
