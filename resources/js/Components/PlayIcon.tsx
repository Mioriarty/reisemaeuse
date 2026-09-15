/** Blocky by construction: a triangle and two bars, no rounded caps. */
export function PlayIcon({ size = 18 }: { size?: number }) {
    return (
        <svg width={size} height={size} viewBox="0 0 16 16" aria-hidden focusable="false">
            <path d="M3 1.5 14 8 3 14.5Z" fill="currentColor" />
        </svg>
    );
}

export function PauseIcon({ size = 18 }: { size?: number }) {
    return (
        <svg width={size} height={size} viewBox="0 0 16 16" aria-hidden focusable="false">
            <path d="M3 2h3.5v12H3zM9.5 2H13v12H9.5z" fill="currentColor" />
        </svg>
    );
}
