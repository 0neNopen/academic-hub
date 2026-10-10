import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Send } from 'lucide-react';

export default function ForgotPassword({ status }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('password.email'));
    };

    return (
        <GuestLayout>
            <Head title="Lupa Kata Sandi" />

            {/* Tombol Back ke Halaman Masuk */}
            <div className="mb-5">
                <Link
                    href={route('login')}
                    className="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 transition group"
                >
                    <ArrowLeft className="h-4 w-4 group-hover:-translate-x-0.5 transition-transform" />
                    <span>Kembali ke Halaman Masuk</span>
                </Link>
            </div>

            <div className="mb-6">
                <h2 className="text-lg font-bold text-gray-900 dark:text-white">
                    Lupa Kata Sandi?
                </h2>
                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">
                    Jangan khawatir. Masukkan alamat email mahasiswa Anda, dan kami akan mengirimkan tautan untuk mengatur ulang kata sandi Anda.
                </p>
            </div>

            {status && (
                <div className="mb-5 p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs font-medium text-emerald-800 dark:text-emerald-300 leading-relaxed">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <InputLabel
                        htmlFor="email"
                        value="Alamat Email Mahasiswa"
                        className="text-xs font-semibold text-gray-700 dark:text-gray-300"
                    />

                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        placeholder="nama@kampus.ac.id"
                        className="mt-1 block w-full text-sm rounded-xl"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                        required
                    />

                    <InputError message={errors.email} className="mt-1.5" />
                </div>

                <div className="p-3 rounded-xl bg-blue-50/70 dark:bg-blue-950/40 border border-blue-100 dark:border-blue-900/50 flex items-start gap-2.5">
                    <div className="mt-0.5 p-1 rounded-lg bg-blue-600 text-white shrink-0">
                        <Send className="h-3 w-3" />
                    </div>
                    <div className="text-[11px] leading-relaxed text-gray-600 dark:text-gray-300">
                        <span className="font-semibold text-gray-900 dark:text-white">Opsi Instan:</span>
                        {' '}Jika akun sudah terhubung ke Telegram, tautan reset akan otomatis dikirim ke bot <a href="https://t.me/academic_hub_notif_bot" target="_blank" rel="noreferrer" className="text-blue-600 dark:text-blue-400 font-semibold hover:underline">@academic_hub_notif_bot</a>.
                    </div>
                </div>

                <div className="pt-2">
                    <PrimaryButton
                        className="w-full justify-center py-2.5 rounded-xl text-xs font-semibold shadow-md shadow-blue-500/20"
                        disabled={processing}
                    >
                        {processing ? 'Mengirim Tautan...' : 'Kirim Tautan Reset Sandi'}
                    </PrimaryButton>
                </div>

                <div className="pt-4 border-t border-gray-100 dark:border-gray-800 text-center text-xs text-gray-500 dark:text-gray-400">
                    Ingat kata sandi Anda?{' '}
                    <Link
                        href={route('login')}
                        className="font-semibold text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 hover:underline"
                    >
                        Masuk ke Akun
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}

