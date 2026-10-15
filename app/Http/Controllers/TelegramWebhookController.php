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
        $webhookSecret = (string) config('services.telegram.webhook_secret', '');
        if ($webhookSecret !== '') {
            $headerSecret = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');
            if (! hash_equals($webhookSecret, $headerSecret)) {
                abort(403, 'Unauthorized webhook request.');
            }
        }

        $payload = $request->all();

        $appUrl = config('app.url', 'https://academic-hub-ocw2.onrender.com');
        if (empty($appUrl) || str_contains($appUrl, 'localhost')) {
            $appUrl = 'https://academic-hub-ocw2.onrender.com';
        }

        // 1. Penanganan Callback Query dari tombol Inline Keyboard
        $callbackQuery = $payload['callback_query'] ?? null;
        if ($callbackQuery) {
            $callbackId = $callbackQuery['id'] ?? '';
            $chatId = $callbackQuery['message']['chat']['id'] ?? $callbackQuery['from']['id'] ?? null;
            $messageId = $callbackQuery['message']['message_id'] ?? null;
            $data = trim((string) ($callbackQuery['data'] ?? ''));

            if ($callbackId) {
                $this->telegramService->answerCallbackQuery($callbackId);
            }

            if (!$chatId) {
                return response()->json(['status' => 'no_chat_id']);
            }

            $user = User::where('telegram_chat_id', (string) $chatId)->first();

            if (str_starts_with($data, 'materi_course:')) {
                $courseId = (int) substr($data, strlen('materi_course:'));
                $this->handleMateriDetail($chatId, $user, $courseId, $appUrl, $messageId);
                return response()->json(['status' => 'ok']);
            } elseif ($data === 'materi_list') {
                $this->handleMateriCommand($chatId, $user, $appUrl, $messageId);
                return response()->json(['status' => 'ok']);
            }

            return response()->json(['status' => 'callback_handled']);
        }

        // 2. Ambil payload pesan dari Telegram
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

        // Cari user berdasarkan telegram_chat_id
        $user = User::where('telegram_chat_id', (string) $chatId)->first();

        // Routing perintah teks (mendukung slash command & teks tombol keyboard)
        if (str_starts_with($lowerText, '/start')) {
            $this->handleStartCommand($chatId, $firstName, $user, $appUrl);
        } elseif (str_starts_with($lowerText, '/reset') || str_starts_with($lowerText, '/lupasandi')) {
            $this->handleResetPasswordCommand($chatId, $user, $text, $appUrl);
        } elseif (str_starts_with($lowerText, '/semua_jadwal') || str_starts_with($lowerText, '/semuajadwal') || str_contains($lowerText, 'semua jadwal')) {
            $this->handleSemuaJadwalCommand($chatId, $user, $appUrl);
        } elseif (str_starts_with($lowerText, '/jadwal') || str_contains($lowerText, 'jadwal hari ini')) {
            $this->handleJadwalCommand($chatId, $user, $appUrl);
        } elseif (str_starts_with($lowerText, '/tugas') || str_contains($lowerText, 'tugas aktif')) {
            $this->handleTugasCommand($chatId, $user, $appUrl);
        } elseif (str_starts_with($lowerText, '/materi') || $lowerText === 'materi' || str_contains($lowerText, 'materi kuliah')) {
            if (preg_match('/^\/materi[_ ](\d+)$/i', $text, $matches)) {
                $this->handleMateriDetail($chatId, $user, (int) $matches[1], $appUrl);
            } else {
                $this->handleMateriCommand($chatId, $user, $appUrl);
            }
        } elseif (str_starts_with($lowerText, '/portal') || str_contains($lowerText, 'portal kampus') || $lowerText === 'portal') {
            $this->handlePortalCommand($chatId, $user, $appUrl);
        } elseif (str_starts_with($lowerText, '/id') || str_contains($lowerText, 'chat id')) {
            $this->handleIdCommand($chatId);
        } elseif (str_starts_with($lowerText, '/help') || str_starts_with($lowerText, '/bantuan') || str_contains($lowerText, 'bantuan')) {
            $this->handleHelpCommand($chatId, $user, $appUrl);
        } else {
            $this->handleDefaultMessage($chatId, $user, $appUrl);
        }

        return response()->json(['status' => 'ok']);
    }

    protected function handleStartCommand(int|string $chatId, string $firstName, ?User $user, string $appUrl): void
    {
        $safeFirstName = htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8');
        if ($user) {
            $safeName = htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8');
            $msg = "👋 Halo <b>{$safeName}</b>!\n\n"
                 . "✅ <b>Akun Telegram Anda Berhasil Terhubung</b> dengan Academic Hub.\n\n"
                 . "Ketuk tombol biru <b>[ Menu ]</b> di kiri bawah atau gunakan perintah berikut:\n"
                 . "• 📅 <b>/jadwal</b> - Cek kuliah hari ini\n"
                 . "• 🗓️ <b>/semua_jadwal</b> - Lihat seluruh matkul terdaftar\n"
                 . "• 📝 <b>/tugas</b> - Cek tugas mendekati deadline\n"
                 . "• 📑 <b>/materi</b> - Cek ringkasan berkas materi kuliah\n"
                 . "• 🏛️ <b>/portal</b> - Akses cepat portal kampus Anda\n"
                 . "• ℹ️ <b>/help</b> - Daftar perintah bot\n\n"
                 . "🌐 <a href=\"{$appUrl}/dashboard\">Buka Website Academic Hub</a>";

            $this->telegramService->sendMessage($chatId, $msg, null, $this->telegramService->removeKeyboardMarkup());
        } else {
            $msg = "👋 Halo <b>{$safeFirstName}</b>!\n\n"
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
        $safeName = htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8');

        $allUserCoursesCount = $user->courses()->count();
        if ($allUserCoursesCount === 0) {
            $msg = "📅 <b>Jadwal Kuliah Hari Ini ({$todayName}, {$todayDate})</b>\n\n"
                 . "Halo <b>{$safeName}</b>!\n\n"
                 . "📌 <b>Jadwal Kuliah Belum Terdaftar:</b>\n"
                 . "Akun Academic Hub kamu saat ini belum memiliki daftar mata kuliah.\n\n"
                 . "Yuk lengkapi jadwal kuliah semester ini melalui website agar bot dapat menampilkan jadwal harianmu:\n"
                 . "🔗 <a href=\"{$appUrl}/dashboard\">Buka Dashboard Academic Hub</a>";
            $this->telegramService->sendMessage($chatId, $msg, null, $this->telegramService->removeKeyboardMarkup());
            return;
        }

        $courses = $user->courses()
            ->whereRaw('LOWER(day_of_week) = ?', [strtolower($todayName)])
            ->orderBy('start_time', 'asc')
            ->get();

        $isWeekend = in_array(strtolower($todayName), ['sabtu', 'minggu']);

        if ($courses->isEmpty()) {
            if ($isWeekend) {
                $weekendGreeting = strtolower($todayName) === 'sabtu'
                    ? "🌴 <b>Happy Weekend! (Sabtu Santai)</b>"
                    : "☕ <b>Selamat Hari Minggu!</b>";
                $msg = "📅 <b>Jadwal Kuliah Hari Ini ({$todayName}, {$todayDate})</b>\n\n"
                     . "{$weekendGreeting}\n"
                     . "Halo <b>{$safeName}</b>! Tidak ada jadwal perkuliahan hari ini 🥳\n"
                     . "Manfaatkan akhir pekanmu untuk istirahat, refreshing, atau bersantai.\n\n"
                     . "💡 <b>Aksi Cepat:</b>\n"
                     . "• <b>/tugas</b> - Cek status tugas aktif\n"
                     . "• <b>/semua_jadwal</b> - Intip jadwal kuliah sepekan";
            } else {
                $msg = "📅 <b>Jadwal Kuliah Hari Ini ({$todayName}, {$todayDate})</b>\n\n"
                     . "🎉 <b>Hari Bebas Kuliah (Free Day)!</b>\n"
                     . "<i>Tidak ada jadwal perkuliahan untuk hari ini. Selamat beristirahat atau belajar mandiri!</i>\n\n"
                     . "💡 Ketuk <b>/tugas</b> untuk cek tugas aktif atau <b>/semua_jadwal</b> untuk melihat jadwal hari lain.";
            }
        } else {
            $msg = "📅 <b>Jadwal Kuliah Hari Ini ({$todayName}, {$todayDate})</b>\n"
                 . "Halo <b>{$safeName}</b>, berikut agenda perkuliahanmu hari ini:\n\n";

            foreach ($courses as $index => $course) {
                $no = $index + 1;
                $timeText = $this->formatCourseTime($course->start_time, $course->end_time);
                $courseName = htmlspecialchars($course->name, ENT_QUOTES, 'UTF-8');
                $lecturer = htmlspecialchars($course->lecturer_name ?: 'Dosen belum diset', ENT_QUOTES, 'UTF-8');
                $code = $course->code ? " (" . htmlspecialchars($course->code, ENT_QUOTES, 'UTF-8') . ")" : "";
                $roomText = $course->room ? "   📍 Ruang: " . htmlspecialchars($course->room, ENT_QUOTES, 'UTF-8') . "\n" : "";

                $msg .= "{$no}. <b>{$courseName}</b>{$code}\n"
                      . "   ⏰ Waktu: {$timeText}\n"
                      . "   👤 Dosen: {$lecturer}\n"
                      . $roomText . "\n";
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

        $safeName = htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8');
        $msg = "🗓️ <b>Seluruh Jadwal Perkuliahan Anda</b>\n"
             . "Halo <b>{$safeName}</b>, berikut ringkasan seluruh matkul yang terdaftar di Academic Hub:\n\n";

        foreach ($dayOrder as $day) {
            if (!empty($grouped[$day])) {
                $msg .= "📌 <b>" . strtoupper($day) . "</b>\n";
                foreach ($grouped[$day] as $course) {
                    $timeText = $this->formatCourseTime($course->start_time, $course->end_time);
                    $code = $course->code ? " (" . htmlspecialchars($course->code, ENT_QUOTES, 'UTF-8') . ")" : "";
                    $courseName = htmlspecialchars($course->name, ENT_QUOTES, 'UTF-8');
                    $lecturer = htmlspecialchars($course->lecturer_name ?: 'Dosen belum diset', ENT_QUOTES, 'UTF-8');
                    $roomText = $course->room ? "   📍 Ruang: " . htmlspecialchars($course->room, ENT_QUOTES, 'UTF-8') . "\n" : "";

                    $msg .= "• <b>{$courseName}</b>{$code}\n"
                          . "   ⏰ Waktu: {$timeText}\n"
                          . "   👤 Dosen: {$lecturer}\n"
                          . $roomText . "\n";
                }
            }
        }

        // Cetak hari lainnya jika ada yang tidak standar
        foreach ($grouped as $day => $courses) {
            if (!in_array($day, $dayOrder) && !empty($courses)) {
                $msg .= "📌 <b>" . strtoupper($day) . "</b>\n";
                foreach ($courses as $course) {
                    $timeText = $this->formatCourseTime($course->start_time, $course->end_time);
                    $code = $course->code ? " (" . htmlspecialchars($course->code, ENT_QUOTES, 'UTF-8') . ")" : "";
                    $courseName = htmlspecialchars($course->name, ENT_QUOTES, 'UTF-8');
                    $lecturer = htmlspecialchars($course->lecturer_name ?: 'Dosen belum diset', ENT_QUOTES, 'UTF-8');
                    $roomText = $course->room ? "   📍 Ruang: " . htmlspecialchars($course->room, ENT_QUOTES, 'UTF-8') . "\n" : "";

                    $msg .= "• <b>{$courseName}</b>{$code}\n"
                          . "   ⏰ Waktu: {$timeText}\n"
                          . "   👤 Dosen: {$lecturer}\n"
                          . $roomText . "\n";
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
                $typeTag = $assignment->assignment_type === 'group' ? ' [👥 Kelompok]' : ' [👤 Individu]';
                $priorityTag = match ($assignment->priority) {
                    'high' => ' [🔴 Prioritas Tinggi]',
                    'low' => ' [🟢 Prioritas Rendah]',
                    default => ' [🟡 Prioritas Sedang]',
                };

                $msg .= "{$no}. <b>{$title}</b>{$typeTag}{$priorityTag}\n"
                      . "   📚 {$courseNameEscaped}\n"
                      . "   ⏳ Deadline: {$formattedDeadline} WIB ({$diffText})\n";

                if (!empty($assignment->description)) {
                    $cleanDesc = htmlspecialchars(trim($assignment->description));
                    $descPreview = Str::limit($cleanDesc, 140);
                    $msg .= "   📝 Keterangan: <i>{$descPreview}</i>\n";
                }

                $assignmentLinks = $assignment->all_links;
                if (!empty($assignmentLinks)) {
                    if (count($assignmentLinks) === 1) {
                        $lTitle = htmlspecialchars($assignmentLinks[0]['title'] ?? 'Link Pengumpulan');
                        $lUrl = htmlspecialchars($assignmentLinks[0]['url']);
                        $msg .= "   🔗 <a href=\"{$lUrl}\">{$lTitle}</a>\n";
                    } else {
                        $msg .= "   🔗 <b>Tautan Terkait:</b>\n";
                        foreach ($assignmentLinks as $l) {
                            $lTitle = htmlspecialchars($l['title'] ?? 'Tautan');
                            $lUrl = htmlspecialchars($l['url']);
                            $msg .= "      • <a href=\"{$lUrl}\">{$lTitle}</a>\n";
                        }
                    }
                }

                $msg .= "\n";
            }
        }

        $msg .= "🌐 <a href=\"{$appUrl}/dashboard\">Buka Website untuk Kumpul / Cek Detail</a>";

        $this->telegramService->sendMessage($chatId, $msg, null, $this->telegramService->removeKeyboardMarkup());
    }

    protected function handleMateriCommand(int|string $chatId, ?User $user, string $appUrl, ?int $messageId = null): void
    {
        if (!$user) {
            $this->telegramService->sendMessage(
                $chatId,
                "⚠️ Akun Telegram Anda belum terhubung ke akun Academic Hub.\n\nSilakan hubungkan Chat ID Anda <code>{$chatId}</code> di profil web: <a href=\"{$appUrl}/profile\">{$appUrl}/profile</a>"
            );
            return;
        }

        $courses = $user->courses()
            ->withCount('materials')
            ->withMax('materials', 'meeting_number')
            ->orderBy('name', 'asc')
            ->get();

        if ($courses->isEmpty()) {
            $msg = "📑 <b>Arsip Berkas Materi Perkuliahan</b>\n\n"
                 . "Belum ada mata kuliah yang didaftarkan di website.\n\n"
                 . "👉 Tambahkan mata kuliah Anda: <a href=\"{$appUrl}/dashboard\">{$appUrl}/dashboard</a>";
            $this->telegramService->sendMessage($chatId, $msg, null, $this->telegramService->removeKeyboardMarkup());
            return;
        }

        $safeName = htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8');
        $msg = "📑 <b>Arsip Berkas Materi Perkuliahan</b>\n"
             . "Halo <b>{$safeName}</b>, berikut ringkasan berkas materi kuliah Anda:\n\n";

        $totalMaterials = 0;
        $buttons = [];
        foreach ($courses as $course) {
            $count = (int) $course->materials_count;
            $totalMaterials += $count;
            $statusText = $count > 0 ? "<b>{$count} berkas</b> tersimpan" : "<i>Belum ada berkas</i>";

            $latestMeeting = $course->materials_max_meeting_number;
            $latestText = $latestMeeting ? " <i>(P{$latestMeeting})</i>" : "";
            $safeCourseName = htmlspecialchars($course->name, ENT_QUOTES, 'UTF-8');

            $msg .= "• <b>{$safeCourseName}</b>: {$statusText}{$latestText}\n";

            // Tombol interaktif per matkul: HANYA TEKS BERSIH DAN RAPI (TANPA EMOJI)
            $btnLabel = $course->name . ($count > 0 ? " ({$count} berkas)" : " (0 berkas)");
            $buttons[] = [
                [
                    'text' => Str::limit($btnLabel, 35),
                    'callback_data' => "materi_course:{$course->id}",
                ],
            ];
        }

        $msg .= "\n📊 <b>Total Materi:</b> {$totalMaterials} berkas tersimpan\n\n"
              . "💡 <i>Ketuk tombol mata kuliah di bawah untuk membuka rincian berkas, dokumen, dan tautan materi secara detail.</i>";

        $buttons[] = [
            [
                'text' => 'Buka Dashboard di Website',
                'url' => "{$appUrl}/dashboard",
            ],
        ];

        if ($messageId) {
            $success = $this->telegramService->editMessageText($chatId, $messageId, $msg, $buttons);
            if (!$success) {
                $this->telegramService->sendMessage($chatId, $msg, $buttons);
            }
        } else {
            $this->telegramService->sendMessage($chatId, $msg, $buttons);
        }
    }

    protected function handleMateriDetail(int|string $chatId, ?User $user, int $courseId, string $appUrl, ?int $messageId = null): void
    {
        if (!$user) {
            $this->telegramService->sendMessage(
                $chatId,
                "⚠️ Akun Telegram Anda belum terhubung ke akun Academic Hub.\n\nSilakan hubungkan Chat ID Anda <code>{$chatId}</code> di profil web: <a href=\"{$appUrl}/profile\">{$appUrl}/profile</a>"
            );
            return;
        }

        $course = $user->courses()
            ->with(['materials' => function ($q) {
                $q->orderBy('meeting_number', 'asc')->orderBy('created_at', 'asc');
            }])
            ->find($courseId);

        if (!$course) {
            $msg = "⚠️ Mata kuliah tidak ditemukan atau Anda tidak memiliki akses.";
            $buttons = [
                [
                    ['text' => 'Kembali ke Daftar Matkul', 'callback_data' => 'materi_list'],
                ],
            ];
            if ($messageId) {
                $this->telegramService->editMessageText($chatId, $messageId, $msg, $buttons);
            } else {
                $this->telegramService->sendMessage($chatId, $msg, $buttons);
            }
            return;
        }

        $courseName = htmlspecialchars($course->name);
        $courseCode = $course->code ? ' (' . htmlspecialchars($course->code) . ')' : '';
        $lecturer = $course->lecturer_name ? htmlspecialchars($course->lecturer_name) : 'Dosen belum diset';
        $materials = $course->materials;
        $total = $materials->count();

        $msg = "📚 <b>Materi Kuliah: {$courseName}</b>{$courseCode}\n"
             . "👤 <b>Dosen:</b> {$lecturer}\n"
             . "📊 <b>Total Materi:</b> {$total} berkas tersimpan\n"
             . "──────────────────────────────\n\n";

        if ($materials->isEmpty()) {
            $msg .= "📭 <i>Belum ada berkas atau tautan materi yang diunggah untuk mata kuliah ini.</i>\n\n"
                  . "💡 <i>Anda dapat mengunggah berkas slide, PDF, atau tautan Drive/LMS melalui website Academic Hub.</i>\n";
        } else {
            foreach ($materials as $index => $material) {
                $no = $index + 1;
                $title = htmlspecialchars($material->title);
                $meetingText = $material->meeting_number ? "Pertemuan ke-{$material->meeting_number}" : "Materi Umum";
                $createdDate = $material->created_at ? $material->created_at->locale('id')->isoFormat('D MMMM YYYY') : '-';

                $msg .= "<b>{$no}. {$title}</b>\n"
                      . "   🏷️ <i>{$meetingText}</i>\n"
                      . "   📅 Diunggah: {$createdDate}\n";

                if (!empty($material->file_path)) {
                    $ext = strtolower(pathinfo($material->file_path, PATHINFO_EXTENSION));
                    $icon = match ($ext) {
                        'pdf' => '📄',
                        'ppt', 'pptx' => '📊',
                        'doc', 'docx' => '📝',
                        'zip', 'rar' => '🗂️',
                        'jpg', 'jpeg', 'png', 'webp' => '🖼️',
                        default => '📎',
                    };
                    $fileTypeLabel = strtoupper($ext) ?: 'FILE';
                    $cleanFilename = htmlspecialchars(basename($material->file_path));
                    $msg .= "   {$icon} Berkas: <code>{$cleanFilename}</code> ({$fileTypeLabel})\n";
                }

                $links = $material->all_links;
                if (!empty($links)) {
                    if (count($links) === 1) {
                        $lTitle = htmlspecialchars($links[0]['title'] ?? 'Tautan Materi');
                        $lUrl = htmlspecialchars($links[0]['url']);
                        $msg .= "   🔗 <a href=\"{$lUrl}\">{$lTitle}</a>\n";
                    } else {
                        $msg .= "   🔗 <b>Tautan Terkait:</b>\n";
                        foreach ($links as $l) {
                            $lTitle = htmlspecialchars($l['title'] ?? 'Tautan');
                            $lUrl = htmlspecialchars($l['url']);
                            $msg .= "      • <a href=\"{$lUrl}\">{$lTitle}</a>\n";
                        }
                    }
                }

                if (!empty($material->notes)) {
                    $cleanNotes = htmlspecialchars(trim($material->notes));
                    $msg .= "   📝 Catatan: <i>" . Str::limit($cleanNotes, 160) . "</i>\n";
                }

                $msg .= "\n";
            }
        }

        // Tombol aksi: TANPA EMOJI, TEKS BERSIH & RAPI
        $buttons = [
            [
                ['text' => 'Kembali ke Daftar Matkul', 'callback_data' => 'materi_list'],
            ],
            [
                ['text' => 'Buka Mata Kuliah di Website', 'url' => "{$appUrl}/courses/{$course->id}"],
            ],
        ];

        if ($messageId) {
            $success = $this->telegramService->editMessageText($chatId, $messageId, $msg, $buttons);
            if (!$success) {
                $this->telegramService->sendMessage($chatId, $msg, $buttons);
            }
        } else {
            $this->telegramService->sendMessage($chatId, $msg, $buttons);
        }
    }

    protected function handleIdCommand(int|string $chatId): void
    {
        $msg = "🔢 <b>Chat ID Telegram Anda:</b>\n\n"
             . "<code>{$chatId}</code>\n\n"
             . "<i>Gunakan angka ID ini pada menu Pengaturan Profil di Academic Hub untuk menghubungkan bot notifikasi.</i>";

        $this->telegramService->sendMessage($chatId, $msg, null, $this->telegramService->removeKeyboardMarkup());
    }

    protected function handleHelpCommand(int|string $chatId, ?User $user = null, ?string $appUrl = null): void
    {
        if ($user) {
            $reminderTime = $user->schedule_reminder_time ?: '06:00';

            $earlyHours = ($user->task_reminder_early !== null && is_numeric($user->task_reminder_early))
                ? (int) $user->task_reminder_early
                : 24;
            $lateHours = ($user->task_reminder_late !== null && is_numeric($user->task_reminder_late))
                ? (int) $user->task_reminder_late
                : 3;

            $stages = [];
            if ($earlyHours > 0) {
                $stages[] = "H-{$earlyHours}";
            }
            if ($lateHours > 0) {
                $stages[] = "H-{$lateHours}";
            }

            if (!empty($stages)) {
                $deadlineText = "deadline tugas (" . implode(' & ', $stages) . ")";
            } else {
                $deadlineText = "deadline tugas (pengingat dinonaktifkan)";
            }

            $reminderNote = "<i>Bot akan otomatis mengingatkan {$deadlineText} serta menyapa jadwal kuliah setiap pagi pukul {$reminderTime} WIB.</i>";
        } else {
            $reminderNote = "<i>Bot akan otomatis mengingatkan deadline tugas (H-24 & H-3) serta menyapa jadwal kuliah setiap pagi pukul 06:00 WIB (dapat disesuaikan di profil web).</i>";
        }

        $msg = "🤖 <b>Panduan Perintah Academic Hub Bot</b>\n\n"
             . "Ketuk tombol biru <b>[ Menu ]</b> di pojok kiri bawah atau kirim perintah berikut:\n\n"
             . "• 📅 <b>/jadwal</b> - Jadwal kuliah hari ini\n"
             . "• 🗓️ <b>/semua_jadwal</b> - Rangkuman semua jadwal kuliah Anda\n"
             . "• 📝 <b>/tugas</b> - Daftar tugas aktif & hitung mundur deadline\n"
             . "• 📑 <b>/materi</b> - Cek berkas materi per mata kuliah\n"
             . "• 🏛️ <b>/portal</b> - Akses cepat portal kampus (OCW, SPADA, dll)\n"
             . "• 🔐 <b>/reset</b> - Atur ulang kata sandi akun Anda\n"
             . "• 🔢 <b>/id</b> - Melihat nomor Chat ID Telegram Anda\n"
             . "• ℹ️ <b>/help</b> - Panduan & bantuan perintah bot\n\n"
             . $reminderNote;

        $this->telegramService->sendMessage($chatId, $msg, null, $this->telegramService->removeKeyboardMarkup());
    }

    protected function handleDefaultMessage(int|string $chatId, ?User $user, string $appUrl): void
    {
        $name = htmlspecialchars($user ? $user->name : 'Mahasiswa', ENT_QUOTES, 'UTF-8');
        $msg = "Halo <b>{$name}</b>! 👋\n\n"
             . "Silakan ketuk tombol biru <b>[ Menu ]</b> di pojok kiri bawah atau gunakan perintah berikut:\n"
             . "• 📅 <b>/jadwal</b> - Jadwal kuliah hari ini\n"
             . "• 🗓️ <b>/semua_jadwal</b> - Rangkuman semua jadwal\n"
             . "• 📝 <b>/tugas</b> - Tugas aktif mendekati deadline\n"
             . "• 📑 <b>/materi</b> - Ringkasan berkas materi kuliah\n"
             . "• 🏛️ <b>/portal</b> - Akses portal kampus\n"
             . "• 🔐 <b>/reset</b> - Atur ulang kata sandi akun\n"
             . "• ℹ️ <b>/help</b> - Panduan bot";

        $this->telegramService->sendMessage($chatId, $msg, null, $this->telegramService->removeKeyboardMarkup());
    }

    protected function handlePortalCommand(int|string $chatId, ?User $user, string $appUrl): void
    {
        if (! $user) {
            $msg = "⚠️ Akun Telegram Anda belum terhubung ke akun Academic Hub.\n\n"
                 . "Silakan hubungkan Chat ID Anda <code>{$chatId}</code> di profil web:\n"
                 . "<a href=\"{$appUrl}/profile\">{$appUrl}/profile</a>";
            $this->telegramService->sendMessage($chatId, $msg);
            return;
        }

        $portals = $user->all_campus_portals;
        $campusName = $user->campus_name ? htmlspecialchars($user->campus_name, ENT_QUOTES, 'UTF-8') : 'Kampus Anda';

        if (empty($portals)) {
            $safeName = htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8');
            $msg = "🏛️ <b>Portal Kampus Belum Diatur</b>\n\n"
                 . "Halo <b>{$safeName}</b>, Anda belum menambahkan tautan portal kampus (seperti OCW, SPADA, SIAKAD) di Academic Hub.\n\n"
                 . "👉 Silakan atur portal kampus Anda di halaman profil web:\n"
                 . "<a href=\"{$appUrl}/profile\">{$appUrl}/profile</a>";

            $buttons = [
                [
                    ['text' => '⚙️ Atur Portal di Web', 'url' => "{$appUrl}/profile"],
                ],
            ];

            $this->telegramService->sendMessage($chatId, $msg, $buttons);
            return;
        }

        $msg = "🏛️ <b>Akses Cepat Portal Kampus</b>\n"
             . "Kampus: <b>{$campusName}</b>\n"
             . "───────────────────────\n\n";

        foreach ($portals as $idx => $portal) {
            $num = $idx + 1;
            $pName = htmlspecialchars($portal['name'] ?? 'Portal');
            $pUrl = htmlspecialchars($portal['url']);
            $msg .= "{$num}️⃣ 🌐 <b>{$pName}</b>\n"
                  . "   🔗 <a href=\"{$pUrl}\">{$pUrl}</a>\n\n";
        }

        $msg .= "💡 <i>Ketuk tombol tautan langsung di bawah untuk membuka di browser:</i>";

        $buttons = [];
        $hasLongName = false;
        foreach ($portals as $portal) {
            if (mb_strlen($portal['name'] ?? '') > 12) {
                $hasLongName = true;
                break;
            }
        }

        if ($hasLongName) {
            foreach ($portals as $portal) {
                $pName = $portal['name'] ?? 'Buka Portal';
                $buttons[] = [
                    ['text' => "🌐 {$pName} ↗", 'url' => $portal['url']],
                ];
            }
        } else {
            $row = [];
            foreach ($portals as $portal) {
                $pName = $portal['name'] ?? 'Portal';
                $row[] = ['text' => "🌐 {$pName} ↗", 'url' => $portal['url']];
                if (count($row) === 2) {
                    $buttons[] = $row;
                    $row = [];
                }
            }
            if (!empty($row)) {
                $buttons[] = $row;
            }
        }

        $buttons[] = [
            ['text' => '🏠 Buka Dashboard Web', 'url' => "{$appUrl}/dashboard"],
        ];

        $this->telegramService->sendMessage($chatId, $msg, $buttons);
    }

    protected function handleResetPasswordCommand(int|string $chatId, ?User $user, string $text, string $appUrl): void
    {
        if (! $user) {
            $msg = "🔐 <b>Atur Ulang Kata Sandi</b>\n\n"
                 . "⚠️ Demi alasan keamanan, fitur reset kata sandi via Telegram hanya dapat digunakan jika akun Telegram Anda <b>sudah terhubung sebelumnya</b> di halaman Profil Academic Hub.\n\n"
                 . "Silakan gunakan fitur <b>Lupa Kata Sandi</b> melalui website:\n"
                 . "🔗 <a href=\"{$appUrl}/forgot-password\">{$appUrl}/forgot-password</a>";

            $buttons = [
                [
                    ['text' => '🌐 Buka Lupa Kata Sandi', 'url' => "{$appUrl}/forgot-password"],
                ],
            ];

            $this->telegramService->sendMessage($chatId, $msg, $buttons);
            return;
        }

        $token = \Illuminate\Support\Facades\Password::broker()->createToken($user);
        $resetUrl = url(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ], false));

        $safeName = htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8');
        $msg = "🔐 <b>Tautan Reset Kata Sandi Anda</b>\n\n"
             . "Halo <b>{$safeName}</b>,\n"
             . "Berikut adalah tautan untuk mengatur ulang kata sandi akun Academic Hub Anda (berlaku selama 60 menit):\n\n"
             . "🔗 <a href=\"{$resetUrl}\">{$resetUrl}</a>\n\n"
             . "<i>Ketuk tombol di bawah ini untuk membuka halaman ubah sandi baru:</i>";

        $msgId = $this->telegramService->sendMessageWithId($chatId, $msg, [
            [
                ['text' => '🔑 Atur Ulang Kata Sandi', 'url' => $resetUrl],
            ],
        ]);

        if ($msgId) {
            cache()->put('tg_reset_msg_' . $user->id, $msgId, 3600);
        }
    }
}


