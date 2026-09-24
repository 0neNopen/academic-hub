import InputError from "@/Components/InputError";
import InputLabel from "@/Components/InputLabel";
import PrimaryButton from "@/Components/PrimaryButton";
import TextInput from "@/Components/TextInput";
import { Transition } from "@headlessui/react";
import { Link, useForm, usePage } from "@inertiajs/react";
import { MessageSquare, Send, CheckCircle2, Info, ExternalLink } from "lucide-react";

export default function UpdateProfileInformation({
    mustVerifyEmail,
    status,
    className = "",
}) {
    const user = usePage().props.auth.user;

    const { data, setData, patch, errors, processing, recentlySuccessful } =
        useForm({
            name: user.name,
            email: user.email,
            whatsapp_number: user.whatsapp_number || "",
            notification_channel: user.notification_channel || "whatsapp",
            telegram_chat_id: user.telegram_chat_id || "",
        });

    const submit = (e) => {
        e.preventDefault();
        patch(route("profile.update"));
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-bold text-gray-900 dark:text-white">
                    Informasi Profil & Preferensi Notifikasi
                </h2>
                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Kelola data identitas dan tentukan kanal otomatis pengingat deadline kuliah Anda.
                </p>
            </header>

            <form onSubmit={submit} className="mt-6 space-y-6">
                {/* Data Identitas Mahasiswa */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {/* Nama Lengkap */}
                    <div>
                        <InputLabel htmlFor="name" value="Nama Lengkap" />
                        <TextInput
                            id="name"
                            className="mt-1 block w-full"
                            value={data.name}
                            onChange={(e) => setData("name", e.target.value)}
                            required
                            isFocused
                            autoComplete="name"
                        />
                        <InputError className="mt-2" message={errors.name} />
                    </div>

                    {/* Alamat Email */}
                    <div>
                        <InputLabel htmlFor="email" value="Alamat Email" />
                        <TextInput
                            id="email"
                            type="email"
                            className="mt-1 block w-full"
                            value={data.email}
                            onChange={(e) => setData("email", e.target.value)}
                            required
                            autoComplete="username"
                        />
                        <InputError className="mt-2" message={errors.email} />
                    </div>
                </div>

                {/* Preferensi Notifikasi Multi-Kanal */}
                <div className="pt-6 border-t border-gray-100 dark:border-gray-800">
                    <div className="flex items-center justify-between mb-2">
                        <h3 className="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <MessageSquare className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            Kanal Pengingat Deadline Tugas
                        </h3>
                    </div>

                    {/* Banner Rekomendasi Telegram */}
                    <div className="mb-4 rounded-xl bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-950/40 dark:to-indigo-950/40 p-4 border border-blue-100 dark:border-blue-900/60 flex items-start gap-3">
                        <div className="p-2 bg-blue-600 text-white rounded-lg shrink-0 mt-0.5 shadow-sm">
                            <Send className="h-4 w-4" />
                        </div>
                        <div className="text-xs space-y-1">
                            <p className="font-bold text-blue-900 dark:text-blue-200 text-sm">
                                💡 Saran: Gunakan Bot Telegram untuk Pengalaman Terbaik
                            </p>
                            <p className="text-blue-800 dark:text-blue-300 leading-relaxed">
                                Notifikasi Telegram terkirim lebih cepat, 100% stabil, tanpa batasan kuota, dan dilengkapi tombol interaktif untuk langsung membuka link pengumpulan tugas.
                            </p>
                        </div>
                    </div>

                    {/* Radio Cards Pilihan Kanal */}
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
                        {[
                            {
                                id: "telegram",
                                label: "Telegram Bot",
                                sublabel: "100% Cepat, Stabil & Gratis",
                                badge: "Aktif & Direkomendasikan ⭐",
                                icon: Send,
                                color: "text-sky-600 dark:text-sky-400",
                            },
                            {
                                id: "whatsapp",
                                label: "WhatsApp Bot",
                                sublabel: "Dalam Pemeliharaan",
                                badge: "Ditunda Sementara ⏳",
                                icon: MessageSquare,
                                color: "text-emerald-600 dark:text-emerald-400",
                            },
                            {
                                id: "both",
                                label: "Keduanya (WA + TG)",
                                sublabel: "Prioritas Telegram",
                                badge: null,
                                icon: CheckCircle2,
                                color: "text-indigo-600 dark:text-indigo-400",
                            },
                        ].map((ch) => {
                            const IconComponent = ch.icon;
                            const isSelected = data.notification_channel === ch.id;
                            return (
                                <label
                                    key={ch.id}
                                    className={`relative flex flex-col p-3.5 rounded-xl border cursor-pointer transition ${
                                        isSelected
                                            ? "border-blue-600 bg-blue-50/60 dark:bg-blue-950/50 ring-2 ring-blue-600/20 shadow-sm"
                                            : "border-gray-200 dark:border-gray-700 hover:bg-gray-50/80 dark:hover:bg-gray-800 bg-white dark:bg-gray-800/60"
                                    }`}
                                >
                                    {ch.badge && (
                                        <span className={`absolute -top-2.5 right-3 text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm ${
                                            ch.id === "telegram"
                                                ? "text-white bg-blue-600"
                                                : "text-amber-900 bg-amber-200 border border-amber-300 dark:bg-amber-900 dark:text-amber-200 dark:border-amber-700"
                                        }`}>
                                            {ch.badge}
                                        </span>
                                    )}
                                    <div className="flex items-center gap-2 mb-1">
                                        <input
                                            type="radio"
                                            name="notification_channel"
                                            value={ch.id}
                                            checked={isSelected}
                                            onChange={(e) => setData("notification_channel", e.target.value)}
                                            className="text-blue-600 focus:ring-blue-500"
                                        />
                                        <IconComponent className={`h-4 w-4 ${ch.color}`} />
                                        <span className="text-xs font-bold text-gray-900 dark:text-gray-100">{ch.label}</span>
                                    </div>
                                    <span className="text-[11px] text-gray-500 dark:text-gray-400 pl-6">{ch.sublabel}</span>
                                </label>
                            );
                        })}
                    </div>
                    <InputError className="mt-1" message={errors.notification_channel} />

                    {/* DETAIL PENGATURAN & PANDUAN: OPSI TELEGRAM */}
                    {(data.notification_channel === "telegram" || data.notification_channel === "both") && (
                        <div className="mt-4 p-4 rounded-xl bg-sky-50/70 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-900/60 space-y-4">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <Send className="h-4 w-4 text-sky-600 dark:text-sky-400" />
                                    <h4 className="text-sm font-bold text-sky-950 dark:text-sky-200">
                                        Pengaturan Bot Telegram
                                    </h4>
                                </div>
                                {data.telegram_chat_id ? (
                                    <span className="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-100 dark:bg-emerald-950/70 px-2.5 py-0.5 rounded-full border dark:border-emerald-900/60">
                                        <CheckCircle2 className="h-3.5 w-3.5" /> Terhubung
                                    </span>
                                ) : (
                                    <span className="inline-flex items-center gap-1 text-[11px] font-medium text-amber-800 dark:text-amber-300 bg-amber-100 dark:bg-amber-950/70 px-2.5 py-0.5 rounded-full border dark:border-amber-900/60">
                                        <Info className="h-3.5 w-3.5" /> Belum Terhubung
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
                                    className="mt-1 block w-full shadow-sm"
                                    value={data.telegram_chat_id}
                                    onChange={(e) => setData("telegram_chat_id", e.target.value)}
                                    placeholder="Contoh: 8684409030"
                                />
                                <InputError className="mt-2" message={errors.telegram_chat_id} />
                            </div>

                            {/* Panduan Langkah demi Langkah Telegram */}
                            <div className="text-xs text-sky-900 dark:text-sky-200 bg-white/90 dark:bg-gray-900/90 p-3.5 rounded-lg border border-sky-100 dark:border-sky-900/40 space-y-2">
                                <p className="font-bold text-sky-900 dark:text-sky-200 flex items-center gap-1.5">
                                    📌 Cara Menghubungkan Bot Telegram (Hanya 1 Menit):
                                </p>
                                <ol className="list-decimal list-inside space-y-1.5 text-gray-700 dark:text-gray-300 ml-1">
                                    <li>
                                        Buka bot pencari ID:{" "}
                                        <a
                                            href="https://t.me/userinfobot"
                                            target="_blank"
                                            rel="noreferrer"
                                            className="font-semibold text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-0.5"
                                        >
                                            @userinfobot <ExternalLink className="h-3 w-3" />
                                        </a>{" "}
                                        lalu kirim <code>/start</code> untuk melihat angka <b>Id</b> Anda.
                                    </li>
                                    <li>
                                        Buka bot kampus kita:{" "}
                                        <a
                                            href="https://t.me/academic_hub_notif_bot"
                                            target="_blank"
                                            rel="noreferrer"
                                            className="font-semibold text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-0.5"
                                        >
                                            @academic_hub_notif_bot <ExternalLink className="h-3 w-3" />
                                        </a>{" "}
                                        lalu klik <b>Start</b> agar bot memiliki izin mengirimkan pesan.
                                    </li>
                                    <li>Salin angka <b>Id</b> Anda, tempelkan ke kolom Chat ID di atas, lalu klik <b>Simpan Perubahan</b> di bawah.</li>
                                </ol>
                            </div>
                        </div>
                    )}

                    {/* DETAIL PENGATURAN & PANDUAN: OPSI WHATSAPP */}
                    <div className="mt-4">
                        <div className={`p-4 rounded-xl border transition space-y-3 ${
                            data.notification_channel === "telegram"
                                ? "bg-amber-50/70 dark:bg-amber-950/30 border-amber-200 dark:border-amber-900/50"
                                : "bg-emerald-50/70 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-900/50"
                        }`}>
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <MessageSquare className={`h-4 w-4 ${
                                        data.notification_channel === "telegram" ? "text-amber-700 dark:text-amber-400" : "text-emerald-700 dark:text-emerald-400"
                                    }`} />
                                    <h4 className={`text-sm font-bold ${
                                        data.notification_channel === "telegram" ? "text-amber-950 dark:text-amber-200" : "text-emerald-950 dark:text-emerald-200"
                                    }`}>
                                        {data.notification_channel === "telegram"
                                            ? "Nomor WhatsApp Cadangan (Otomatis Aktif jika Telegram Kendala)"
                                            : "Pengaturan Bot WhatsApp"}
                                    </h4>
                                </div>
                                {data.notification_channel === "telegram" && (
                                    <span className="text-[11px] font-semibold text-amber-800 dark:text-amber-300 bg-amber-100 dark:bg-amber-950/70 px-2 py-0.5 rounded-full border dark:border-amber-900/60">
                                        Jalur Bantuan Aktif
                                    </span>
                                )}
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="whatsapp_number"
                                    value="Nomor WhatsApp Anda"
                                    className="font-semibold text-xs text-gray-800 dark:text-gray-200"
                                />
                                <TextInput
                                    id="whatsapp_number"
                                    type="text"
                                    className="mt-1 block w-full shadow-sm"
                                    value={data.whatsapp_number}
                                    onChange={(e) => setData("whatsapp_number", e.target.value)}
                                    placeholder="Contoh: 081234567890 atau 6281234567890"
                                />
                                <InputError className="mt-2" message={errors.whatsapp_number} />
                            </div>

                            {/* Panduan Langkah demi Langkah WhatsApp */}
                            <div className="text-xs bg-amber-50/90 dark:bg-amber-950/40 p-3 rounded-lg border border-amber-200 dark:border-amber-900/50 space-y-1 text-amber-900 dark:text-amber-200">
                                <p className="font-semibold text-amber-950 dark:text-amber-200 flex items-center gap-1">
                                    ⏳ Status Bot WhatsApp (Ditunda Sementara):
                                </p>
                                <ul className="list-disc list-inside space-y-1 ml-1 text-amber-900 dark:text-amber-300">
                                    <li>Pengiriman bot WhatsApp saat ini ditunda sementara karena keterbatasan kuota & perangkat.</li>
                                    <li>Nomor telepon Anda tetap tersimpan aman sebagai identitas profil mahasiswa.</li>
                                    <li><b>Sangat disarankan memakai Bot Telegram</b> di atas untuk menerima notifikasi deadline tugas secara 100% stabil, cepat, dan tanpa hambatan.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                {mustVerifyEmail && user.email_verified_at === null && (
                    <div>
                        <p className="mt-2 text-sm text-gray-800 dark:text-gray-200">
                            Email Anda belum terverifikasi.{" "}
                            <Link
                                href={route("verification.send")}
                                method="post"
                                as="button"
                                className="rounded-md text-sm text-gray-600 dark:text-gray-400 underline hover:text-gray-900 dark:hover:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                Klik di sini untuk mengirim ulang email verifikasi.
                            </Link>
                        </p>

                        {status === "verification-link-sent" && (
                            <div className="mt-2 text-sm font-medium text-green-600 dark:text-green-400">
                                Tautan verifikasi baru telah dikirimkan ke email Anda.
                            </div>
                        )}
                    </div>
                )}

                <div className="flex items-center gap-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                    <PrimaryButton disabled={processing}>Simpan Perubahan</PrimaryButton>

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out"
                        leaveTo="opacity-0"
                    >
                        <p className="text-sm font-medium text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                            <CheckCircle2 className="h-4 w-4" /> Tersimpan dengan sukses.
                        </p>
                    </Transition>
                </div>
            </form>
        </section>
    );
}
