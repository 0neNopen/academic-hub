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
                <h2 className="text-lg font-bold text-gray-900 dark:text-white">
                    Informasi Profil & Preferensi Notifikasi
                </h2>
                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Kelola data identitas mahasiswa dan hubungkan bot pengingat kuliah harian Anda.
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
                            className="mt-1 block w-full shadow-xs"
                            value={data.name}
                            placeholder="Nama lengkap Anda"
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
                            className="mt-1 block w-full shadow-xs"
                            value={data.email}
                            onChange={(e) => setData("email", e.target.value)}
                            required
                            autoComplete="username"
                        />
                        <InputError className="mt-2" message={errors.email} />
                    </div>

                    {/* Nomor WhatsApp Kontak */}
                    <div className="sm:col-span-2">
                        <InputLabel htmlFor="whatsapp_number" value="Nomor WhatsApp (Kontak Mahasiswa)" />
                        <TextInput
                            id="whatsapp_number"
                            type="text"
                            className="mt-1 block w-full shadow-xs"
                            value={data.whatsapp_number}
                            onChange={(e) => setData("whatsapp_number", e.target.value)}
                            placeholder="Contoh: 081234567890 atau 6281234567890"
                        />
                        <p className="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                            Nomor telepon aktif Anda untuk keperluan kontak profil mahasiswa.
                        </p>
                        <InputError className="mt-2" message={errors.whatsapp_number} />
                    </div>
                </div>

                {/* Pengaturan Kanal Notifikasi Bot */}
                <div className="pt-6 border-t border-gray-100 dark:border-gray-800 space-y-4">
                    <div>
                        <h3 className="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <Send className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            Kanal Pengingat Jadwal & Deadline Tugas
                        </h3>
                        <p className="mt-1 text-xs text-gray-600 dark:text-gray-400">
                            Notifikasi otomatis dikirimkan setiap pagi untuk membantu Anda memantau perkuliahan.
                        </p>
                    </div>

                    {/* Kartu Status Kanal (Responsive Grid Mobile & Desktop) */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        {/* 1. Telegram Bot (Aktif & Direkomendasikan) */}
                        <div
                            onClick={() => setData("notification_channel", "telegram")}
                            className="relative flex flex-col justify-between p-4 rounded-xl border-2 border-blue-600 bg-blue-50/50 dark:bg-blue-950/40 shadow-xs cursor-pointer transition"
                        >
                            <span className="absolute -top-2.5 right-3 text-[10px] font-bold px-2.5 py-0.5 rounded-full shadow-xs text-white bg-blue-600">
                                Aktif & Resmi ⭐
                            </span>
                            <div className="flex items-start gap-3">
                                <div className="p-2 rounded-lg bg-blue-600 text-white shrink-0 mt-0.5 shadow-xs">
                                    <Send className="h-4 w-4" />
                                </div>
                                <div className="space-y-1">
                                    <div className="flex items-center gap-2">
                                        <input
                                            type="radio"
                                            name="notification_channel"
                                            value="telegram"
                                            checked={data.notification_channel === "telegram"}
                                            onChange={() => setData("notification_channel", "telegram")}
                                            className="text-blue-600 focus:ring-blue-500"
                                        />
                                        <h4 className="text-sm font-bold text-gray-900 dark:text-gray-100">
                                            Telegram Bot
                                        </h4>
                                    </div>
                                    <p className="text-xs text-blue-900 dark:text-blue-300 font-medium">
                                        100% Cepat, Stabil, & Gratis Selamanya
                                    </p>
                                    <p className="text-[11px] text-gray-600 dark:text-gray-400 leading-relaxed">
                                        Mendukung pengingat otomatis, tombol link tugas, dan perintah interaktif (/jadwal, /tugas, /reset).
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* 2. WhatsApp Bot (Nonaktif Sementara) */}
                        <div className="relative flex flex-col justify-between p-4 rounded-xl border border-gray-200 dark:border-gray-700/80 bg-gray-50/70 dark:bg-gray-800/40 opacity-80 cursor-not-allowed">
                            <span className="absolute -top-2.5 right-3 text-[10px] font-bold px-2 py-0.5 rounded-full shadow-xs text-amber-900 bg-amber-200 border border-amber-300 dark:bg-amber-950 dark:text-amber-200 dark:border-amber-800">
                                Nonaktif Sementara ⏳
                            </span>
                            <div className="flex items-start gap-3">
                                <div className="p-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 shrink-0 mt-0.5">
                                    <MessageSquare className="h-4 w-4" />
                                </div>
                                <div className="space-y-1">
                                    <div className="flex items-center gap-2">
                                        <input
                                            type="radio"
                                            disabled
                                            checked={false}
                                            className="text-gray-400 cursor-not-allowed"
                                        />
                                        <h4 className="text-sm font-bold text-gray-700 dark:text-gray-300">
                                            WhatsApp Bot
                                        </h4>
                                    </div>
                                    <p className="text-xs text-amber-800 dark:text-amber-400 font-medium">
                                        Ditunda Demi Layanan Tetap 100% Bebas Biaya
                                    </p>
                                    <p className="text-[11px] text-gray-500 dark:text-gray-400 leading-relaxed">
                                        Kebijakan API WhatsApp membatasi pesan multi-pengguna & mewajibkan biaya langganan gateway server.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* DETAIL PENGATURAN & PANDUAN TELEGRAM */}
                    <div className="p-4 sm:p-5 rounded-2xl bg-gradient-to-br from-sky-50/80 via-blue-50/40 to-white dark:from-sky-950/40 dark:via-gray-900 dark:to-gray-900 border border-sky-200/80 dark:border-sky-900/60 shadow-xs space-y-4">
                        <div className="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-sky-100 dark:border-sky-900/40">
                            <div className="flex items-center gap-2">
                                <div className="p-1.5 rounded-lg bg-sky-500 text-white shrink-0">
                                    <Send className="h-4 w-4" />
                                </div>
                                <h4 className="text-sm font-bold text-sky-950 dark:text-sky-100">
                                    Pengaturan Akun Bot Telegram
                                </h4>
                            </div>
                            {data.telegram_chat_id ? (
                                <span className="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-800 dark:text-emerald-300 bg-emerald-100/90 dark:bg-emerald-950/80 px-3 py-1 rounded-full border border-emerald-200 dark:border-emerald-800/60">
                                    <CheckCircle2 className="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" />
                                    Terhubung
                                </span>
                            ) : (
                                <span className="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-900 dark:text-amber-300 bg-amber-100/90 dark:bg-amber-950/80 px-3 py-1 rounded-full border border-amber-200 dark:border-amber-800/60">
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
                                Masukkan angka unik ID akun Telegram Anda untuk menerima notifikasi harian.
                            </p>
                            <InputError className="mt-2" message={errors.telegram_chat_id} />
                        </div>

                        {/* Panduan Langkah demi Langkah Telegram */}
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
                                    lalu klik <b>Start</b> agar bot memiliki izin mengirimkan pesan.
                                </li>
                                <li className="leading-relaxed">
                                    Salin angka <b>Id</b> Anda, tempelkan ke kolom Chat ID di atas, lalu klik <b>Simpan Perubahan</b> di bawah.
                                </li>
                            </ol>
                        </div>
                    </div>

                    {/* KARTU PENJELASAN TRANSPARAN MENGENAI WHATSAPP BOT */}
                    <div className="p-4 rounded-xl bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200/80 dark:border-amber-900/50 space-y-2">
                        <div className="flex items-center gap-2 text-amber-950 dark:text-amber-200">
                            <Info className="h-4 w-4 text-amber-600 dark:text-amber-400 shrink-0" />
                            <h4 className="text-xs sm:text-sm font-bold">
                                Mengapa Bot WhatsApp Dinonaktifkan Sementara?
                            </h4>
                        </div>
                        <div className="text-xs text-amber-900 dark:text-amber-300 leading-relaxed space-y-1.5 pl-6">
                            <p>
                                <b>1. Menjaga Layanan Tetap 100% Gratis:</b> Academic Hub dirancang sebagai aplikasi mandiri untuk mahasiswa tanpa pungutan biaya langganan server pihak ketiga.
                            </p>
                            <p>
                                <b>2. Kebijakan & Batasan API WhatsApp:</b> Layanan gateway WhatsApp gratis memiliki batasan sangat ketat (maksimal 3 kontak) atau rentan pemblokiran nomor (*banned*) permanen oleh Meta jika mengirim broadcast jadwal rutin. Sedangkan WhatsApp Business resmi mewajibkan biaya per pesan.
                            </p>
                            <p>
                                <b>3. Solusi Andal & Stabil via Telegram:</b> Bot Telegram menggunakan API resmi yang 100% gratis, aman tanpa risiko pemblokiran, bebas kuota, serta mendukung tombol interaktif untuk membuka tugas dan materi secara instan.
                            </p>
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
