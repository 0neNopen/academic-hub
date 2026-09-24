<?php

namespace App\Console\Commands;

use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class TestWhatsAppMessage extends Command
{
    protected $signature = 'wa:test {phone}';
    protected $description = 'Tes kirim notifikasi WhatsApp';

    public function handle(WhatsAppService $waService): void
    {
        $phone = $this->argument('phone');
        $this->info("Mengirim pesan ke $phone...");

        $msg = "🎓 *Academic Hub - Notifikasi Kuliah*\n\nHalo! Integrasi bot pengingat tugas kuliah Anda di Laravel berhasil terhubung. Sistem siap memantau deadline tugas Anda!";

        if ($waService->sendMessage($phone, $msg)) {
            $this->info("✅ Pesan WhatsApp berhasil terkirim!");
        } else {
            $this->error("❌ Gagal mengirim pesan. Periksa token di .env.");
        }
    }
}