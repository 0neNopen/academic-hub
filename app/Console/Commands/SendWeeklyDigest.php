<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendWeeklyDigest extends Command
{
    protected $signature = 'assignments:send-weekly-digest {--user= : ID user spesifik (opsional)}';
    protected $description = 'Kirim ringkasan mingguan jadwal kuliah dan deadline tugas via Telegram';

    public function handle(TelegramService $telegramService): void
    {
        Carbon::setLocale('id');
        $tz = config('app.timezone', 'Asia/Jakarta');
        $now = Carbon::now($tz);
        $startOfWeek = $now->copy()->startOfWeek(); // Hari Senin 00:00:00
        $endOfWeek = $now->copy()->endOfWeek(); // Hari Minggu 23:59:59

        $this->info("Memulai pengiriman Weekly Digest (" . $startOfWeek->isoFormat('D MMM') . " - " . $endOfWeek->isoFormat('D MMM YYYY') . ")...");

        $query = User::with(['courses.assignments' => function ($q) use ($startOfWeek, $endOfWeek) {
            $q->where('status', '!=', 'completed')
              ->whereBetween('deadline', [$startOfWeek, $endOfWeek])
              ->orderBy('deadline', 'asc');
        }])
        ->whereNotNull('telegram_chat_id');

        if ($userId = $this->option('user')) {
            $query->where('id', $userId);
        }

        $users = $query->get();
        $sentCount = 0;

        foreach ($users as $user) {
            $weeklyAssignments = $user->courses->flatMap->assignments->sortBy('deadline');
            $courses = $user->courses;

            $message = "📅 <b>RINGKASAN AKADEMIK MINGGU INI</b>\n";
            $message .= "Periode: <i>" . $startOfWeek->isoFormat('D MMMM') . " – " . $endOfWeek->isoFormat('D MMMM YYYY') . "</i>\n\n";
            $message .= "Halo <b>" . htmlspecialchars($user->name) . "</b>! Semangat mengawali pekan ini! 💪\n\n";

            // Seksi 1: Deadline Tugas Minggu Ini
            $message .= "📝 <b>Tugas Kuliah Pekan Ini:</b>\n";
            if ($weeklyAssignments->isEmpty()) {
                $message .= "<i>✨ Tidak ada deadline tugas minggu ini! Waktu yang bagus untuk review materi atau santai sejenak.</i>\n\n";
            } else {
                $idx = 1;
                foreach ($weeklyAssignments as $assignment) {
                    $dl = Carbon::parse($assignment->deadline)->locale('id')->timezone($tz);
                    $courseName = $assignment->course?->name ?? 'Mata Kuliah';
                    $message .= "{$idx}. <b>" . htmlspecialchars($assignment->title) . "</b> (" . htmlspecialchars($courseName) . ")\n";
                    $message .= "   ⏰ " . $dl->isoFormat('dddd, D MMM - HH:mm') . " WIB\n";
                    $idx++;
                }
                $message .= "\n";
            }

            // Seksi 2: Ringkasan Mata Kuliah
            $message .= "📚 <b>Mata Kuliah Terdaftar:</b> " . $courses->count() . " Matkul\n";
            $message .= "💡 <i>Tips: Cicil tugasmu lebih awal agar akhir pekanmu tetap tenang!</i>\n\n";
            $message .= "🔗 Buka Academic Hub: " . config('app.url', 'https://academic-hub-ocw2.onrender.com') . "/dashboard";

            $success = $telegramService->sendMessage($user->telegram_chat_id, $message);
            if ($success) {
                $sentCount++;
                $this->info("✓ Weekly digest terkirim ke: {$user->name} ({$user->email})");
            } else {
                $this->warn("✗ Gagal mengirim weekly digest ke: {$user->name}");
            }
        }

        $this->info("Selesai! Total weekly digest terkirim: {$sentCount} pengguna.");
    }
}
