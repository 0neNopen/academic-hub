import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';

export default function DeleteUserForm({ className = '' }) {
    const [confirmingUserDeletion, setConfirmingUserDeletion] = useState(false);
    const passwordInput = useRef();

    const {
        data,
        setData,
        delete: destroy,
        processing,
        reset,
        errors,
        clearErrors,
    } = useForm({
        password: '',
    });

    const confirmUserDeletion = () => {
        setConfirmingUserDeletion(true);
    };

    const deleteUser = (e) => {
        e.preventDefault();

        destroy(route('profile.destroy'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: () => passwordInput.current.focus(),
            onFinish: () => reset(),
        });
    };

    const closeModal = () => {
        setConfirmingUserDeletion(false);

        clearErrors();
        reset();
    };

    return (
        <section className={`space-y-4 ${className}`}>
            <header>
                <h2 className="text-base font-bold text-red-600 dark:text-red-400">
                    Hapus Akun
                </h2>

                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                    Setelah akun dihapus, seluruh data mata kuliah, materi, dan tugas Anda akan dihapus permanen.
                </p>
            </header>

            <DangerButton onClick={confirmUserDeletion} className="text-xs">
                Hapus Akun Saya
            </DangerButton>

            <Modal show={confirmingUserDeletion} onClose={closeModal}>
                <form onSubmit={deleteUser} className="p-6">
                    <h2 className="text-base font-bold text-gray-900 dark:text-white">
                        Apakah Anda yakin ingin menghapus akun?
                    </h2>

                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                        Setelah akun Anda dihapus, semua data akan hilang secara permanen. Masukkan kata sandi Anda untuk mengonfirmasi tindakan ini.
                    </p>

                    <div className="mt-4">
                        <InputLabel
                            htmlFor="delete_password"
                            value="Kata Sandi Anda"
                            className="sr-only"
                        />

                        <TextInput
                            id="delete_password"
                            type="password"
                            name="password"
                            ref={passwordInput}
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                            className="mt-1 block w-full text-sm"
                            isFocused
                            placeholder="Masukkan kata sandi..."
                        />

                        <InputError
                            message={errors.password}
                            className="mt-1.5"
                        />
                    </div>

                    <div className="mt-6 flex justify-end gap-2">
                        <SecondaryButton onClick={closeModal} className="text-xs">
                            Batal
                        </SecondaryButton>

                        <DangerButton disabled={processing} className="text-xs">
                            Ya, Hapus Permanen
                        </DangerButton>
                    </div>
                </form>
            </Modal>
        </section>
    );
}
