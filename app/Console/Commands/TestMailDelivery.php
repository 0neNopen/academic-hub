<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMailDelivery extends Command
{
    protected $signature = 'mail:test {email : Alamat email tujuan pengujian}';
    protected $description = 'Kirim email uji coba untuk memverifikasi pengaturan SMTP Gmail';

    public function handle(): int
    {
        $recipient = $this->argument('email');

        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->error("Format email '{$recipient}' tidak valid.");
            return 1;
        }

        $mailer = config('mail.default');
        $host = config('mail.mailers.smtp.host');
        $port = config('mail.mailers.smtp.port');
        $username = config('mail.mailers.smtp.username');

        $this->info("Mengirim email uji coba ke: {$recipient}");
        $this->line("Detail Mailer: driver={$mailer}, host={$host}, port={$port}, user={$username}");

        try {
            Mail::raw(
                "Halo Mahasiswa!\n\nIni adalah email uji coba dari Academic Hub.\nPengaturan SMTP Gmail Anda telah BERHASIL terhubung dan siap digunakan untuk fitur Lupa Kata Sandi.\n\nWaktu Kirim: " . now()->setTimezone('Asia/Jakarta')->isoFormat('dddd, D MMMM YYYY - HH:mm:ss') . " WIB\n\nSalam,\nTim Pengembang Academic Hub",
                function ($message) use ($recipient) {
                    $message->to($recipient)
                        ->subject('✅ [Academic Hub] Uji Coba Pengiriman Email Berhasil');
                }
            );

            $this->info("✅ Email uji coba BERHASIL dikirim ke {$recipient}!");
            return 0;
        } catch (\Throwable $e) {
            $this->error("❌ Gagal mengirim email: " . $e->getMessage());
            return 1;
        }
    }
}
