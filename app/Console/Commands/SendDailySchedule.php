<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SendDailySchedule extends Command
{
    protected $signature = 'courses:send-daily-schedule {--user= : ID user spesifik (opsional)} {--day= : Nama hari spesifik (opsional, contoh: Senin)} {--check-time : Hanya kirim ke user dengan waktu reminder yang cocok dengan jam saat ini}';
    protected $description = 'Kirim ringkasan jadwal kuliah dan deadline tugas hari ini via Telegram';

    public function handle(TelegramService $telegramService): void
    {
        Carbon::setLocale('id');
        $tz = config('app.timezone', 'Asia/Jakarta');
        $now = Carbon::now($tz);

        $indonesianDays = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $todayDay = $this->option('day') ?: $indonesianDays[$now->dayOfWeek];
        $isSaturday = ($todayDay === 'Sabtu');
        $isSunday = ($todayDay === 'Minggu');
        $isWeekend = ($isSaturday || $isSunday);

        $appUrl = config('app.url', 'https://academic-hub-ocw2.onrender.com');
        if (empty($appUrl) || str_contains($appUrl, 'localhost')) {
            $appUrl = 'https://academic-hub-ocw2.onrender.com';
        }

        $formattedDate = $now->isoFormat('D MMMM YYYY');
        $this->info("Memulai pengiriman Jadwal Kuliah Harian untuk hari {$todayDay} ({$formattedDate})...");

        // Ambil seluruh user dengan mata kuliah dan tugas yang belum selesai
        $query = User::with([
            'courses' => function ($q) {
                $q->orderBy('start_time', 'asc');
            },
            'courses.assignments' => function ($q) {
                $q->where('status', '!=', 'completed')
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
            // Jika opsi --check-time aktif, periksa jam & menit reminder user (default '06:00')
            if ($this->option('check-time')) {
                $cacheKey = "daily_schedule_sent_{$user->id}_{$now->toDateString()}";
                if (Cache::has($cacheKey)) {
                    continue;
                }

                $userTime = $user->schedule_reminder_time ?: '06:00';
                $parts = explode(':', $userTime);
                $userHour = (int) ($parts[0] ?? 6);
                $userMinute = (int) ($parts[1] ?? 0);

                $todayUserTime = $now->copy()->setTime($userHour, $userMinute, 0);
                $diffMinutes = $todayUserTime->diffInMinutes($now, false);

                // Kirim jika waktu sekarang sudah mencapai jam reminder user hari ini,
                // dan belum lewat lebih dari 60 menit (agar tidak terlambat berjam-jam)
                if ($diffMinutes < 0 || $diffMinutes > 60) {
                    continue;
                }
            }

            $allCourses = $user->courses;
            $todayCourses = $allCourses->filter(function ($c) use ($todayDay) {
                return strcasecmp($c->day_of_week, $todayDay) === 0;
            })->values();

            $allPendingAssignments = $allCourses->flatMap->assignments->sortBy('deadline')->values();

            $todayAssignments = $allPendingAssignments->filter(function ($task) use ($now) {
                return Carbon::parse($task->deadline)->isSameDay($now);
            })->values();

            $userName = htmlspecialchars($user->name);

            // KASUS 1: Mahasiswa belum pernah menginputkan mata kuliah sama sekali
            if ($allCourses->isEmpty()) {
                $message = "☀️ <b>SELAMAT PAGI!</b>\n";
                $message .= "Hari: <b>{$todayDay}, {$formattedDate}</b>\n\n";
                $message .= "Halo <b>{$userName}</b>! Semangat mengawali harimu 💪\n\n";
                $message .= "📌 <b>Jadwal Kuliah Belum Terdaftar:</b>\n";
                $message .= "<blockquote>Akun Academic Hub kamu saat ini belum memiliki daftar mata kuliah.\n\n";
                $message .= "Yuk lengkapi jadwal kuliah semester ini di website agar bot bisa:\n";
                $message .= "• Mengingatkan kelas setiap pagi beserta jam & ruangannya\n";
                $message .= "• Membantu memantau deadline tugas kuliahmu</blockquote>\n\n";
                $message .= "👉 Tambah jadwal sekarang:\n";
                $message .= "🔗 Dashboard: {$appUrl}/dashboard";
            }
            // KASUS 2: HARI SABTU (Edisi Sabtu Santai) & Tidak ada jadwal kelas hari ini
            elseif ($isSaturday && $todayCourses->isEmpty()) {
                $message = "🌴 <b>HAPPY WEEKEND! (SABTU SANTAI)</b>\n";
                $message .= "Hari: <b>Sabtu, {$formattedDate}</b>\n\n";
                $message .= "Selamat pagi <b>{$userName}</b>! Waktunya rehat sejenak setelah sepekan beraktivitas 🥳\n";
                $message .= "<blockquote>Nikmati hari libur ini untuk refreshing, istirahat, atau kumpul bersama orang terdekat.</blockquote>\n\n";

                // Status tugas mandiri aktif
                if ($allPendingAssignments->isNotEmpty()) {
                    $message .= "📋 <b>Status Tugas Mandiri:</b>\n";
                    $message .= "Kamu masih memiliki <b>" . $allPendingAssignments->count() . " tugas aktif</b> nih. Kalau ada waktu luang, yuk cicil santai agar pekan depan lebih tenang:\n";
                    $limitTasks = $allPendingAssignments->take(3);
                    $tIdx = 1;
                    foreach ($limitTasks as $task) {
                        $dl = Carbon::parse($task->deadline)->locale('id')->timezone($tz);
                        $cName = $task->course?->name ?? 'Mata Kuliah';
                        $tType = $task->assignment_type === 'group' ? ' 👥 <i>Kelompok</i>' : ' 👤 <i>Individu</i>';
                        $prioTag = $task->priority === 'high' ? ' 🔴 <b>Prioritas Tinggi</b>' : '';
                        $message .= "{$tIdx}. <b>" . htmlspecialchars($task->title) . "</b> ({$cName}){$tType}{$prioTag}\n";
                        $message .= "   ⏰ Deadline: <code>" . $dl->isoFormat('dddd, D MMM - HH:mm') . " WIB</code>\n";
                        $tIdx++;
                    }
                    $message .= "\n👉 Ketik <b>/tugas</b> untuk melihat detail seluruh tugasmu.\n\n";
                } else {
                    $message .= "<blockquote>🎉 Hore! Semua tugasmu sudah beres. Nikmati akhir pekan tanpa beban pikiran ya!</blockquote>\n\n";
                }

                $message .= "💡 <b>Aksi Cepat:</b>\n";
                $message .= "• /tugas — Cek tugas aktif\n";
                $message .= "• /semua_jadwal — Intip jadwal kuliah sepekan\n\n";
                $message .= "🔗 Dashboard: {$appUrl}/dashboard";
            }
            // KASUS 3: HARI MINGGU (Edisi Sunday Recharge) & Tidak ada jadwal kelas hari ini
            elseif ($isSunday && $todayCourses->isEmpty()) {
                $message = "☕ <b>SELAMAT HARI MINGGU!</b>\n";
                $message .= "Hari: <b>Minggu, {$formattedDate}</b>\n\n";
                $message .= "Selamat pagi <b>{$userName}</b>! Semoga akhir pekanmu menyenangkan dan berenergi ✨\n\n";
                $message .= "<blockquote>🔋 Isi ulang energimu dan luangkan waktu sejenak untuk persiapan perkuliahan besok.</blockquote>\n\n";

                // Intipan kuliah besok Senin
                $tomorrowCourses = $allCourses->filter(function ($c) {
                    return strcasecmp($c->day_of_week, 'Senin') === 0;
                })->values();

                if ($tomorrowCourses->isNotEmpty()) {
                    $message .= "📅 <b>Intipan Kuliah Besok (Senin):</b>\n";
                    $message .= "Besok kamu memiliki " . $tomorrowCourses->count() . " mata kuliah:\n";
                    $cIdx = 1;
                    foreach ($tomorrowCourses as $course) {
                        $code = $course->code ? " <code>[" . htmlspecialchars($course->code) . "]</code>" : "";
                        $time = $course->start_time ? "<code>" . substr($course->start_time, 0, 5) . " WIB</code>" : "<i>Waktu menyesuaikan</i>";
                        $room = $course->room ? " — Ruang " . htmlspecialchars($course->room) : "";
                        $message .= "{$cIdx}. <b>" . $course->name . "</b>{$code}\n";
                        $message .= "   🕒 {$time}{$room}\n";
                        $cIdx++;
                    }
                    $message .= "\n";
                } else {
                    $message .= "📅 <b>Info Besok (Senin):</b>\n";
                    $message .= "<blockquote>🎉 Besok hari Senin kamu juga tidak ada jadwal kelas (Free Day)!</blockquote>\n\n";
                }

                // Pengingat tugas terdekat
                if ($allPendingAssignments->isNotEmpty()) {
                    $message .= "📋 <b>Pengingat Tugas Terdekat:</b>\n";
                    $limitTasks = $allPendingAssignments->take(2);
                    $tIdx = 1;
                    foreach ($limitTasks as $task) {
                        $dl = Carbon::parse($task->deadline)->locale('id')->timezone($tz);
                        $cName = $task->course?->name ?? 'Mata Kuliah';
                        $message .= "{$tIdx}. <b>" . htmlspecialchars($task->title) . "</b> ({$cName})\n";
                        $message .= "   ⏰ Deadline: <code>" . $dl->isoFormat('dddd, D MMM - HH:mm') . " WIB</code>\n";
                        $tIdx++;
                    }
                    $message .= "\n👉 Ketik <b>/tugas</b> untuk detail tugas lainnya.\n\n";
                } else {
                    $message .= "<blockquote>🎉 Tidak ada tanggungan tugas mendesak. Siap menyambut pekan baru!</blockquote>\n\n";
                }

                $message .= "💡 <b>Aksi Cepat:</b>\n";
                $message .= "• /tugas — Cek tugas aktif\n";
                $message .= "• /semua_jadwal — Lihat jadwal lengkap sepekan\n\n";
                $message .= "🔗 Dashboard: {$appUrl}/dashboard";
            }
            // KASUS 4: HARI KERJA (Senin - Jumat) & Hari Bebas Kuliah (Free Day)
            elseif ($todayCourses->isEmpty()) {
                $message = "☀️ <b>JADWAL KULIAH HARI INI</b>\n";
                $message .= "Hari: <b>{$todayDay}, {$formattedDate}</b>\n\n";
                $message .= "Selamat pagi <b>{$userName}</b>!\n\n";
                $message .= "🎉 <b>Hari Bebas Kuliah (Free Day)!</b>\n";
                $message .= "<blockquote>🏖️ Tidak ada jadwal kuliah hari ini! Manfaatkan waktu untuk istirahat, mengulang materi, atau menyelesaikan tugas.</blockquote>\n\n";

                // Cek apakah ada deadline hari ini
                if ($todayAssignments->isNotEmpty()) {
                    $message .= "⚠️ <b>Deadline Tugas Hari Ini (" . $todayAssignments->count() . " Tugas):</b>\n";
                    $tIdx = 1;
                    foreach ($todayAssignments as $task) {
                        $dl = Carbon::parse($task->deadline)->locale('id')->timezone($tz);
                        $cName = $task->course?->name ?? 'Mata Kuliah';
                        $tType = $task->assignment_type === 'group' ? ' 👥 <i>Kelompok</i>' : ' 👤 <i>Individu</i>';
                        $prioTag = $task->priority === 'high' ? ' 🔴 <b>Prioritas Tinggi</b>' : '';
                        $message .= "{$tIdx}. <b>" . htmlspecialchars($task->title) . "</b> ({$cName}){$tType}{$prioTag}\n";
                        $message .= "   ⏰ Jam: <code>" . $dl->format('H:i') . " WIB</code>\n";

                        $taskDetails = [];
                        if (!empty($task->description)) {
                            $cleanDesc = htmlspecialchars(trim($task->description));
                            $taskDetails[] = "📝 " . Str::limit($cleanDesc, 120);
                        }
                        $links = $task->all_links;
                        if (!empty($links)) {
                            $linkItems = [];
                            foreach ($links as $l) {
                                $lTitle = htmlspecialchars($l['title'] ?? 'Link');
                                $lUrl = htmlspecialchars($l['url']);
                                $linkItems[] = "<a href=\"{$lUrl}\">{$lTitle}</a>";
                            }
                            $taskDetails[] = "🔗 " . implode(' • ', $linkItems);
                        }
                        if (!empty($taskDetails)) {
                            $message .= "   <blockquote expandable>" . implode("\n   ", $taskDetails) . "</blockquote>\n";
                        }
                        $tIdx++;
                    }
                    $message .= "\n";
                } elseif ($allPendingAssignments->isNotEmpty()) {
                    $message .= "📋 <b>Tugas Aktif yang Bisa Dicicil:</b>\n";
                    $limitTasks = $allPendingAssignments->take(2);
                    foreach ($limitTasks as $task) {
                        $dl = Carbon::parse($task->deadline)->locale('id')->timezone($tz);
                        $cName = $task->course?->name ?? 'Mata Kuliah';
                        $tType = $task->assignment_type === 'group' ? ' 👥 <i>Kelompok</i>' : ' 👤 <i>Individu</i>';
                        $prioTag = $task->priority === 'high' ? ' 🔴 <b>Prioritas Tinggi</b>' : '';
                        $message .= "• <b>" . htmlspecialchars($task->title) . "</b> ({$cName}){$tType}{$prioTag}\n";
                        $message .= "   ⏰ Deadline: <code>" . $dl->isoFormat('dddd, D MMM - HH:mm') . " WIB</code>\n";
                    }
                    $message .= "\n👉 Ketik <b>/tugas</b> untuk cek semua tugas aktif.\n\n";
                }

                $portals = $user->all_campus_portals;
                if (!empty($portals)) {
                    $campusLabel = $user->campus_name ? htmlspecialchars($user->campus_name) : 'Portal Kampus';
                    $portalLinks = [];
                    foreach ($portals as $portal) {
                        $pName = !empty($portal['name']) ? htmlspecialchars($portal['name']) : 'Buka Portal';
                        $pUrl = htmlspecialchars($portal['url']);
                        $portalLinks[] = "<a href=\"{$pUrl}\">{$pName}</a>";
                    }
                    $message .= "🏛️ <b>{$campusLabel}:</b> " . implode(' • ', $portalLinks) . "\n\n";
                }

                $message .= "🔗 Dashboard: {$appUrl}/dashboard";
            }
            // KASUS 5: ADA KELAS HARI INI (Termasuk jika ada kelas di hari Sabtu/Minggu)
            else {
                $headerTitle = $isWeekend ? "🌴 <b>JADWAL KULIAH HARI INI (AKHIR PEKAN)</b>" : "☀️ <b>JADWAL KULIAH HARI INI</b>";
                $message = "{$headerTitle}\n";
                $message .= "Hari: <b>{$todayDay}, {$formattedDate}</b>\n\n";
                $message .= "Selamat pagi <b>{$userName}</b>! Berikut agenda perkuliahanmu hari ini:\n\n";

                $message .= "📚 <b>Kelas Hari Ini (" . $todayCourses->count() . " Matkul):</b>\n";
                $idx = 1;
                foreach ($todayCourses as $course) {
                    $codeText = $course->code ? " <code>[" . htmlspecialchars($course->code) . "]</code>" : "";
                    $message .= "{$idx}. <b>" . $course->name . "</b>{$codeText}\n";
                    if ($course->start_time) {
                        $start = substr($course->start_time, 0, 5);
                        $end = $course->end_time ? substr($course->end_time, 0, 5) : 'Selesai';
                        $message .= "   🕒 <code>{$start} - {$end} WIB</code>\n";
                    } else {
                        $message .= "   🕒 <i>Waktu menyesuaikan dosen</i>\n";
                    }
                    if ($course->room) {
                        $message .= "   📍 Ruang: <b>" . htmlspecialchars($course->room) . "</b>\n";
                    }
                    if ($course->lecturer_name) {
                        $message .= "   👨‍🏫 Dosen: " . htmlspecialchars($course->lecturer_name) . "\n";
                    }

                    $optionalDetails = [];
                    if (!empty($course->pj_name)) {
                        $pjPhone = '';
                        if (!empty($course->pj_phone)) {
                            $cleanPhone = preg_replace('/[^0-9]/', '', $course->pj_phone);
                            if (str_starts_with($cleanPhone, '0')) {
                                $cleanPhone = '62' . substr($cleanPhone, 1);
                            }
                            $pjPhone = " (<a href=\"https://wa.me/{$cleanPhone}\">WhatsApp</a>)";
                        }
                        $optionalDetails[] = "👤 PJ: " . htmlspecialchars($course->pj_name) . $pjPhone;
                    }
                    if (!empty($course->attendance_url)) {
                        $optionalDetails[] = "📝 Presensi: <a href=\"" . htmlspecialchars($course->attendance_url) . "\">Link Presensi</a>";
                    }

                    if (!empty($optionalDetails)) {
                        $message .= "   <blockquote expandable>" . implode("\n   ", $optionalDetails) . "</blockquote>\n";
                    }
                    $idx++;
                }
                $message .= "\n";

                // Deadline tugas hari ini
                if ($todayAssignments->isNotEmpty()) {
                    $message .= "⚠️ <b>Deadline Tugas Hari Ini (" . $todayAssignments->count() . " Tugas):</b>\n";
                    $tIdx = 1;
                    foreach ($todayAssignments as $task) {
                        $dl = Carbon::parse($task->deadline)->locale('id')->timezone($tz);
                        $cName = $task->course?->name ?? 'Mata Kuliah';
                        $tType = $task->assignment_type === 'group' ? ' 👥 <i>Kelompok</i>' : ' 👤 <i>Individu</i>';
                        $prioTag = $task->priority === 'high' ? ' 🔴 <b>Prioritas Tinggi</b>' : '';
                        $message .= "{$tIdx}. <b>" . htmlspecialchars($task->title) . "</b> ({$cName}){$tType}{$prioTag}\n";
                        $message .= "   ⏰ Jam: <code>" . $dl->format('H:i') . " WIB</code>\n";

                        $taskDetails = [];
                        if (!empty($task->description)) {
                            $cleanDesc = htmlspecialchars(trim($task->description));
                            $taskDetails[] = "📝 " . Str::limit($cleanDesc, 120);
                        }
                        $links = $task->all_links;
                        if (!empty($links)) {
                            $linkItems = [];
                            foreach ($links as $l) {
                                $lTitle = htmlspecialchars($l['title'] ?? 'Link');
                                $lUrl = htmlspecialchars($l['url']);
                                $linkItems[] = "<a href=\"{$lUrl}\">{$lTitle}</a>";
                            }
                            $taskDetails[] = "🔗 " . implode(' • ', $linkItems);
                        }
                        if (!empty($taskDetails)) {
                            $message .= "   <blockquote expandable>" . implode("\n   ", $taskDetails) . "</blockquote>\n";
                        }
                        $tIdx++;
                    }
                    $message .= "\n";
                }

                $portals = $user->all_campus_portals;
                if (!empty($portals)) {
                    $campusLabel = $user->campus_name ? htmlspecialchars($user->campus_name) : 'Portal Kampus';
                    $portalLinks = [];
                    foreach ($portals as $portal) {
                        $pName = !empty($portal['name']) ? htmlspecialchars($portal['name']) : 'Buka Portal';
                        $pUrl = htmlspecialchars($portal['url']);
                        $portalLinks[] = "<a href=\"{$pUrl}\">{$pName}</a>";
                    }
                    $message .= "🏛️ <b>{$campusLabel}:</b> " . implode(' • ', $portalLinks) . "\n\n";
                }

                $message .= "🔗 Dashboard: {$appUrl}/dashboard";
            }

            $success = $telegramService->sendMessage($user->telegram_chat_id, $message);
            if ($success) {
                $sentCount++;
                if ($this->option('check-time')) {
                    Cache::put("daily_schedule_sent_{$user->id}_{$now->toDateString()}", true, $now->copy()->endOfDay());
                }
                $this->info("✓ Jadwal harian terkirim ke: {$user->name} ({$user->email})");
            } else {
                $this->warn("✗ Gagal mengirim jadwal harian ke: {$user->name}");
            }
        }

        $this->info("Selesai! Total jadwal harian terkirim: {$sentCount} pengguna.");
    }
}
