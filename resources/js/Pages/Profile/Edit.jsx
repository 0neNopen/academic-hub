import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({ mustVerifyEmail, status }) {
    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h2 className="text-xl font-bold leading-tight text-gray-900 dark:text-white">
                        Pengaturan Profil
                    </h2>
                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Kelola data identitas, preferensi notifikasi deadline, dan keamanan akun Anda.
                    </p>
                </div>
            }
        >
            <Head title="Pengaturan Profil" />

            <div className="py-8">
                <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                        {/* Kolom Utama (Kiri): Profil & Preferensi Notifikasi */}
                        <div className="lg:col-span-7 xl:col-span-8 space-y-6">
                            <div className="bg-white dark:bg-gray-900 p-6 sm:p-8 rounded-xl border border-gray-100 dark:border-gray-800 shadow-sm">
                                <UpdateProfileInformationForm
                                    mustVerifyEmail={mustVerifyEmail}
                                    status={status}
                                />
                            </div>
                        </div>

                        {/* Kolom Samping (Kanan): Keamanan Sandi & Hapus Akun */}
                        <div className="lg:col-span-5 xl:col-span-4 space-y-6">
                            <div className="bg-white dark:bg-gray-900 p-6 sm:p-8 rounded-xl border border-gray-100 dark:border-gray-800 shadow-sm">
                                <UpdatePasswordForm />
                            </div>

                            <div className="bg-white dark:bg-gray-900 p-6 sm:p-8 rounded-xl border border-red-100 dark:border-red-900/40 shadow-sm">
                                <DeleteUserForm />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

