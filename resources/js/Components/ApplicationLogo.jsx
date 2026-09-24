export default function ApplicationLogo({ className = 'h-10 w-auto', ...props }) {
    return (
        <img
            src="/images/academic-hub-logo-mark.png"
            alt="Academic Hub Logo"
            className={`rounded-xl object-contain shadow-sm ${className}`}
            {...props}
        />
    );
}
