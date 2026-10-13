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
        } elseif (str_starts_with($lowerText, '/semua_jadwal') || str_starts_with($lowerText, '/semuajadwal') || str_contains($lowerText, 'semua jadwal')) {
            $this->handleSemuaJadwalCommand($chatId, $user, $appUrl);
        } elseif (str_starts_with($lowerText, '/jadwal') || str_contains($lowerText, 'jadwal hari ini')) {
            $this->handleJadwalCommand($chatId, $user, $appUrl);
        } elseif (str_starts_with($lowerText, '/tugas') || str_contains($lowerText, 'tugas aktif')) {
            $this->handleTugasCommand($chatId, $user, $appUrl);
        } elseif (str_starts_with($lowerText, '/materi') || str_contains($lowerText, 'materi')) {
            if (preg_match('/^\/materi[_ ](\d+)$/i', $text, $matches)) {
                $this->handleMateriDetail($chatId, $user, (int) $matches[1], $appUrl);
            } else {
                $this->handleMateriCommand($chatId, $user, $appUrl);
            }
        } elseif (str_starts_with($lowerText, '/portal') || str_contains($lowerText, 'portal kampus') || str_contains($lowerText, 'portal')) {
            $this->handlePortalCommand($chatId, $user, $appUrl);
        } elseif (str_starts_with($lowerText, '/id') || str_contains($lowerText, 'chat id')) {
            $this->handleIdCommand($chatId);
        } elseif (str_starts_with($lowerText, '/reset') || str_starts_with($lowerText, '/lupasandi')) {
            $this->handleResetPasswordCommand($chatId, $user, $text, $appUrl);
        } elseif (str_starts_with($lowerText, '/help') || str_starts_with($lowerText, '/bantuan') || str_contains($lowerText, 'bantuan')) {
            $this->handleHelpCommand($chatId, $user);
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
                 . "<blockquote>\n"
                 . "💡 <i>Gunakan tombol biru <b>[ Menu ]</b> di kiri bawah atau kirim perintah cepat berikut:</i>\n"
                 . "</blockquote>\n\n"
                 . "• 📅 <b>/jadwal</b> - Cek kuliah hari ini\n"
                 . "• 🗓️ <b>/semua_jadwal</b> - Lihat seluruh matkul terdaftar\n"
                 . "• 📝 <b>/tugas</b> - Cek tugas mendekati deadline\n"
                 . "• 📑 <b>/materi</b> - Cek ringkasan berkas materi kuliah\n"
                 . "• 🏛️ <b>/portal</b> - Akses cepat portal kampus Anda\n"
                 . "• ℹ️ <b>/help</b> - Daftar perintah bot\n\n"
                 . "🌐 <a href=\"{$appUrl}/dashboard\">Buka Website Academic Hub</a>";

            $this->telegramService->sendMessage($chatId, $msg, null, $this->telegramService->removeKeyboardMarkup());
        } else {
            $msg = "👋 Halo <b>{$firstName}</b>!\n\n"
                 . "Selamat datang di <b>Academic Hub Notifier</b>. 🎓\n\n"
                 . "🔢 <b>Chat ID Telegram Anda adalah:</b>\n"
                 . "<code>{$chatId}</code> <i>(ketuk untuk menyalin)</i>\n\n"
                 . "<blockquote>\n"
                 . "📌 <b>Cara menghubungkan dengan akun web Anda:</b>\n"
                 . "1. Salin angka Chat ID di atas.\n"
                 . "2. Masuk ke halaman profil: <a href=\"{$appUrl}/profile\">{$appUrl}/profile</a>\n"
                 . "3. Tempelkan ke kolom <b>Telegram Chat ID Anda</b> lalu klik Simpan.\n"
                 . "</blockquote>\n\n"
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

        $allUserCoursesCount = $user->courses()->count();
        if ($allUserCoursesCount === 0) {
            $msg = "📅 <b>Jadwal Kuliah Hari Ini ({$todayName}, {$todayDate})</b>\n\n"
                 . "Halo <b>" . htmlspecialchars($user->name) . "</b>!\n\n"
                 . "<blockquote>\n"
                 . "📌 <b>Jadwal Kuliah Belum Terdaftar:</b>\n"
                 . "Akun Academic Hub kamu saat ini belum memiliki daftar mata kuliah.\n\n"
                 . "Yuk lengkapi jadwal kuliah semester ini melalui website agar bot dapat menampilkan jadwal harianmu:\n"
                 . "🔗 <a href=\"{$appUrl}/dashboard\">Buka Dashboard Academic Hub</a>\n"
                 . "</blockquote>";
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
                     . "Halo <b>" . htmlspecialchars($user->name) . "</b>! Tidak ada jadwal perkuliahan hari ini 🥳\n"
                     . "Manfaatkan akhir pekanmu untuk istirahat, refreshing, atau bersantai.\n\n"
                     . "<blockquote>\n"
                     . "💡 <b>Aksi Cepat:</b>\n"
                     . "• <b>/tugas</b> - Cek status tugas aktif\n"
                     . "• <b>/semua_jadwal</b> - Intip jadwal kuliah sepekan\n"
                     . "</blockquote>";
            } else {
                $msg = "📅 <b>Jadwal Kuliah Hari Ini ({$todayName}, {$todayDate})</b>\n\n"
                     . "🎉 <b>Hari Bebas Kuliah (Free Day)!</b>\n"
                     . "<i>Tidak ada jadwal perkuliahan untuk hari ini. Selamat beristirahat atau belajar mandiri!</i>\n\n"
                     . "<blockquote>\n"
                     . "💡 Ketuk <b>/tugas</b> untuk cek tugas aktif atau <b>/semua_jadwal</b> untuk melihat jadwal hari lain.\n"
                     . "</blockquote>";
            }
        } else {
            $msg = "📅 <b>Jadwal Kuliah Hari Ini ({$todayName}, {$todayDate})</b>\n"
                 . "Halo <b>" . htmlspecialchars($user->name) . "</b>, berikut agenda perkuliahanmu hari ini:\n\n";

            foreach ($courses as $index => $course) {
                $no = $index + 1;
                $timeText = $this->formatCourseTime($course->start_time, $course->end_time);
                $courseName = $course->name;
                $code = $course->code ? " <code>[" . htmlspecialchars($course->code) . "]</code>" : "";
                $lecturer = $course->lecturer_name ? htmlspecialchars($course->lecturer_name) : null;
                $room = $course->room ? htmlspecialchars($course->room) : null;

                $msg .= "{$no}. <b>{$courseName}</b>{$code}\n"
                      . "   ⏰ <code>{$timeText}</code>\n";

                if ($room) {
                    $msg .= "   📍 Ruang: <b>{$room}</b>\n";
                }
                if ($lecturer) {
                    $msg .= "   👨‍🏫 Dosen: {$lecturer}\n";
                }

                $details = [];
                if ($course->pj_name) {
                    $pjText = "👤 <b>PJ: " . htmlspecialchars($course->pj_name) . "</b>";
                    if ($course->pj_phone) {
                        $cleanPhone = preg_replace('/[^0-9]/', '', $course->pj_phone);
                        if (str_starts_with($cleanPhone, '0')) {
                            $cleanPhone = '62' . substr($cleanPhone, 1);
                        }
                        $pjText .= " (<a href=\"https://wa.me/{$cleanPhone}\">WhatsApp</a>)";
                    }
                    $details[] = $pjText;
                }
                if ($course->attendance_url) {
                    $details[] = "📝 <b>Presensi:</b> <a href=\"" . htmlspecialchars($course->attendance_url) . "\">Link Presensi Online</a>";
                }

                if (!empty($details)) {
                    $msg .= "<blockquote expandable>\n" . implode("\n", $details) . "\n</blockquote>\n\n";
                } else {
                    $msg .= "\n";
                }
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
                 . "<blockquote>\n"
                 . "Belum ada mata kuliah yang didaftarkan di website.\n\n"
                 . "👉 Silakan tambahkan mata kuliah Anda melalui website: <a href=\"{$appUrl}/dashboard\">{$appUrl}/dashboard</a>\n"
                 . "</blockquote>";
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
             . "Halo <b>" . htmlspecialchars($user->name) . "</b>, berikut ringkasan seluruh matkul yang terdaftar di Academic Hub:\n\n";

        foreach ($dayOrder as $day) {
            if (!empty($grouped[$day])) {
                $msg .= "📌 <b>" . strtoupper($day) . "</b>\n";
                foreach ($grouped[$day] as $course) {
                    $timeText = $this->formatCourseTime($course->start_time, $course->end_time);
                    $courseName = $course->name;
                    $code = $course->code ? " <code>[" . htmlspecialchars($course->code) . "]</code>" : "";
                    $lecturer = $course->lecturer_name ? htmlspecialchars($course->lecturer_name) : null;
                    $room = $course->room ? htmlspecialchars($course->room) : null;

                    $msg .= "• <b>{$courseName}</b>{$code}\n"
                          . "   ⏰ <code>{$timeText}</code>\n";

                    if ($room) {
                        $msg .= "   📍 Ruang: <b>{$room}</b>\n";
                    }
                    if ($lecturer) {
                        $msg .= "   👨‍🏫 Dosen: {$lecturer}\n";
                    }

                    $details = [];
                    if ($course->pj_name) {
                        $pjText = "👤 PJ: " . htmlspecialchars($course->pj_name);
                        if ($course->pj_phone) {
                            $cleanPhone = preg_replace('/[^0-9]/', '', $course->pj_phone);
                            if (str_starts_with($cleanPhone, '0')) {
                                $cleanPhone = '62' . substr($cleanPhone, 1);
                            }
                            $pjText .= " (<a href=\"https://wa.me/{$cleanPhone}\">WhatsApp</a>)";
                        }
                        $details[] = $pjText;
                    }
                    if ($course->attendance_url) {
                        $details[] = "📝 <a href=\"" . htmlspecialchars($course->attendance_url) . "\">Presensi Online</a>";
                    }

                    if (!empty($details)) {
                        $msg .= "<blockquote expandable>\n" . implode("\n", $details) . "\n</blockquote>\n\n";
                    } else {
                        $msg .= "\n";
                    }
                }
            }
        }

        // Cetak hari lainnya jika ada yang tidak standar
        foreach ($grouped as $day => $courses) {
            if (!in_array($day, $dayOrder) && !empty($courses)) {
                $msg .= "📌 <b>" . strtoupper($day) . "</b>\n";
                foreach ($courses as $course) {
                    $timeText = $this->formatCourseTime($course->start_time, $course->end_time);
                    $courseName = $course->name;
                    $code = $course->code ? " <code>[" . htmlspecialchars($course->code) . "]</code>" : "";
                    $lecturer = $course->lecturer_name ? htmlspecialchars($course->lecturer_name) : null;
                    $room = $course->room ? htmlspecialchars($course->room) : null;

                    $msg .= "• <b>{$courseName}</b>{$code}\n"
                          . "   ⏰ <code>{$timeText}</code>\n";

                    if ($room) {
                        $msg .= "   📍 Ruang: <b>{$room}</b>\n";
                    }
                    if ($lecturer) {
                        $msg .= "   👨‍🏫 Dosen: {$lecturer}\n";
                    }

                    $details = [];
                    if ($course->pj_name) {
                        $pjText = "👤 PJ: " . htmlspecialchars($course->pj_name);
                        if ($course->pj_phone) {
                            $cleanPhone = preg_replace('/[^0-9]/', '', $course->pj_phone);
                            if (str_starts_with($cleanPhone, '0')) {
                                $cleanPhone = '62' . substr($cleanPhone, 1);
                            }
                            $pjText .= " (<a href=\"https://wa.me/{$cleanPhone}\">WhatsApp</a>)";
                        }
                        $details[] = $pjText;
                    }
                    if ($course->attendance_url) {
                        $details[] = "📝 <a href=\"" . htmlspecialchars($course->attendance_url) . "\">Presensi Online</a>";
                    }

                    if (!empty($details)) {
                        $msg .= "<blockquote expandable>\n" . implode("\n", $details) . "\n</blockquote>\n\n";
                    } else {
                        $msg .= "\n";
                    }
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
                 . "<blockquote>\n"
                 . "🎉 <i>Hebat! Tidak ada tugas aktif yang mendekati deadline saat ini. Semua tugas telah terselesaikan.</i>\n"
                 . "</blockquote>";
        } else {
            $msg = "📝 <b>Daftar Tugas Mendekati Deadline:</b>\n\n";
            $now = Carbon::now('Asia/Jakarta');

            foreach ($assignments as $index => $assignment) {
                $no = $index + 1;
                $deadline = Carbon::parse($assignment->deadline)->setTimezone('Asia/Jakarta');

                if ($deadline->isPast()) {
                    $diffText = '⚠️ Terlewat ' . $deadline->diffForHumans($now, [
                        'syntax' => Carbon::DIFF_RELATIVE_TO_NOW,
                        'parts' => 2,
                    ]);
                } else {
                    $rawDiff = $deadline->diffForHumans($now, [
                        'syntax' => Carbon::DIFF_RELATIVE_TO_NOW,
                        'parts' => 2,
                    ]);
                    $cleanDiff = str_replace('dari sekarang', 'lagi', $rawDiff);
                    $diffText = "⏳ sisa {$cleanDiff}";
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
                      . "   📚 <i>{$courseNameEscaped}</i>\n"
                      . "   ⏳ Deadline: <code>{$formattedDeadline} WIB</code> ({$diffText})\n";

                $extraBlocks = [];
                if (!empty($assignment->description)) {
                    $cleanDesc = htmlspecialchars(trim($assignment->description));
                    $descPreview = Str::limit($cleanDesc, 180);
                    $extraBlocks[] = "📝 <b>Keterangan:</b> <i>{$descPreview}</i>";
                }

                $assignmentLinks = $assignment->all_links;
                if (!empty($assignmentLinks)) {
                    if (count($assignmentLinks) === 1) {
                        $lTitle = htmlspecialchars($assignmentLinks[0]['title'] ?? 'Link Pengumpulan');
                        $lUrl = htmlspecialchars($assignmentLinks[0]['url']);
                        $extraBlocks[] = "🔗 <b>{$lTitle}:</b> <a href=\"{$lUrl}\">{$lUrl}</a>";
                    } else {
                        $linksText = "🔗 <b>Tautan Terkait:</b>";
                        foreach ($assignmentLinks as $l) {
                            $lTitle = htmlspecialchars($l['title'] ?? 'Tautan');
                            $lUrl = htmlspecialchars($l['url']);
                            $linksText .= "\n• {$lTitle}: <a href=\"{$lUrl}\">{$lUrl}</a>";
                        }
                        $extraBlocks[] = $linksText;
                    }
                }

                if (!empty($extraBlocks)) {
                    $msg .= "<blockquote expandable>\n" . implode("\n", $extraBlocks) . "\n</blockquote>\n\n";
                } else {
                    $msg .= "\n";
                }
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

        $courses = $user->courses()->withCount('materials')->orderBy('name', 'asc')->get();

        if ($courses->isEmpty()) {
            $msg = "📑 <b>Arsip Berkas Materi Perkuliahan</b>\n\n"
                 . "<blockquote>\n"
                 . "Belum ada mata kuliah yang didaftarkan di website.\n\n"
                 . "👉 Tambahkan mata kuliah Anda: <a href=\"{$appUrl}/dashboard\">{$appUrl}/dashboard</a>\n"
                 . "</blockquote>";
            $this->telegramService->sendMessage($chatId, $msg, null, $this->telegramService->removeKeyboardMarkup());
            return;
        }

        $msg = "📑 <b>Arsip Berkas Materi Perkuliahan</b>\n"
             . "Halo <b>" . htmlspecialchars($user->name) . "</b>, berikut ringkasan berkas materi kuliah Anda:\n\n";

        $totalMaterials = 0;
        $buttons = [];
        foreach ($courses as $course) {
            $count = (int) $course->materials_count;
            $totalMaterials += $count;
            $statusText = $count > 0 ? "<b>{$count} berkas</b> tersimpan" : "<i>Belum ada berkas</i>";

            $latest = $course->materials()->orderBy('meeting_number', 'desc')->first();
            $latestText = ($latest && $latest->meeting_number) ? " <code>[P{$latest->meeting_number}]</code>" : "";

            $msg .= "• <b>{$course->name}</b>: {$statusText}{$latestText}\n";

            // Tombol interaktif per matkul: HANYA TEKS BERSIH DAN RAPI (TANPA EMOJI)
            $btnLabel = $course->name . " ({$count} berkas)";
            $buttons[] = [
                [
                    'text' => Str::limit($btnLabel, 35),
                    'callback_data' => "materi_course:{$course->id}",
                ],
            ];
        }

        $msg .= "\n📊 <b>Total Materi:</b> <code>{$totalMaterials} berkas tersimpan</code>\n\n"
              . "<blockquote>\n"
              . "💡 <i>Ketuk tombol mata kuliah di bawah untuk membuka rincian berkas, dokumen, dan tautan materi secara detail.</i>\n"
              . "</blockquote>";

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

        $courseName = $course->name;
        $courseCode = $course->code ? ' <code>[' . htmlspecialchars($course->code) . ']</code>' : '';
        $lecturer = $course->lecturer_name ? htmlspecialchars($course->lecturer_name) : 'Dosen belum diset';
        $materials = $course->materials;
        $total = $materials->count();

        $msg = "📚 <b>Materi Kuliah: {$courseName}</b>{$courseCode}\n"
             . "👤 <b>Dosen:</b> {$lecturer}\n"
             . "📊 <b>Total Materi:</b> <code>{$total} berkas tersimpan</code>\n"
             . "──────────────────────────────\n\n";

        if ($materials->isEmpty()) {
            $msg .= "<blockquote>\n"
                  . "📭 <i>Belum ada berkas atau tautan materi yang diunggah untuk mata kuliah ini.</i>\n\n"
                  . "💡 <i>Anda dapat mengunggah berkas slide, PDF, atau tautan Drive/LMS melalui website Academic Hub.</i>\n"
                  . "</blockquote>\n";
        } else {
            foreach ($materials as $index => $material) {
                $no = $index + 1;
                $title = htmlspecialchars($material->title);
                $meetingText = $material->meeting_number ? "Pertemuan ke-{$material->meeting_number}" : "Materi Umum";
                $createdDate = $material->created_at ? $material->created_at->locale('id')->isoFormat('D MMMM YYYY') : '-';

                $msg .= "📁 <b>{$no}. {$title}</b>\n"
                      . "   🏷️ <i>{$meetingText}</i> • 📅 <code>{$createdDate}</code>\n";

                $details = [];
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
                    $details[] = "{$icon} Berkas: <code>{$cleanFilename}</code> ({$fileTypeLabel})";
                }

                $links = $material->all_links;
                if (!empty($links)) {
                    if (count($links) === 1) {
                        $lTitle = htmlspecialchars($links[0]['title'] ?? 'Tautan Materi');
                        $lUrl = htmlspecialchars($links[0]['url']);
                        $details[] = "🔗 <a href=\"{$lUrl}\">{$lTitle}</a>";
                    } else {
                        $lText = "🔗 <b>Tautan Terkait:</b>";
                        foreach ($links as $l) {
                            $lTitle = htmlspecialchars($l['title'] ?? 'Tautan');
                            $lUrl = htmlspecialchars($l['url']);
                            $lText .= "\n• <a href=\"{$lUrl}\">{$lTitle}</a>";
                        }
                        $details[] = $lText;
                    }
                }

                if (!empty($material->notes)) {
                    $cleanNotes = htmlspecialchars(trim($material->notes));
                    $details[] = "📝 Catatan: <i>" . Str::limit($cleanNotes, 200) . "</i>";
                }

                if (!empty($details)) {
                    $msg .= "<blockquote expandable>\n" . implode("\n", $details) . "\n</blockquote>\n\n";
                } else {
                    $msg .= "\n";
                }
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
             . "<code>{$chatId}</code> <i>(ketuk untuk menyalin)</i>\n\n"
             . "<blockquote>\n"
             . "💡 <i>Gunakan angka ID ini pada menu Pengaturan Notifikasi & Kampus di Academic Hub untuk menghubungkan bot notifikasi.</i>\n"
             . "</blockquote>";

        $this->telegramService->sendMessage($chatId, $msg, null, $this->telegramService->removeKeyboardMarkup());
    }

    protected function handleHelpCommand(int|string $chatId, ?User $user = null): void
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
             . "<blockquote>\n"
             . $reminderNote . "\n"
             . "</blockquote>";

        $this->telegramService->sendMessage($chatId, $msg, null, $this->telegramService->removeKeyboardMarkup());
    }

    protected function handleDefaultMessage(int|string $chatId, ?User $user): void
    {
        $name = $user ? htmlspecialchars($user->name) : 'Mahasiswa';
        $msg = "Halo <b>{$name}</b>! 👋\n\n"
             . "<blockquote>\n"
             . "💡 <i>Silakan ketuk tombol biru <b>[ Menu ]</b> di pojok kiri bawah atau gunakan perintah berikut:</i>\n"
             . "</blockquote>\n\n"
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
        $campusName = $user->campus_name ? htmlspecialchars($user->campus_name) : 'Kampus Anda';

        if (empty($portals)) {
            $msg = "🏛️ <b>Portal Kampus Belum Diatur</b>\n\n"
                 . "Halo <b>{$user->name}</b>, Anda belum menambahkan tautan portal kampus (seperti OCW, SPADA, SIAKAD) di Academic Hub.\n\n"
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

        $msg .= "<blockquote>\n"
              . "💡 <i>Ketuk tombol tautan langsung di bawah untuk membuka di browser:</i>\n"
              . "</blockquote>";

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
        $parts = preg_split('/\s+/', trim($text));
        $targetEmail = $parts[1] ?? null;

        $targetUser = $user;
        if (! $targetUser && $targetEmail && filter_var($targetEmail, FILTER_VALIDATE_EMAIL)) {
            $candidateUser = User::where('email', $targetEmail)->first();
            if ($candidateUser) {
                if (empty($candidateUser->telegram_chat_id)) {
                    $chatIdInUse = User::where('telegram_chat_id', (string) $chatId)->exists();
                    if (! $chatIdInUse) {
                        $candidateUser->update(['telegram_chat_id' => (string) $chatId]);
                    }
                }
                if ($candidateUser->telegram_chat_id === (string) $chatId) {
                    $targetUser = $candidateUser;
                }
            }
        }

        if (! $targetUser) {
            $msg = "🔐 <b>Atur Ulang Kata Sandi</b>\n\n"
                 . "Akun Telegram Anda belum terhubung ke akun Academic Hub.\n\n"
                 . "Silakan kirim perintah dengan format:\n"
                 . "<code>/reset email_anda@kampus.ac.id</code>\n\n"
                 . "Contoh:\n"
                 . "<code>/reset novv.a2n@gmail.com</code>";
            $this->telegramService->sendMessage($chatId, $msg);
            return;
        }

        $token = \Illuminate\Support\Facades\Password::broker()->createToken($targetUser);
        $resetUrl = url(route('password.reset', [
            'token' => $token,
            'email' => $targetUser->email,
        ], false));

        $msg = "🔐 <b>Tautan Reset Kata Sandi Anda</b>\n\n"
             . "Halo <b>" . htmlspecialchars($targetUser->name) . "</b>,\n"
             . "Berikut adalah tautan untuk mengatur ulang kata sandi akun Academic Hub Anda (berlaku selama 60 menit):\n\n"
             . "🔗 <a href=\"{$resetUrl}\">{$resetUrl}</a>\n\n"
             . "<i>Ketuk tombol di bawah ini untuk membuka halaman ubah sandi baru:</i>";

        $msgId = $this->telegramService->sendMessageWithId($chatId, $msg, [
            [
                ['text' => '🔑 Atur Ulang Kata Sandi', 'url' => $resetUrl],
            ],
        ]);

        if ($msgId) {
            cache()->put('tg_reset_msg_' . $targetUser->id, $msgId, 3600);
        }
    }
}


