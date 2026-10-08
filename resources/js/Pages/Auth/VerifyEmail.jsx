import PrimaryButton from '@/Components/PrimaryButton';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function VerifyEmail({ status }) {
    const { post, processing } = useForm({});

    const submit = (e) => {
        e.preventDefault();

        post(route('verification.send'));
    };

    return (
        <GuestLayout>
            <Head title="Verifikasi Email" />

            <div className="mb-6">
                <h2 className="text-lg font-bold text-gray-900 dark:text-white">
                    Verifikasi Alamat Email
                </h2>
                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">
                    Terima kasih telah bergabung di Academic Hub! Silakan periksa kotak masuk email Anda dan klik tautan verifikasi untuk mengaktifkan akun Anda.
                </p>
            </div>

            {status === 'verification-link-sent' && (
                <div className="mb-5 p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs font-medium text-emerald-800 dark:text-emerald-300 leading-relaxed">
                    Tautan verifikasi baru telah berhasil dikirimkan ke alamat email yang Anda daftarkan.
                </div>
            )}

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <PrimaryButton
                        className="w-full justify-center py-2.5 rounded-xl text-xs font-semibold shadow-md shadow-blue-500/20"
                        disabled={processing}
                    >
                        {processing ? 'Mengirim Ulang...' : 'Kirim Ulang Email Verifikasi'}
                    </PrimaryButton>
                </div>

                <div className="pt-4 border-t border-gray-100 dark:border-gray-800 text-center">
                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:underline"
                    >
                        Keluar dari Akun
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}

