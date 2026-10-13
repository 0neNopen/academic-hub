<?php

namespace App\Jobs;

use App\Models\Assignment;
use App\Services\TelegramService;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendAssignmentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30, 60];

    public function __construct(
        public int $assignmentId,
        public bool $isH3 = false
    ) {}

    public function handle(WhatsAppService $waService, TelegramService $telegramService): void
    {
        $assignment = Assignment::with(['course.user'])->find($this->assignmentId);

        if (!$assignment || $assignment->status === 'completed') {
            return;
        }

        $user = $assignment->course?->user;
        if (!$user) {
            return;
        }

        // Hindari pengiriman ganda jika reminder telah terkirim
        if ($this->isH3 && $assignment->reminder_h3_sent_at !== null) {
            return;
        }
        if (!$this->isH3 && $assignment->reminder_h24_sent_at !== null) {
            return;
        }

        $tz = config('app.timezone', 'Asia/Jakarta');
        Carbon::setLocale('id');
        $now = Carbon::now($tz);
        $deadline = Carbon::parse($assignment->deadline)->locale('id')->timezone($tz);

        $diffInMinutes = (int) $now->diffInMinutes($deadline, false);
        $hoursLeft = round($diffInMinutes / 60, 1);

        if ($diffInMinutes <= 0) {
            return;
        }

        // Tentukan teks status hari pengumpulan
        if ($deadline->isToday()) {
            $statusWaktuWA = "akan jatuh tempo *HARI INI*";
            $statusWaktuTG = "akan jatuh tempo <b>HARI INI</b>";
        } elseif ($deadline->isTomorrow()) {
            $statusWaktuWA = "akan jatuh tempo *BESOK*";
            $statusWaktuTG = "akan jatuh tempo <b>BESOK</b>";
        } else {
            $statusWaktuWA = "akan jatuh tempo pada *" . $deadline->isoFormat('dddd, D MMMM YYYY') . "*";
            $statusWaktuTG = "akan jatuh tempo pada <b>" . $deadline->isoFormat('dddd, D MMMM YYYY') . "</b>";
        }

        $formattedDeadline = $deadline->isoFormat('dddd, D MMMM YYYY - HH:mm') . ' WIB';

        $urgencyTitleWA = $this->isH3 ? "🚨 *PERINGATAN MENDESAK: DEADLINE SEGERA BERAKHIR!* 🚨" : "⏳ *PENGINGAT DEADLINE KULIAH*";
        $urgencyTitleTG = $this->isH3 ? "🚨 <b>PERINGATAN MENDESAK: DEADLINE SEGERA BERAKHIR!</b> 🚨" : "⏳ <b>PENGINGAT DEADLINE KULIAH</b>";
        $timeRemainingText = ($diffInMinutes <= 90) ? "tinggal {$diffInMinutes} menit lagi!" : "sekitar {$hoursLeft} jam lagi";

        $descWA = '';
        if (!empty($assignment->description)) {
            $descWA = "📝 *Keterangan:* _" . Str::limit(trim($assignment->description), 200) . "_\n";
        }

        $descTG = '';
        if (!empty($assignment->description)) {
            $cleanDesc = htmlspecialchars(trim($assignment->description));
            $descTG = "📝 <b>Keterangan:</b> <i>" . Str::limit($cleanDesc, 200) . "</i>\n";
        }

        $typeTagWA = $assignment->assignment_type === 'group' ? "👥 *Tipe:* Tugas Kelompok\n" : "👤 *Tipe:* Tugas Individu\n";
        $typeTagTG = $assignment->assignment_type === 'group' ? "👥 <b>Tipe:</b> Tugas Kelompok\n" : "👤 <b>Tipe:</b> Tugas Individu\n";

        $assignmentLinks = $assignment->all_links;
        $linksWA = '';
        $linksTG = '';

        if (!empty($assignmentLinks)) {
            if (count($assignmentLinks) === 1) {
                $lTitle = $assignmentLinks[0]['title'] ?? 'Link Pengumpulan';
                $lUrl = $assignmentLinks[0]['url'];
                $linksWA = "🔗 *{$lTitle}:* {$lUrl}\n\n";
                $linksTG = "🔗 <b>" . htmlspecialchars($lTitle) . ":</b> <a href=\"" . htmlspecialchars($lUrl) . "\">" . htmlspecialchars($lUrl) . "</a>\n\n";
            } else {
                $linksWA = "🔗 *Tautan Terkait:*\n";
                $linksTG = "🔗 <b>Tautan Terkait:</b>\n";
                foreach ($assignmentLinks as $l) {
                    $lTitle = $l['title'] ?? 'Link';
                    $lUrl = $l['url'];
                    $linksWA .= "• {$lTitle}: {$lUrl}\n";
                    $linksTG .= "• " . htmlspecialchars($lTitle) . ": <a href=\"" . htmlspecialchars($lUrl) . "\">" . htmlspecialchars($lUrl) . "</a>\n";
                }
                $linksWA .= "\n";
                $linksTG .= "\n";
            }
        }

        // Format Pesan WhatsApp
        $waMessage = "{$urgencyTitleWA}\n\n"
            . "Halo *{$user->name}*!\n"
            . "Tugas *{$assignment->title}* ({$assignment->course->name}) {$statusWaktuWA}:\n\n"
            . $typeTagWA
            . "⏰ *Batas Pengumpulan:* {$formattedDeadline}\n"
            . "⏱️ *Sisa Waktu:* {$timeRemainingText}\n"
            . ($descWA ? "{$descWA}\n" : "\n")
            . $linksWA
            . "Yuk segera diselesaikan agar tidak terburu-buru!";

        // Format Pesan Telegram (HTML + Inline Keyboard Button)
        $tgMessage = "{$urgencyTitleTG}\n\n"
            . "Halo <b>" . htmlspecialchars($user->name) . "</b>!\n"
            . "Tugas <b>" . htmlspecialchars($assignment->title) . "</b> (" . htmlspecialchars($assignment->course->name) . ") {$statusWaktuTG}:\n\n"
            . $typeTagTG
            . "📅 <b>Batas Pengumpulan:</b> {$formattedDeadline}\n"
            . "⏱️ <b>Sisa Waktu:</b> {$timeRemainingText}\n"
            . ($descTG ? "{$descTG}\n" : "\n")
            . $linksTG
            . "Yuk segera diselesaikan dan kumpulkan tepat waktu!";

        $appUrl = config('app.url', 'http://localhost');
        $tgButtons = $telegramService->buildSubmissionButton($assignment->submission_url, $assignment->links, $appUrl);

        $channel = $user->notification_channel ?? 'telegram';
        $sentSuccess = false;
        $waConfigured = $waService->isConfigured();

        if ($channel === 'telegram') {
            $telegramSent = false;
            if (!empty($user->telegram_chat_id)) {
                $telegramSent = $telegramService->sendMessage($user->telegram_chat_id, $tgMessage, $tgButtons);
            }

            if ($telegramSent) {
                $sentSuccess = true;
                Log::info("[Queue][Telegram] Pengingat terkirim ke {$user->name} untuk: {$assignment->title}");
            } else {
                if ($waConfigured && !empty($user->whatsapp_number)) {
                    Log::warning("[Queue][Telegram Fallback] Mengaktifkan WhatsApp Fallback untuk {$user->name}...");
                    if ($waService->sendMessage($user->whatsapp_number, $waMessage)) {
                        $sentSuccess = true;
                        Log::info("[Queue][WhatsApp Fallback] Pengingat berhasil terkirim ke {$user->name}");
                    }
                }
            }
        } elseif ($channel === 'both') {
            if (!empty($user->telegram_chat_id) && $telegramService->sendMessage($user->telegram_chat_id, $tgMessage, $tgButtons)) {
                $sentSuccess = true;
                Log::info("[Queue][Telegram] Pengingat terkirim ke {$user->name} untuk: {$assignment->title}");
            }
            if ($waConfigured && !empty($user->whatsapp_number) && $waService->sendMessage($user->whatsapp_number, $waMessage)) {
                $sentSuccess = true;
                Log::info("[Queue][WhatsApp] Pengingat terkirim ke {$user->name} untuk: {$assignment->title}");
            }
        } else {
            // Kanal WhatsApp Saja
            if ($waConfigured && !empty($user->whatsapp_number)) {
                if ($waService->sendMessage($user->whatsapp_number, $waMessage)) {
                    $sentSuccess = true;
                    Log::info("[Queue][WhatsApp] Pengingat terkirim ke {$user->name} untuk: {$assignment->title}");
                }
            } else {
                if (!empty($user->telegram_chat_id) && $telegramService->sendMessage($user->telegram_chat_id, $tgMessage, $tgButtons)) {
                    $sentSuccess = true;
                    Log::info("[Queue][Telegram Fallback] WA ditunda, pengingat berhasil dialihkan ke Telegram untuk {$user->name}");
                }
            }
        }

        if ($sentSuccess) {
            if ($this->isH3) {
                $assignment->update(['reminder_h3_sent_at' => $now]);
            } else {
                $assignment->update(['reminder_h24_sent_at' => $now]);
            }
        }
    }
}
