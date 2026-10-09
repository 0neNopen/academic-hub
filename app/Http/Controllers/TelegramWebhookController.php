<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\User;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TelegramWebhookController extends Controller
{
    public function __construct(
        protected TelegramService $telegramService
    ) {}

    /**
     * Menangani pembaruan (incoming webhook) dari Telegram Bot API.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        // Ambil payload pesan dari Telegram
        $message = $payload['message'] ?? $payload['edited_message'] ?? null;
        if (!$message) {
            return response()->json(['status' => 'ignored']);
        }

        $chatId = $message['chat']['id'] ?? null;
        $text = trim((string) ($message['text'] ?? ''));
        $lowerText = strtolower($text);
        $firstName = $message['from']['first_name'] ?? 'Mahasiswa';

        if (!$chatId) {
            return response()->json(['status' => 'no_chat_id']);
        }

        $appUrl = config('app.url', 'https://academic-hub-ocw2.onrender.com');
        if (empty($appUrl) || str_contains($appUrl, 'localhost')) {
            $appUrl = 'https://academic-hub-ocw2.onrender.com';
        }

        // Cari user berdasarkan telegram_chat_id
        $user = User::where('telegram_chat_id', (string) $chatId)->first();

        // Routing perintah teks (mendukung slash command & teks tombol keyboard)
        if (str_starts_with($lowerText, '/start')) {
            $this->handleStartCommand($chatId, $firstName, $user, $appUrl);
        } elseif (str_starts_with($lowerText, '/semua_jadwal') || str_starts_with($lowerText, '/semuajadwal') || str_contains($lowerText, 'semua jadwal')) {
            $this->handleSemuaJadwalCommand($chatId, $user, $appUrl);
        } elseif (str_starts_with($lowerText, '/jadwal') || str_contains($lowerText, 'jadwal hari ini')) {
            $this->handleJadwalCommand($chatId, $user, $appUrl);
        } elseif (str_starts_with($lowerText, '/tugas') || str_contains($lowerText, 'tugas aktif')) {
            $this->handleTugasCommand($chatId, $user, $appUrl);
        } elseif (str_starts_with($lowerText, '/materi') || str_contains($lowerText, 'materi')) {
            $this->handleMateriCommand($chatId, $user, $appUrl);
        } elseif (str_starts_with($lowerText, '/id') || str_contains($lowerText, 'chat id')) {
            $this->handleIdCommand($chatId);
        } elseif (str_starts_with($lowerText, '/help') || str_starts_with($lowerText, '/bantuan') || str_contains($lowerText, 'bantuan')) {
            $this->handleHelpCommand($chatId);
        } else {
            $this->handleDefaultMessage($chatId, $user);
        }

        return response()->json(['status' => 'ok']);
    }

    protected function handleStartCommand(int|string $chatId, string $firstName, ?User $user, string $appUrl): void
    {
        if ($user) {
            $msg = "👋 Halo <b>{$user->name}</b>!\n\n"
                 . "✅ <b>Akun Telegram Anda Berhasil Terhubung</b> dengan Academic Hub.\n\n"
                 . "Ketuk tombol biru <b>[ Menu ]</b> di kiri bawah atau gunakan perintah berikut:\n"
                 . "• 📅 <b>/jadwal</b> - Cek kuliah hari ini\n"
                 . "• 🗓️ <b>/semua_jadwal</b> - Lihat seluruh matkul terdaftar\n"
                 . "• 📝 <b>/tugas</b> - Cek tugas mendekati deadline\n"
                 . "• 📑 <b>/materi</b> - Cek ringkasan berkas materi kuliah\n"
                 . "• ℹ️ <b>/help</b> - Daftar perintah bot\n\n"
                 . "🌐 <a href=\"{$appUrl}/dashboard\">Buka Website Academic Hub</a>";

            $this->telegramService->sendMessage($chatId, $msg, null, $this->telegramService->removeKeyboardMarkup());
        } else {
            $msg = "👋 Halo <b>{$firstName}</b>!\n\n"
                 . "Selamat datang di <b>Academic Hub Notifier</b>. 🎓\n\n"
                 . "🔢 <b>Chat ID Telegram Anda adalah:</b>\n"
                 . "<code>{$chatId}</code> <i>(ketuk untuk menyalin)</i>\n\n"
                 . "📌 <b>Cara menghubungkan dengan akun web Anda:</b>\n"
                 . "1. Salin angka Chat ID di atas.\n"
                 . "2. Masuk ke halaman profil: <a href=\"{$appUrl}/profile\">{$appUrl}/profile</a>\n"
                 . "3. Tempelkan ke kolom <b>Telegram Chat ID Anda</b> lalu klik Simpan.\n\n"
                 . "Setelah disimpan, bot ini akan otomatis aktif mengirimkan jadwal kuliah & pengingat deadline tugas Anda! 🚀";

            $buttons = [
                [
                    ['text' => '⚙️ Hubungkan di Profil Web', 'url' => "{$appUrl}/profile"],
                ],
            ];

            $this->telegramService->sendMessage($chatId, $msg, $buttons);
        }
    }

    protected function formatCourseTime(?string $start, ?string $end): string
    {
        if (empty($start) && empty($end)) {
            return 'Menyesuaikan Dosen';
        }

        $startTime = $start ? substr($start, 0, 5) : '';
        $endTime = $end ? substr($end, 0, 5) : '';

        if ($startTime && $endTime) {
            return "{$startTime} - {$endTime} WIB";
        } elseif ($startTime) {
            return "Mulai {$startTime} WIB";
        }

        return 'Menyesuaikan Dosen';
    }

    protected function handleJadwalCommand(int|string $chatId, ?User $user, string $appUrl): void
    {
        if (!$user) {
            $this->telegramService->sendMessage(
                $chatId,
                "⚠️ Akun Telegram Anda belum terhubung ke akun Academic Hub.\n\nSilakan hubungkan Chat ID Anda <code>{$chatId}</code> di halaman profil: <a href=\"{$appUrl}/profile\">{$appUrl}/profile</a>"
            );
            return;
        }

        $now = Carbon::now('Asia/Jakarta');
        $todayName = $now->locale('id')->isoFormat('dddd');
        $todayDate = $now->locale('id')->isoFormat('D MMMM YYYY');

        $courses = $user->courses()
            ->whereRaw('LOWER(day_of_week) = ?', [strtolower($todayName)])
            ->orderBy('start_time', 'asc')
            ->get();

        if ($courses->isEmpty()) {
            $msg = "📅 <b>Jadwal Kuliah Hari Ini ({$todayName}, {$todayDate})</b>\n\n"
                 . "🎉 <i>Tidak ada jadwal perkuliahan untuk hari ini. Selamat beristirahat atau belajar mandiri!</i>\n\n"
                 . "💡 Ketuk <b>Semua Jadwal</b> untuk melihat jadwal hari lain.";
        } else {
            $msg = "📅 <b>Jadwal Kuliah Hari Ini ({$todayName}, {$todayDate})</b>\n"
                 . "Halo <b>{$user->name}</b>, berikut agenda perkuliahanmu hari ini:\n\n";

            foreach ($courses as $index => $course) {
                $no = $index + 1;
                $timeText = $this->formatCourseTime($course->start_time, $course->end_time);
                $lecturer = $course->lecturer_name ?: 'Dosen belum diset';
                $code = $course->code ? " ({$course->code})" : "";

                $msg .= "{$no}. <b>{$course->name}</b>{$code}\n"
                      . "   ⏰ Waktu: {$timeText}\n"
                      . "   👤 Dosen: {$lecturer}\n\n";
            }
        }

        $this->telegramService->sendMessage($chatId, $msg, null, $this->telegramService->removeKeyboardMarkup());
    }

    protected function handleSemuaJadwalCommand(int|string $chatId, ?User $user, string $appUrl): void
    {
        if (!$user) {
            $this->telegramService->sendMessage(
                $chatId,
                "⚠️ Akun Telegram Anda belum terhubung ke akun Academic Hub.\n\nSilakan hubungkan Chat ID Anda <code>{$chatId}</code> di halaman profil: <a href=\"{$appUrl}/profile\">{$appUrl}/profile</a>"
            );
            return;
        }

        $allCourses = $user->courses()->orderBy('start_time', 'asc')->get();

        if ($allCourses->isEmpty()) {
            $msg = "🗓️ <b>Seluruh Jadwal Perkuliahan Terdaftar</b>\n\n"
                 . "Belum ada mata kuliah yang didaftarkan di website.\n\n"
                 . "👉 Silakan tambahkan mata kuliah Anda melalui website: <a href=\"{$appUrl}/dashboard\">{$appUrl}/dashboard</a>";
            $this->telegramService->sendMessage($chatId, $msg, null, $this->telegramService->removeKeyboardMarkup());
            return;
        }

        $dayOrder = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        $grouped = [];

        foreach ($allCourses as $c) {
            $day = ucfirst(strtolower(trim((string) $c->day_of_week))) ?: 'Lainnya';
            $grouped[$day][] = $c;
        }

        $msg = "🗓️ <b>Seluruh Jadwal Perkuliahan Anda</b>\n"
             . "Halo <b>{$user->name}</b>, berikut ringkasan seluruh matkul yang terdaftar di Academic Hub:\n\n";

        foreach ($dayOrder as $day) {
            if (!empty($grouped[$day])) {
                $msg .= "📌 <b>" . strtoupper($day) . "</b>\n";
                foreach ($grouped[$day] as $course) {
                    $timeText = $this->formatCourseTime($course->start_time, $course->end_time);
                    $code = $course->code ? " ({$course->code})" : "";
                    $lecturer = $course->lecturer_name ?: 'Dosen belum diset';

                    $msg .= "• <b>{$course->name}</b>{$code}\n"
                          . "   ⏰ Waktu: {$timeText}\n"
                          . "   👤 Dosen: {$lecturer}\n\n";
                }
            }
        }

        // Cetak hari lainnya jika ada yang tidak standar
        foreach ($grouped as $day => $courses) {
            if (!in_array($day, $dayOrder) && !empty($courses)) {
                $msg .= "📌 <b>" . strtoupper($day) . "</b>\n";
                foreach ($courses as $course) {
                    $timeText = $this->formatCourseTime($course->start_time, $course->end_time);
                    $code = $course->code ? " ({$course->code})" : "";
                    $lecturer = $course->lecturer_name ?: 'Dosen belum diset';

                    $msg .= "• <b>{$course->name}</b>{$code}\n"
                          . "   ⏰ Waktu: {$timeText}\n"
                          . "   👤 Dosen: {$lecturer}\n\n";
                }
            }
        }

        $msg .= "🌐 <a href=\"{$appUrl}/dashboard\">Kelola Mata Kuliah di Website</a>";

        $this->telegramService->sendMessage($chatId, $msg, null, $this->telegramService->removeKeyboardMarkup());
    }

    protected function handleTugasCommand(int|string $chatId, ?User $user, string $appUrl): void
    {
        if (!$user) {
            $this->telegramService->sendMessage(
                $chatId,
                "⚠️ Akun Telegram Anda belum terhubung ke akun Academic Hub.\n\nSilakan hubungkan Chat ID Anda <code>{$chatId}</code> di profil web: <a href=\"{$appUrl}/profile\">{$appUrl}/profile</a>"
            );
            return;
        }

        $assignments = Assignment::whereIn('course_id', $user->courses()->pluck('id'))
            ->where('status', '!=', 'completed')
            ->orderBy('deadline', 'asc')
            ->with('course')
            ->take(5)
            ->get();

        if ($assignments->isEmpty()) {
            $msg = "📝 <b>Daftar Tugas Aktif:</b>\n\n"
                 . "🎉 <i>Hebat! Tidak ada tugas aktif yang mendekati deadline saat ini. Semua tugas telah terselesaikan.</i>";
        } else {
            $msg = "📝 <b>Daftar Tugas Mendekati Deadline:</b>\n\n";
            $now = Carbon::now('Asia/Jakarta');

            foreach ($assignments as $index => $assignment) {
                $no = $index + 1;
                $deadline = Carbon::parse($assignment->deadline)->setTimezone('Asia/Jakarta');

                if ($deadline->isPast()) {
                    $diffText = 'Terlewat ' . $deadline->diffForHumans($now, [
                        'syntax' => Carbon::DIFF_RELATIVE_TO_NOW,
                        'parts' => 2,
                    ]);
                } else {
                    $rawDiff = $deadline->diffForHumans($now, [
                        'syntax' => Carbon::DIFF_RELATIVE_TO_NOW,
                        'parts' => 2,
                    ]);
                    $cleanDiff = str_replace('dari sekarang', 'lagi', $rawDiff);
                    $diffText = "sisa {$cleanDiff}";
                }

                $courseName = $assignment->course?->name ?? 'Mata Kuliah';
                $formattedDeadline = $deadline->locale('id')->isoFormat('D MMM YYYY, HH:mm');

                $title = htmlspecialchars($assignment->title);
                $courseNameEscaped = htmlspecialchars($courseName);

                $msg .= "{$no}. <b>{$title}</b>\n"
                      . "   📚 {$courseNameEscaped}\n"
                      . "   ⏳ Deadline: {$formattedDeadline} WIB ({$diffText})\n";

                if (!empty($assignment->description)) {
                    $cleanDesc = htmlspecialchars(trim($assignment->description));
                    $descPreview = Str::limit($cleanDesc, 140);
                    $msg .= "   📝 Keterangan: <i>{$descPreview}</i>\n";
                }

                if (!empty($assignment->submission_url)) {
                    $url = htmlspecialchars($assignment->submission_url);
                    $msg .= "   🔗 <a href=\"{$url}\">Link Pengumpulan</a>\n";
                }

                $msg .= "\n";
            }
        }

        $msg .= "🌐 <a href=\"{$appUrl}/dashboard\">Buka Website untuk Kumpul / Cek Detail</a>";

        $this->telegramService->sendMessage($chatId, $msg, null, $this->telegramService->removeKeyboardMarkup());
    }

    protected function handleMateriCommand(int|string $chatId, ?User $user, string $appUrl): void
    {
        if (!$user) {
            $this->telegramService->sendMessage(
                $chatId,
                "⚠️ Akun Telegram Anda belum terhubung ke akun Academic Hub.\n\nSilakan hubungkan Chat ID Anda <code>{$chatId}</code> di profil web: <a href=\"{$appUrl}/profile\">{$appUrl}/profile</a>"
            );
            return;
        }

        $courses = $user->courses()->withCount('materials')->orderBy('name', 'asc')->get();

        if ($courses->isEmpty()) {
            $msg = "📑 <b>Arsip Berkas Materi Perkuliahan</b>\n\n"
                 . "Belum ada mata kuliah yang didaftarkan di website.\n\n"
                 . "👉 Tambahkan mata kuliah Anda: <a href=\"{$appUrl}/dashboard\">{$appUrl}/dashboard</a>";
            $this->telegramService->sendMessage($chatId, $msg, null, $this->telegramService->removeKeyboardMarkup());
            return;
        }

        $msg = "📑 <b>Arsip Berkas Materi Perkuliahan</b>\n"
             . "Halo <b>{$user->name}</b>, berikut ringkasan berkas materi kuliah Anda:\n\n";

        $totalMaterials = 0;
        foreach ($courses as $course) {
            $count = (int) $course->materials_count;
            $totalMaterials += $count;
            $statusText = $count > 0 ? "<b>{$count} berkas</b> tersimpan" : "<i>Belum ada berkas</i>";

            $latest = $course->materials()->orderBy('meeting_number', 'desc')->first();
            $latestText = ($latest && $latest->meeting_number) ? " <i>(P{$latest->meeting_number})</i>" : "";

            $msg .= "• <b>{$course->name}</b>: {$statusText}{$latestText}\n";
        }

        $msg .= "\n📊 <b>Total Materi:</b> {$totalMaterials} berkas tersimpan\n\n"
              . "🌐 <a href=\"{$appUrl}/dashboard\">Buka Website untuk Pratinjau & Unduh Berkas</a>";

        $this->telegramService->sendMessage($chatId, $msg, null, $this->telegramService->removeKeyboardMarkup());
    }

    protected function handleIdCommand(int|string $chatId): void
    {
        $msg = "🔢 <b>Chat ID Telegram Anda:</b>\n\n"
             . "<code>{$chatId}</code>\n\n"
             . "<i>Gunakan angka ID ini pada menu Pengaturan Profil di Academic Hub untuk menghubungkan bot notifikasi.</i>";

        $this->telegramService->sendMessage($chatId, $msg, null, $this->telegramService->removeKeyboardMarkup());
    }

    protected function handleHelpCommand(int|string $chatId): void
    {
        $msg = "🤖 <b>Panduan Perintah Academic Hub Bot</b>\n\n"
             . "Ketuk tombol biru <b>[ Menu ]</b> di pojok kiri bawah atau kirim perintah berikut:\n\n"
             . "• 📅 <b>/jadwal</b> - Jadwal kuliah hari ini\n"
             . "• 🗓️ <b>/semua_jadwal</b> - Rangkuman semua jadwal kuliah Anda\n"
             . "• 📝 <b>/tugas</b> - Daftar tugas aktif & hitung mundur deadline\n"
             . "• 📑 <b>/materi</b> - Cek berkas materi per mata kuliah\n"
             . "• 🔢 <b>/id</b> - Melihat nomor Chat ID Telegram Anda\n"
             . "• ℹ️ <b>/help</b> - Panduan & bantuan perintah bot\n\n"
             . "<i>Bot akan otomatis mengingatkan deadline tugas (H-24 & H-3) serta menyapa jadwal kuliah setiap pagi pukul 06.00 WIB.</i>";

        $this->telegramService->sendMessage($chatId, $msg, null, $this->telegramService->removeKeyboardMarkup());
    }

    protected function handleDefaultMessage(int|string $chatId, ?User $user): void
    {
        $name = $user ? $user->name : 'Mahasiswa';
        $msg = "Halo <b>{$name}</b>! 👋\n\n"
             . "Silakan ketuk tombol biru <b>[ Menu ]</b> di pojok kiri bawah atau gunakan perintah berikut:\n"
             . "• 📅 <b>/jadwal</b> - Jadwal kuliah hari ini\n"
             . "• 🗓️ <b>/semua_jadwal</b> - Rangkuman semua jadwal\n"
             . "• 📝 <b>/tugas</b> - Tugas aktif mendekati deadline\n"
             . "• 📑 <b>/materi</b> - Ringkasan berkas materi kuliah\n"
             . "• ℹ️ <b>/help</b> - Panduan bot";

        $this->telegramService->sendMessage($chatId, $msg, null, $this->telegramService->removeKeyboardMarkup());
    }
}
