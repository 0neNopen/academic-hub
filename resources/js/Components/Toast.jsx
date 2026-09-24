import { usePage } from '@inertiajs/react';
import { AlertCircle, CheckCircle2, Info, X } from 'lucide-react';
import { useEffect, useState } from 'react';

export default function Toast() {
    const { flash } = usePage().props;
    const [toasts, setToasts] = useState([]);

    useEffect(() => {
        if (!flash) return;

        const newToasts = [];
        const timestamp = Date.now();

        if (flash.success) {
            newToasts.push({
                id: `success-${timestamp}`,
                type: 'success',
                message: flash.success,
            });
        }
        if (flash.error) {
            newToasts.push({
                id: `error-${timestamp}`,
                type: 'error',
                message: flash.error,
            });
        }
        if (flash.info) {
            newToasts.push({
                id: `info-${timestamp}`,
                type: 'info',
                message: flash.info,
            });
        }

        if (newToasts.length > 0) {
            setToasts((prev) => [...prev, ...newToasts]);
        }
    }, [flash]);

    const removeToast = (id) => {
        setToasts((prev) => prev.filter((t) => t.id !== id));
    };

    if (toasts.length === 0) return null;

    return (
        <div
            aria-live="polite"
            className="fixed bottom-5 right-5 z-50 flex flex-col gap-2.5 max-w-sm w-full px-4 sm:px-0 pointer-events-none"
        >
            {toasts.map((toast) => (
                <ToastItem key={toast.id} toast={toast} onDismiss={() => removeToast(toast.id)} />
            ))}
        </div>
    );
}

function ToastItem({ toast, onDismiss }) {
    useEffect(() => {
        const timer = setTimeout(() => {
            onDismiss();
        }, 4000);

        return () => clearTimeout(timer);
    }, [onDismiss]);

    const styles = {
        success: {
            bg: 'bg-emerald-50 dark:bg-emerald-950/90 border-emerald-200 dark:border-emerald-800 text-emerald-900 dark:text-emerald-100 shadow-xl',
            icon: <CheckCircle2 className="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" />,
            bar: 'bg-emerald-500 dark:bg-emerald-400',
            title: 'Berhasil',
        },
        error: {
            bg: 'bg-rose-50 dark:bg-rose-950/90 border-rose-200 dark:border-rose-800 text-rose-900 dark:text-rose-100 shadow-xl',
            icon: <AlertCircle className="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5" />,
            bar: 'bg-rose-500 dark:bg-rose-400',
            title: 'Terjadi Kesalahan',
        },
        info: {
            bg: 'bg-blue-50 dark:bg-blue-950/90 border-blue-200 dark:border-blue-800 text-blue-900 dark:text-blue-100 shadow-xl',
            icon: <Info className="w-5 h-5 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" />,
            bar: 'bg-blue-500 dark:bg-blue-400',
            title: 'Informasi',
        },
    };

    const currentStyle = styles[toast.type] || styles.info;

    return (
        <div
            className={`pointer-events-auto flex flex-col overflow-hidden rounded-xl border shadow-lg transition-all duration-300 animate-in fade-in slide-in-from-bottom-3 ${currentStyle.bg}`}
            role="alert"
        >
            <div className="flex items-start gap-3 p-3.5">
                {currentStyle.icon}
                <div className="flex-1 min-w-0 pr-1">
                    <p className="text-xs font-semibold tracking-wide uppercase opacity-75">
                        {currentStyle.title}
                    </p>
                    <p className="text-sm font-medium mt-0.5 leading-snug">
                        {toast.message}
                    </p>
                </div>
                <button
                    onClick={onDismiss}
                    type="button"
                    aria-label="Tutup notifikasi"
                    className="shrink-0 rounded-lg p-1 text-gray-400 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-black/5 dark:hover:bg-white/10 transition"
                >
                    <X className="w-4 h-4" />
                </button>
            </div>
            {/* Indikator Durasi */}
            <div className="w-full bg-black/5 h-1">
                <div
                    className={`h-full ${currentStyle.bar} animate-[shrink_4s_linear_forwards]`}
                    style={{
                        animation: 'shrink 4s linear forwards',
                    }}
                />
            </div>
        </div>
    );
}
