import { Moon, Sun } from 'lucide-react';
import { useEffect, useState } from 'react';

export default function ThemeToggle({ className = '' }) {
    const [isDark, setIsDark] = useState(false);

    useEffect(() => {
        // Cek status dark mode saat pertama kali mount
        const isCurrentDark = document.documentElement.classList.contains('dark');
        setIsDark(isCurrentDark);
    }, []);

    const toggleTheme = () => {
        const nextDark = !isDark;
        setIsDark(nextDark);

        if (nextDark) {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        }
    };

    return (
        <button
            type="button"
            onClick={toggleTheme}
            aria-label={isDark ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'}
            title={isDark ? 'Mode Terang' : 'Mode Gelap'}
            className={`relative inline-flex items-center justify-center p-2 rounded-xl text-gray-500 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-yellow-300 dark:hover:bg-gray-800/80 transition-all duration-200 focus:outline-hidden ${className}`}
        >
            {isDark ? (
                <Sun className="w-4 h-4 sm:w-5 sm:h-5 transition-transform duration-300 rotate-0 hover:rotate-90 text-yellow-400" />
            ) : (
                <Moon className="w-4 h-4 sm:w-5 sm:h-5 transition-transform duration-300 -rotate-12 hover:rotate-0 text-gray-600" />
            )}
        </button>
    );
}
