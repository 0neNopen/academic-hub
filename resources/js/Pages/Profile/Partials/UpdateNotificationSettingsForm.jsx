import InputError from "@/Components/InputError";
import InputLabel from "@/Components/InputLabel";
import PrimaryButton from "@/Components/PrimaryButton";
import TextInput from "@/Components/TextInput";
import { Transition } from "@headlessui/react";
import { useForm, usePage } from "@inertiajs/react";
import {
    AlarmClock,
    Bell,
    CheckCircle2,
    Clock,
    ExternalLink,
    GraduationCap,
    Info,
    MessageSquare,
    Send,
    ShieldAlert,
    Sparkles,
} from "lucide-react";

export default function UpdateNotificationSettingsForm({ className = "" }) {
    const user = usePage().props.auth.user;

    const { data, setData, patch, errors, processing, recentlySuccessful } =
        useForm({
            name: user.name,
            email: user.email,
            schedule_reminder_time: user.schedule_reminder_time || "06:00",
            task_reminder_early:
                user.task_reminder_early !== undefined && user.task_reminder_early !== null
                    ? String(user.task_reminder_early)
                    : "24",
            task_reminder_late:
                user.task_reminder_late !== undefined && user.task_reminder_late !== null
                    ? String(user.task_reminder_late)
                    : "3",
            campus_name: user.campus_name || "",
            campus_portal_url: user.campus_portal_url || "",
            notification_channel: "telegram",
            telegram_chat_id: user.telegram_chat_id || "",
        });

    const submit = (e) => {
        e.preventDefault();
        patch(route("profile.update"));
    };

    return (
        <section className={className}>
            <header>
                <div className="flex items-center gap-2">
                    <div className="p-2 rounded-lg bg-blue-600 text-white shadow-xs">
                        <Bell className="h-5 w-5" />
                    </div>
                    <div>
                        <h2 className="text-lg font-bold text-gray-900 dark:text-white">
                            Pengaturan Notifikasi Bot & Kampus
                        </h2>
                        <p className="text-xs text-gray-600 dark:text-gray-400">
                            Sesuaikan jadwal pengingat bot Telegram, deadline tugas, dan tautan portal kampus Anda.
                        </p>
                    </div>
                </div>
            </header>

            <form onSubmit={submit} className="mt-6 space-y-7">
                {/* 1. WAKTU PENGINGAT JADWAL KULIAH PAGI */}
                <div className="p-5 rounded-2xl bg-gradient-to-br from-blue-50/70 via-indigo-50/30 to-white dark:from-blue-950/30 dark:via-gray-900 dark:to-gray-900 border border-blue-200/70 dark:border-blue-900/50 space-y-4">
                    <div className="flex items-center gap-2.5">
                        <div className="p-1.5 rounded-lg bg-blue-600 text-white shadow-xs">
                            <AlarmClock className="h-4 w-4" />
                        </div>
                        <div>
                            <h3 className="text-sm font-bold text-gray-900 dark:text-white">
                                Jam Pengiriman Jadwal Kuliah Harian
                            </h3>
                            <p className="text-xs text-gray-600 dark:text-gray-400">
                                Bot Telegram akan mengirimkan rekap kelas dan tugas hari ini pada jam yang Anda pilih.
                            </p>
                        </div>
                    </div>

                    <div className="grid grid-cols-2 sm:grid-cols-5 gap-2.5 pt-1">
                        {[
                            { value: "05:00", label: "05:00 WIB", note: "Subuh / Awal" },
                            { value: "06:00", label: "06:00 WIB", note: "Standar (Paling Ideal)", popular: true },
                            { value: "07:00", label: "07:00 WIB", note: "Sebelum Kuliah" },
                            { value: "08:00", label: "08:00 WIB", note: "Awal Perkuliahan" },
                            { value: "09:00", label: "09:00 WIB", note: "Jadwal Siang" },
                        ].map((item) => (
                            <button
                                key={item.value}
                                type="button"
                                onClick={() => setData("schedule_reminder_time", item.value)}
                                className={`relative p-3 rounded-xl border text-left transition flex flex-col justify-between ${
                                    data.schedule_reminder_time === item.value
                                        ? "border-blue-600 bg-blue-600 text-white shadow-xs font-semibold ring-2 ring-blue-500/20"
                                        : "border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:border-blue-300 dark:hover:border-blue-800"
                                }`}
                            >
                                {item.popular && (
                                    <span
                                        className={`absolute -top-2 right-2 text-[9px] font-bold px-1.5 py-0.2 rounded-full ${
                                            data.schedule_reminder_time === item.value
                                                ? "bg-white text-blue-700"
                                                : "bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300"
                                        }`}
                                    >
                                        Rekomendasi ⭐
                                    </span>
                                )}
                                <div className="text-sm font-bold">{item.label}</div>
                                <div
                                    className={`text-[10px] mt-1 ${
                                        data.schedule_reminder_time === item.value
                                            ? "text-blue-100"
                                            : "text-gray-500 dark:text-gray-400"
                                    }`}
                                >
                                    {item.note}
                                </div>
                            </button>
                        ))}
                    </div>
                    <InputError message={errors.schedule_reminder_time} />
                </div>

                {/* 2. PENGINGAT DEADLINE TUGAS (2-FASE ANTI-SPAM) */}
                <div className="p-5 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-xs space-y-4">
                    <div className="flex items-center gap-2.5">
                        <div className="p-1.5 rounded-lg bg-amber-500 text-white shadow-xs">
                            <Clock className="h-4 w-4" />
                        </div>
                        <div>
                            <h3 className="text-sm font-bold text-gray-900 dark:text-white">
                                Preferensi Pengingat Deadline Tugas (2 Tahap Cerdas)
                            </h3>
                            <p className="text-xs text-gray-600 dark:text-gray-400">
                                Pengingat 2 kali menjaga Anda tetap produktif tanpa spamming notifikasi yang mengganggu.
                            </p>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                        {/* Tahap 1: Pengingat Awal (Rutin) */}
                        <div className="p-4 rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-800/40 space-y-2">
                            <label className="block text-xs font-bold text-gray-900 dark:text-white">
                                ⏳ Pengingat Tahap 1 (Persiapan Awal)
                            </label>
                            <p className="text-[11px] text-gray-500 dark:text-gray-400 leading-relaxed">
                                Notifikasi pertama untuk memberi waktu menyicil atau berkoordinasi kelompok.
                            </p>
                            <select
                                value={data.task_reminder_early}
                                onChange={(e) => setData("task_reminder_early", e.target.value)}
                                className="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-xs font-medium shadow-xs focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="48">H-48 Jam (2 Hari Sebelum Deadline)</option>
                                <option value="24">H-24 Jam (1 Hari Sebelum Deadline - Rekomendasi)</option>
                                <option value="12">H-12 Jam Sebelum Deadline</option>
                                <option value="0">Nonaktifkan Pengingat Tahap 1</option>
                            </select>
                            <InputError message={errors.task_reminder_early} />
                        </div>

                        {/* Tahap 2: Peringatan Mendesak */}
                        <div className="p-4 rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-800/40 space-y-2">
                            <label className="block text-xs font-bold text-gray-900 dark:text-white">
                                🚨 Pengingat Tahap 2 (Peringatan Mendesak)
                            </label>
                            <p className="text-[11px] text-gray-500 dark:text-gray-400 leading-relaxed">
                                Notifikasi darurat beberapa jam sebelum batas pengumpulan ditutup.
                            </p>
                            <select
                                value={data.task_reminder_late}
                                onChange={(e) => setData("task_reminder_late", e.target.value)}
                                className="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-xs font-medium shadow-xs focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="5">H-5 Jam Sebelum Deadline</option>
                                <option value="3">H-3 Jam Sebelum Deadline (Rekomendasi)</option>
                                <option value="1">H-1 Jam Sebelum Deadline (Sangat Mendesak)</option>
                                <option value="0">Nonaktifkan Peringatan Mendesak</option>
                            </select>
                            <InputError message={errors.task_reminder_late} />
                        </div>
                    </div>
                </div>

                {/* 3. INFORMASI KAMPUS & TAUTAN PORTAL / PRESENSI UNIVERSAL */}
                <div className="p-5 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-xs space-y-4">
                    <div className="flex items-center gap-2.5">
                        <div className="p-1.5 rounded-lg bg-emerald-600 text-white shadow-xs">
                            <GraduationCap className="h-4 w-4" />
                        </div>
                        <div>
                            <h3 className="text-sm font-bold text-gray-900 dark:text-white">
                                Informasi Kampus & Portal Presensi
                            </h3>
                            <p className="text-xs text-gray-600 dark:text-gray-400">
                                Mendukung seluruh universitas! Tautan ini akan disertakan pada notifikasi Telegram pagi.
                            </p>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                        <div>
                            <InputLabel htmlFor="campus_name" value="Nama Perguruan Tinggi / Kampus (Opsional)" />
                            <TextInput
                                id="campus_name"
                                type="text"
                                className="mt-1 block w-full shadow-xs text-sm"
                                placeholder="Contoh: Universitas Sebelas Maret, ITB, UI, dll"
                                value={data.campus_name}
                                onChange={(e) => setData("campus_name", e.target.value)}
                            />
                            <p className="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                Nama institusi kampus Anda.
                            </p>
                            <InputError message={errors.campus_name} />
                        </div>

                        <div>
                            <InputLabel htmlFor="campus_portal_url" value="Tautan Portal / Presensi / LMS (Opsional)" />
                            <TextInput
                                id="campus_portal_url"
                                type="url"
                                className="mt-1 block w-full shadow-xs text-sm"
                                placeholder="https://ocw.uns.ac.id atau https://siakad.kampus.ac.id"
                                value={data.campus_portal_url}
                                onChange={(e) => setData("campus_portal_url", e.target.value)}
                            />
                            <p className="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                URL web portal presensi atau SIAKAD kampus Anda.
                            </p>
                            <InputError message={errors.campus_portal_url} />
                        </div>
                    </div>
                </div>

                {/* 4. KONEKSI AKUN TELEGRAM BOT */}
                <div className="p-5 rounded-2xl bg-gradient-to-br from-sky-50/80 via-blue-50/40 to-white dark:from-sky-950/40 dark:via-gray-900 dark:to-gray-900 border border-sky-200/80 dark:border-sky-900/60 shadow-xs space-y-4">
                    <div className="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-sky-100 dark:border-sky-900/40">
                        <div className="flex items-center gap-2">
                            <div className="p-1.5 rounded-lg bg-sky-500 text-white shrink-0 shadow-xs">
                                <Send className="h-4 w-4" />
                            </div>
                            <div>
                                <h4 className="text-sm font-bold text-sky-950 dark:text-sky-100">
                                    Koneksi Bot Telegram
                                </h4>
                                <p className="text-xs text-sky-800 dark:text-sky-300">
                                    Kanal utama notifikasi jadwal harian & deadline tugas otomatis.
                                </p>
                            </div>
                        </div>
                        {data.telegram_chat_id ? (
                            <span className="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-800 dark:text-emerald-300 bg-emerald-100/90 dark:bg-emerald-950/80 px-3 py-1 rounded-full border border-emerald-200 dark:border-emerald-800/60 shadow-2xs">
                                <CheckCircle2 className="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" />
                                Terhubung
                            </span>
                        ) : (
                            <span className="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-900 dark:text-amber-300 bg-amber-100/90 dark:bg-amber-950/80 px-3 py-1 rounded-full border border-amber-200 dark:border-amber-800/60 shadow-2xs">
                                <Info className="h-3.5 w-3.5 text-amber-600 dark:text-amber-400" />
                                Belum Terhubung
                            </span>
                        )}
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="telegram_chat_id"
                            value="Telegram Chat ID Anda"
                            className="text-sky-950 dark:text-sky-200 font-semibold text-xs"
                        />
                        <TextInput
                            id="telegram_chat_id"
                            type="text"
                            className="mt-1 block w-full shadow-xs"
                            value={data.telegram_chat_id}
                            onChange={(e) => setData("telegram_chat_id", e.target.value)}
                            placeholder="Contoh: 1234567890"
                        />
                        <p className="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                            Masukkan angka unik ID akun Telegram Anda untuk menerima notifikasi kuliah.
                        </p>
                        <InputError className="mt-2" message={errors.telegram_chat_id} />
                    </div>

                    {/* Panduan 1 Menit */}
                    <div className="text-xs text-sky-950 dark:text-sky-200 bg-white dark:bg-gray-900/90 p-4 rounded-xl border border-sky-100 dark:border-sky-900/50 shadow-2xs space-y-2.5">
                        <p className="font-bold text-sky-950 dark:text-sky-100 flex items-center gap-1.5">
                            📌 Cara Menghubungkan Bot Telegram (Hanya 1 Menit):
                        </p>
                        <ol className="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300 ml-1">
                            <li className="leading-relaxed">
                                Buka bot pencari ID:{" "}
                                <a
                                    href="https://t.me/userinfobot"
                                    target="_blank"
                                    rel="noreferrer"
                                    className="font-semibold text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-0.5"
                                >
                                    @userinfobot <ExternalLink className="h-3 w-3 inline" />
                                </a>{" "}
                                lalu kirim <code>/start</code> untuk melihat angka <b>Id</b> Anda.
                            </li>
                            <li className="leading-relaxed">
                                Buka bot kampus kita:{" "}
                                <a
                                    href="https://t.me/academic_hub_notif_bot"
                                    target="_blank"
                                    rel="noreferrer"
                                    className="font-semibold text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-0.5"
                                >
                                    @academic_hub_notif_bot <ExternalLink className="h-3 w-3 inline" />
                                </a>{" "}
                                lalu klik <b>Start</b> agar bot memiliki izin mengirimkan pesan ke akun Anda.
                            </li>
                            <li className="leading-relaxed">
                                Salin angka <b>Id</b> Anda, tempelkan ke kolom Chat ID di atas, lalu klik <b>Simpan Pengaturan</b> di bawah.
                            </li>
                        </ol>
                    </div>
                </div>

                {/* 5. PENJELASAN MENGENAI BOT WHATSAPP */}
                <div className="p-4 rounded-xl bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200/80 dark:border-amber-900/50 space-y-2">
                    <div className="flex items-center gap-2 text-amber-950 dark:text-amber-200">
                        <Info className="h-4 w-4 text-amber-600 dark:text-amber-400 shrink-0" />
                        <h4 className="text-xs sm:text-sm font-bold">
                            Mengapa Menggunakan Telegram Dibanding WhatsApp?
                        </h4>
                    </div>
                    <div className="text-xs text-amber-900 dark:text-amber-300 leading-relaxed space-y-1.5 pl-6">
                        <p>
                            • <b>100% Gratis & Stabil Selamanya:</b> Academic Hub tidak membebankan biaya langganan apapun kepada mahasiswa. Gateway WhatsApp berbayar, sedangkan Telegram menyediakan API resmi tanpa batas dan tanpa biaya.
                        </p>
                        <p>
                            • <b>Fitur Interaktif & Bebas Blokir:</b> Notifikasi Telegram dilengkapi tombol link presensi/tugas instan dan perintah cepat (<code>/jadwal</code>, <code>/tugas</code>, <code>/semua_jadwal</code>).
                        </p>
                    </div>
                </div>

                {/* TOMBOL SIMPAN PENGATURAN */}
                <div className="flex items-center gap-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                    <PrimaryButton disabled={processing} className="shadow-xs">
                        {processing ? "Menyimpan..." : "Simpan Pengaturan Notifikasi"}
                    </PrimaryButton>

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out"
                        leaveTo="opacity-0"
                    >
                        <p className="text-sm font-medium text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                            <CheckCircle2 className="h-4 w-4" /> Pengaturan berhasil disimpan.
                        </p>
                    </Transition>
                </div>
            </form>
        </section>
    );
}
