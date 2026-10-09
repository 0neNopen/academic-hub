<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\User;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        // Routing perintah teks
        if (str_starts_with($text, '/start')) {
            $this->handleStartCommand($chatId, $firstName, $user, $appUrl);
        } elseif (str_starts_with($text, '/jadwal')) {
            $this->handleJadwalCommand($chatId, $user, $appUrl);
        } elseif (str_starts_with($text, '/tugas')) {
            $this->handleTugasCommand($chatId, $user, $appUrl);
        } elseif (str_starts_with($text, '/id')) {
            $this->handleIdCommand($chatId);
        } elseif (str_starts_with($text, '/help') || str_starts_with($text, '/bantuan')) {
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
                 . "Bot ini siap otomatis mengirimkan:\n"
                 . "⏰ <b>Pengingat Deadline Tugas</b> (H-3, H-1, dan Hari H)\n"
                 . "📅 <b>Jadwal Kuliah Harian</b> setiap pagi pukul 06.00 WIB\n"
                 . "📊 <b>Rekapitulasi Tugas Mingguan</b> setiap hari Senin pukul 07.00 WIB\n\n"
                 . "<b>Perintah Cepat:</b>\n"
                 . "• /jadwal - Cek jadwal kuliah hari ini\n"
                 . "• /tugas - Cek daftar tugas kuliah aktif\n"
                 . "• /help - Bantuan perintah bot";

            $buttons = [
                [
                    ['text' => '🌐 Buka Dashboard Web', 'url' => "{$appUrl}/dashboard"],
                ],
            ];
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
        }

        $this->telegramService->sendMessage($chatId, $msg, $buttons);
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
                 . "🎉 <i>Tidak ada jadwal perkuliahan untuk hari ini. Selamat beristirahat atau belajar mandiri!</i>";
        } else {
            $msg = "📅 <b>Jadwal Kuliah Hari Ini ({$todayName}, {$todayDate})</b>\n"
                 . "Halo <b>{$user->name}</b>, berikut agenda perkuliahanmu hari ini:\n\n";

            foreach ($courses as $index => $course) {
                $no = $index + 1;
                $startTime = $course->start_time ? substr((string) $course->start_time, 0, 5) : '-';
                $endTime = $course->end_time ? substr((string) $course->end_time, 0, 5) : '-';
                $room = $course->room_location ?: 'Belum diset';
                $lecturer = $course->lecturer_name ?: 'Dosen belum diset';

                $msg .= "{$no}. <b>{$course->name}</b> ({$course->code})\n"
                      . "   ⏰ <b>Pukul:</b> {$startTime} - {$endTime} WIB\n"
                      . "   📍 <b>Ruang:</b> {$room}\n"
                      . "   👤 <b>Dosen:</b> {$lecturer}\n\n";
            }
        }

        $this->telegramService->sendMessage($chatId, $msg);
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

            foreach ($assignments as $index => $assignment) {
                $no = $index + 1;
                $deadline = Carbon::parse($assignment->deadline)->setTimezone('Asia/Jakarta');
                $diff = Carbon::now('Asia/Jakarta')->diffForHumans($deadline, [
                    'syntax' => Carbon::DIFF_RELATIVE_TO_NOW,
                    'parts' => 2,
                ]);

                $courseName = $assignment->course?->name ?? 'Mata Kuliah';
                $formattedDeadline = $deadline->locale('id')->isoFormat('D MMM YYYY, HH:mm');

                $msg .= "{$no}. <b>{$assignment->title}</b>\n"
                      . "   📚 {$courseName}\n"
                      . "   ⏳ Deadline: {$formattedDeadline} WIB ({$diff})\n\n";
            }
        }

        $buttons = [
            [
                ['text' => '🌐 Kelola Tugas di Web', 'url' => "{$appUrl}/dashboard"],
            ],
        ];

        $this->telegramService->sendMessage($chatId, $msg, $buttons);
    }

    protected function handleIdCommand(int|string $chatId): void
    {
        $msg = "🔢 <b>Chat ID Telegram Anda:</b>\n\n"
             . "<code>{$chatId}</code>\n\n"
             . "<i>Gunakan angka ID ini pada menu Pengaturan Profil di Academic Hub untuk menghubungkan bot notifikasi.</i>";

        $this->telegramService->sendMessage($chatId, $msg);
    }

    protected function handleHelpCommand(int|string $chatId): void
    {
        $msg = "🤖 <b>Panduan Perintah Academic Hub Bot</b>\n\n"
             . "Berikut daftar perintah yang dapat Anda gunakan:\n\n"
             . "• /start - Memulai bot & cek status koneksi akun\n"
             . "• /jadwal - Melihat jadwal kuliah hari ini\n"
             . "• /tugas - Melihat 5 tugas aktif terdekat\n"
             . "• /id - Melihat Chat ID Telegram Anda\n"
             . "• /help - Menampilkan panduan bantuan ini\n\n"
             . "<i>Bot akan otomatis mengingatkan Anda saat ada deadline tugas (H-3, H-1, Hari H) dan mengirimkan jadwal kuliah setiap pukul 06.00 WIB pagi.</i>";

        $this->telegramService->sendMessage($chatId, $msg);
    }

    protected function handleDefaultMessage(int|string $chatId, ?User $user): void
    {
        $name = $user ? $user->name : 'Mahasiswa';
        $msg = "Halo <b>{$name}</b>! 👋\n\n"
             . "Saya adalah bot otomatis Academic Hub. Ketik salah satu perintah berikut:\n"
             . "• /jadwal untuk melihat jadwal kuliah hari ini\n"
             . "• /tugas untuk melihat deadline tugas terdekat\n"
             . "• /help untuk bantuan lengkap";

        $this->telegramService->sendMessage($chatId, $msg);
    }
}
