import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('password.confirm'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Konfirmasi Keamanan" />

            <div className="mb-6">
                <h2 className="text-lg font-bold text-gray-900">
                    Konfirmasi Keamanan
                </h2>
                <p className="text-xs text-gray-500 mt-1 leading-relaxed">
                    Ini adalah area aman aplikasi. Demi melindungi akun Anda, silakan masukkan kata sandi sebelum melanjutkan.
                </p>
            </div>

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <InputLabel
                        htmlFor="password"
                        value="Kata Sandi Akun"
                        className="text-xs font-semibold text-gray-700"
                    />

                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        placeholder="••••••••"
                        className="mt-1 block w-full text-sm rounded-xl"
                        isFocused={true}
                        onChange={(e) => setData('password', e.target.value)}
                        required
                    />

                    <InputError message={errors.password} className="mt-1.5" />
                </div>

                <div className="pt-2">
                    <PrimaryButton
                        className="w-full justify-center py-2.5 rounded-xl text-xs font-semibold shadow-md shadow-blue-500/20"
                        disabled={processing}
                    >
                        {processing ? 'Memverifikasi...' : 'Konfirmasi Kata Sandi'}
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}

