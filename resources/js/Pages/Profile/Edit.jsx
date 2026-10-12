import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import { Bell, KeyRound, Shield, Trash2, User } from "lucide-react";
import { useState } from "react";
import DeleteUserForm from "./Partials/DeleteUserForm";
import UpdateNotificationSettingsForm from "./Partials/UpdateNotificationSettingsForm";
import UpdatePasswordForm from "./Partials/UpdatePasswordForm";
import UpdateProfileInformationForm from "./Partials/UpdateProfileInformationForm";

export default function Edit({ mustVerifyEmail, status }) {
    const searchParams =
        typeof window !== "undefined"
            ? new URLSearchParams(window.location.search)
            : null;
    const initialTab =
        searchParams?.get("tab") === "settings" ? "settings" : "profile";
    const [activeTab, setActiveTab] = useState(initialTab);

    return (
        <AuthenticatedLayout
            maxWidth="max-w-[1720px]"
            header={
                <div>
                    <h2 className="text-xl font-bold leading-tight text-gray-900 dark:text-white">
                        {activeTab === "settings"
                            ? "Pengaturan Notifikasi & Kampus"
                            : "Profil & Keamanan Akun"}
                    </h2>
                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {activeTab === "settings"
                            ? "Atur jadwal bot Telegram, toleransi pengingat deadline tugas, serta tautan portal kampus."
                            : "Kelola data identitas mahasiswa, kata sandi, dan keamanan akun Anda."}
                    </p>
                </div>
            }
        >
            <Head
                title={
                    activeTab === "settings"
                        ? "Pengaturan Notifikasi & Kampus"
                        : "Profil Akun"
                }
            />

            <div className="py-8">
                <div className="mx-auto max-w-[1720px] px-4 sm:px-6 lg:px-8 xl:px-10">
                    {/* Tab Navigation Segmented Control */}
                    <div className="flex border-b border-gray-200 dark:border-gray-800 mb-6 gap-2">
                        <button
                            type="button"
                            onClick={() => setActiveTab("profile")}
                            className={`inline-flex items-center gap-2 py-3 px-4 border-b-2 font-bold text-sm transition-all ${
                                activeTab === "profile"
                                    ? "border-blue-600 text-blue-600 dark:text-blue-400"
                                    : "border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                            }`}
                        >
                            <User className="h-4 w-4" />
                            Profil & Akun
                        </button>
                        <button
                            type="button"
                            onClick={() => setActiveTab("settings")}
                            className={`inline-flex items-center gap-2 py-3 px-4 border-b-2 font-bold text-sm transition-all ${
                                activeTab === "settings"
                                    ? "border-blue-600 text-blue-600 dark:text-blue-400"
                                    : "border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                            }`}
                        >
                            <Bell className="h-4 w-4" />
                            Pengaturan Notifikasi & Kampus
                        </button>
                    </div>

                    {/* Konten Tab 1: Profil & Keamanan Akun */}
                    {activeTab === "profile" && (
                        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                            {/* Kolom Kiri: Data Identitas Profil */}
                            <div className="lg:col-span-7 xl:col-span-8 space-y-6">
                                <div className="bg-white dark:bg-gray-900 p-6 sm:p-8 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-xs">
                                    <UpdateProfileInformationForm
                                        mustVerifyEmail={mustVerifyEmail}
                                        status={status}
                                    />
                                </div>
                            </div>

                            {/* Kolom Kanan: Keamanan Kata Sandi & Hapus Akun */}
                            <div className="lg:col-span-5 xl:col-span-4 space-y-6">
                                <div className="bg-white dark:bg-gray-900 p-6 sm:p-8 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-xs">
                                    <UpdatePasswordForm />
                                </div>

                                <div className="bg-white dark:bg-gray-900 p-6 sm:p-8 rounded-2xl border border-red-100 dark:border-red-900/40 shadow-xs">
                                    <DeleteUserForm />
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Konten Tab 2: Pengaturan Notifikasi & Kampus */}
                    {activeTab === "settings" && (
                        <UpdateNotificationSettingsForm />
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
