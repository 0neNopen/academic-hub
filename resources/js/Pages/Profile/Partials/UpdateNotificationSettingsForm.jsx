import InputError from "@/Components/InputError";
import InputLabel from "@/Components/InputLabel";
import PrimaryButton from "@/Components/PrimaryButton";
import TextInput from "@/Components/TextInput";
import { Transition } from "@headlessui/react";
import { useForm, usePage } from "@inertiajs/react";
import {
    AlarmClock,
    CheckCircle2,
    Clock,
    ExternalLink,
    Globe,
    GraduationCap,
    Info,
    Plus,
    Send,
    Trash2,
} from "lucide-react";

export default function UpdateNotificationSettingsForm({ className = "" }) {
    const user = usePage().props.auth.user;

    const initialPortals = () => {
        if (user.campus_portals && Array.isArray(user.campus_portals) && user.campus_portals.length > 0) {
            return user.campus_portals;
        }
        if (user.campus_portal_url) {
            return [{ name: "Portal SIAKAD", url: user.campus_portal_url }];
        }
        return [{ name: "SIAKAD", url: "" }];
    };

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
            campus_portals: initialPortals(),
            notification_channel: "telegram",
            telegram_chat_id: user.telegram_chat_id || "",
        });

    const updatePortal = (index, field, value) => {
        const updated = [...data.campus_portals];
        updated[index] = { ...updated[index], [field]: value };
        setData("campus_portals", updated);
    };

    const addPortal = () => {
        if (data.campus_portals.length < 5) {
            setData("campus_portals", [...data.campus_portals, { name: "", url: "" }]);
        }
    };

    const removePortal = (index) => {
        if (data.campus_portals.length === 1) {
            setData("campus_portals", [{ name: "", url: "" }]);
        } else {
            const updated = data.campus_portals.filter((_, i) => i !== index);
            setData("campus_portals", updated);
        }
    };

    const submit = (e) => {
        e.preventDefault();
        const firstValid = data.campus_portals.find((p) => p.url && p.url.trim() !== "");
        data.campus_portal_url = firstValid ? firstValid.url.trim() : "";
        patch(route("profile.update"));
    };

    return (
        <section className={className}>
            <form onSubmit={submit} className="space-y-6">
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    {/* Kolom Kiri (8): Jam Pengiriman, Pengingat Deadline & Portal Kampus */}
                    <div className="lg:col-span-7 xl:col-span-8 space-y-6">
                        {/* 1. WAKTU PENGINGAT JADWAL KULIAH PAGI */}
                        <div className="p-5 sm:p-6 rounded-2xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-xs space-y-4">
                            <div className="flex items-center gap-2.5">
                                <div className="p-1.5 rounded-lg bg-blue-600 text-white shadow-xs">
                                    <AlarmClock className="h-4 w-4" />
                                </div>
                                <div>
                                    <h3 className="text-sm font-bold text-gray-900 dark:text-white">
                                        Jam Pengiriman Jadwal Kuliah Harian
                                    </h3>
                                    <p className="text-xs text-gray-600 dark:text-gray-400">
                                        Bot Telegram akan mengirimkan rekap kelas dan tugas hari ini pada jam yang Anda tentukan.
                                    </p>
                                </div>
                            </div>

                            <div className="space-y-4 pt-1">
                                {/* Input Waktu Utama */}
                                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-xl bg-gray-50/70 dark:bg-gray-800/50 border border-gray-200/80 dark:border-gray-700/80 shadow-2xs">
                                    <div className="space-y-1">
                                        <label
                                            htmlFor="schedule_reminder_time"
                                            className="block text-xs font-bold text-gray-900 dark:text-white"
                                        >
                                            Pilih Jam Pengingat (Format 24 Jam)
                                        </label>
                                        <p className="text-[11px] text-gray-500 dark:text-gray-400">
                                            Atur jam dan menit pengingat (Default: 06:00 pagi).
                                        </p>
                                    </div>

                                    <div className="flex items-center gap-2.5">
                                        <div className="relative flex items-center">
                                            <Clock className="absolute left-3.5 h-4 w-4 text-blue-600 dark:text-sky-400 pointer-events-none" />
                                            <input
                                                id="schedule_reminder_time"
                                                type="time"
                                                value={data.schedule_reminder_time || "06:00"}
                                                onChange={(e) => setData("schedule_reminder_time", e.target.value)}
                                                className="pl-10 pr-3.5 py-2 text-lg font-extrabold font-mono tracking-wider rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-blue-600 dark:text-sky-300 [color-scheme:light] dark:[color-scheme:dark] shadow-xs focus:border-blue-500 focus:ring-blue-500 cursor-pointer [&::-webkit-calendar-picker-indicator]:cursor-pointer [&::-webkit-calendar-picker-indicator]:opacity-80 hover:[&::-webkit-calendar-picker-indicator]:opacity-100"
                                            />
                                        </div>
                                        <span className="text-xs font-bold text-blue-700 dark:text-sky-200 bg-blue-100/90 dark:bg-blue-950/90 px-3 py-2.5 rounded-xl border border-blue-200/80 dark:border-blue-800/80 shadow-2xs">
                                            WIB
                                        </span>
                                    </div>
                                </div>

                                {/* Tombol Pintas / Quick Selection Chips */}
                                <div className="space-y-1.5">
                                    <span className="text-[11px] font-semibold text-gray-600 dark:text-gray-400">
                                        Pilihan Cepat (Klik untuk langsung setel):
                                    </span>
                                    <div className="flex flex-wrap items-center gap-2">
                                        {[
                                            { time: "05:00", label: "05:00 Subuh" },
                                            { time: "05:30", label: "05:30 Pagi Awal" },
                                            { time: "06:00", label: "06:00 Standar (Default) ⭐" },
                                            { time: "06:30", label: "06:30 Sarapan" },
                                            { time: "07:00", label: "07:00 Sebelum Kelas" },
                                            { time: "08:00", label: "08:00 Awal Kuliah" },
                                            { time: "09:00", label: "09:00 Kuliah Siang" },
                                        ].map((preset) => (
                                            <button
                                                key={preset.time}
                                                type="button"
                                                onClick={() => setData("schedule_reminder_time", preset.time)}
                                                className={`text-xs px-2.5 py-1 rounded-lg border font-medium transition cursor-pointer ${
                                                    data.schedule_reminder_time === preset.time
                                                        ? "border-blue-600 bg-blue-600 text-white shadow-2xs font-bold ring-2 ring-blue-500/20"
                                                        : "border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:border-blue-300 dark:hover:border-blue-700"
                                                }`}
                                            >
                                                {preset.label}
                                            </button>
                                        ))}
                                    </div>
                                </div>

                                {/* Banner Konfirmasi Waktu Aktif */}
                                <div className="p-3 rounded-xl bg-blue-50/60 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-900/40 text-xs text-blue-900 dark:text-blue-300 flex items-center gap-2">
                                    <Clock className="h-4 w-4 text-blue-600 dark:text-blue-400 shrink-0" />
                                    <span>
                                        Bot Telegram akan mengirimkan rekap kuliah & deadline tugas setiap hari pukul{" "}
                                        <strong className="font-mono font-bold text-blue-700 dark:text-blue-200">
                                            {data.schedule_reminder_time || "06:00"} WIB
                                        </strong>.
                                    </span>
                                </div>
                            </div>
                            <InputError message={errors.schedule_reminder_time} />
                        </div>

                        {/* 2. PENGINGAT DEADLINE TUGAS (2-FASE ANTI-SPAM) */}
                        <div className="p-5 sm:p-6 rounded-2xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-xs space-y-4">
                            <div className="flex items-center gap-2.5">
                                <div className="p-1.5 rounded-lg bg-amber-500 text-white shadow-xs">
                                    <Clock className="h-4 w-4" />
                                </div>
                                <div>
                                    <h3 className="text-sm font-bold text-gray-900 dark:text-white">
                                        Preferensi Pengingat Deadline Tugas (2 Tahap Cerdas)
                                    </h3>
                                    <p className="text-xs text-gray-600 dark:text-gray-400">
                                        Pengingat 2 kali menjaga Anda tetap produktif tanpa spam notifikasi berlebihan.
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

                        {/* 3. INFORMASI KAMPUS & TAUTAN PORTAL / PRESENSI MULTI-LINK */}
                        <div className="p-5 sm:p-6 rounded-2xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-xs space-y-5">
                            <div className="flex items-center gap-2.5">
                                <div className="p-1.5 rounded-lg bg-emerald-600 text-white shadow-xs">
                                    <GraduationCap className="h-4 w-4" />
                                </div>
                                <div>
                                    <h3 className="text-sm font-bold text-gray-900 dark:text-white">
                                        Informasi Kampus & Tautan Portal / SIAKAD / LMS
                                    </h3>
                                    <p className="text-xs text-gray-600 dark:text-gray-400">
                                        Tautan akan langsung tersedia di menu Akses Cepat Dashboard dan bot Telegram.
                                    </p>
                                </div>
                            </div>

                            {/* Nama Kampus */}
                            <div>
                                <InputLabel htmlFor="campus_name" value="Nama Perguruan Tinggi / Kampus (Opsional)" />
                                <TextInput
                                    id="campus_name"
                                    type="text"
                                    className="mt-1 block w-full shadow-xs text-sm"
                                    placeholder="Contoh: Universitas Sebelas Maret, Institut Teknologi..., Politeknik..."
                                    value={data.campus_name}
                                    onChange={(e) => setData("campus_name", e.target.value)}
                                />
                                <p className="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                    Nama institusi kampus Anda (akan ditampilkan sebagai label tautan di bot Telegram dan dashboard).
                                </p>
                                <InputError message={errors.campus_name} />
                            </div>

                            {/* Multi-Portal Links */}
                            <div className="space-y-3 pt-2 border-t border-gray-100 dark:border-gray-800">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <label className="block text-xs font-bold text-gray-900 dark:text-white">
                                            Tautan Portal Kampus, LMS & Presensi (Bisa Lebih Dari 1)
                                        </label>
                                        <p className="text-[11px] text-gray-500 dark:text-gray-400">
                                            Sertakan portal penting seperti SIAKAD, SPADA, E-learning (Moodle), atau Presensi Online.
                                        </p>
                                    </div>
                                    {data.campus_portals.length < 5 && (
                                        <button
                                            type="button"
                                            onClick={addPortal}
                                            className="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-100 dark:hover:bg-blue-900/60 px-3 py-1.5 rounded-lg border border-blue-200 dark:border-blue-800/80 transition shadow-2xs cursor-pointer"
                                        >
                                            <Plus className="h-3.5 w-3.5" />
                                            Tambah Portal
                                        </button>
                                    )}
                                </div>

                                <div className="space-y-3">
                                    {data.campus_portals.map((portal, index) => (
                                        <div
                                            key={index}
                                            className="p-3.5 rounded-xl bg-gray-50/80 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700/80 space-y-2.5 transition"
                                        >
                                            <div className="flex items-center justify-between gap-2">
                                                <span className="text-xs font-bold text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                                                    <Globe className="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" />
                                                    Portal #{index + 1}
                                                </span>
                                                {data.campus_portals.length > 1 && (
                                                    <button
                                                        type="button"
                                                        onClick={() => removePortal(index)}
                                                        className="text-xs text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-950/40 p-1.5 rounded-md transition cursor-pointer"
                                                        title="Hapus portal ini"
                                                    >
                                                        <Trash2 className="h-3.5 w-3.5" />
                                                    </button>
                                                )}
                                            </div>

                                            <div className="grid grid-cols-1 sm:grid-cols-5 gap-2.5">
                                                <div className="sm:col-span-2">
                                                    <label className="block text-[11px] font-semibold text-gray-600 dark:text-gray-400 mb-1">
                                                        Label Portal
                                                    </label>
                                                    <TextInput
                                                        type="text"
                                                        className="block w-full text-xs py-1.5"
                                                        placeholder="Misal: SIAKAD, SPADA, Presensi"
                                                        value={portal.name}
                                                        onChange={(e) => updatePortal(index, "name", e.target.value)}
                                                    />
                                                    {/* Preset tags */}
                                                    <div className="flex items-center gap-1.5 mt-1.5">
                                                        <span className="text-[10px] text-gray-400">Pilih:</span>
                                                        {["SIAKAD", "SPADA", "Presensi"].map((tag) => (
                                                            <button
                                                                key={tag}
                                                                type="button"
                                                                onClick={() => updatePortal(index, "name", tag)}
                                                                className="text-[10px] px-1.5 py-0.5 rounded bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-blue-400 cursor-pointer"
                                                            >
                                                                +{tag}
                                                            </button>
                                                        ))}
                                                    </div>
                                                    <InputError message={errors[`campus_portals.${index}.name`]} />
                                                </div>

                                                <div className="sm:col-span-3">
                                                    <label className="block text-[11px] font-semibold text-gray-600 dark:text-gray-400 mb-1">
                                                        Alamat URL Web (Harus diawali https://)
                                                    </label>
                                                    <TextInput
                                                        type="url"
                                                        className="block w-full text-xs py-1.5"
                                                        placeholder="https://siakad.nama-kampus.ac.id"
                                                        value={portal.url}
                                                        onChange={(e) => updatePortal(index, "url", e.target.value)}
                                                    />
                                                    <InputError message={errors[`campus_portals.${index}.url`]} />
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                <InputError message={errors.campus_portals} />
                            </div>
                        </div>

                        {/* Tombol Simpan di Bawah Kolom Kiri */}
                        <div className="flex items-center gap-4 pt-2">
                            <PrimaryButton disabled={processing} className="shadow-xs">
                                {processing ? "Menyimpan..." : "Simpan Pengaturan Notifikasi"}
                            </PrimaryButton>

                            <Transition
                                show={recentlySuccessful}
                                enter="transition ease-in-out duration-300"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out duration-300"
                                leaveTo="opacity-0"
                            >
                                <p className="text-sm font-medium text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                    <CheckCircle2 className="h-4 w-4" /> Pengaturan berhasil disimpan.
                                </p>
                            </Transition>
                        </div>
                    </div>

                    {/* Kolom Kanan (4): Aksi Simpan Cepat, Koneksi Telegram & FAQ */}
                    <div className="lg:col-span-5 xl:col-span-4 space-y-6 lg:sticky lg:top-6">
                        {/* Card Aksi Simpan Cepat */}
                        <div className="p-5 rounded-2xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-xs space-y-3">
                            <div className="flex items-center gap-2">
                                <div className="p-1.5 rounded-lg bg-blue-600 text-white shadow-xs">
                                    <CheckCircle2 className="h-4 w-4" />
                                </div>
                                <div>
                                    <h4 className="text-sm font-bold text-gray-900 dark:text-white">
                                        Simpan Perubahan
                                    </h4>
                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                        Terapkan waktu pengingat & portal kampus.
                                    </p>
                                </div>
                            </div>
                            <PrimaryButton disabled={processing} className="w-full justify-center py-2.5 shadow-xs text-xs">
                                {processing ? "Menyimpan..." : "Simpan Pengaturan"}
                            </PrimaryButton>
                            <Transition
                                show={recentlySuccessful}
                                enter="transition ease-in-out duration-300"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out duration-300"
                                leaveTo="opacity-0"
                            >
                                <p className="text-xs font-semibold text-emerald-600 dark:text-emerald-400 flex items-center justify-center gap-1.5 pt-1">
                                    <CheckCircle2 className="h-4 w-4" /> Pengaturan berhasil disimpan.
                                </p>
                            </Transition>
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
                                        Salin angka <b>Id</b> Anda, tempelkan ke kolom Chat ID di atas, lalu klik <b>Simpan Pengaturan</b>.
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
                                    • <b>Fitur Interaktif & Bebas Blokir:</b> Notifikasi Telegram dilengkapi tombol link presensi/tugas instan dan perintah cepat (<code>/jadwal</code>, <code>/tugas</code>, <code>/semua_jadwal</code>, <code>/portal</code>).
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </section>
    );
}
