import { useAudio } from '@/lib/audio';
import { formatDuration } from '@/lib/format';
import { PauseIcon, PlayIcon } from '@/Components/PlayIcon';

/**
 * The bar that keeps the current piece playing while you read on.
 *
 * It only appears once something is actually playing, so it never eats screen
 * space on a phone for nothing.
 */
export default function MiniPlayer() {
    const { current, playing, position, duration, toggle, stop } = useAudio();

    if (!current) return null;

    const total = duration || current.durationSeconds || 0;
    const progress = total > 0 ? Math.min(100, (position / total) * 100) : 0;

    return (
        <div className="sticky bottom-0 z-40 border-t border-ink bg-paper">
            <div
                aria-hidden
                className="h-0.5 bg-accent transition-[width] duration-200"
                style={{ width: `${progress}%` }}
            />
            <div className="mx-auto flex max-w-6xl items-center gap-3 px-3 py-2 sm:px-8">
                <button
                    type="button"
                    onClick={() => toggle(current)}
                    aria-label={playing ? 'Pause' : 'Weiter abspielen'}
                    className="flex h-11 w-11 shrink-0 items-center justify-center bg-ink text-paper transition-colors hover:bg-accent"
                >
                    {playing ? <PauseIcon /> : <PlayIcon />}
                </button>
                <div className="min-w-0 flex-1">
                    <p className="label-xs truncate text-graphite">Läuft gerade</p>
                    <p className="truncate font-display text-sm font-medium">{current.title}</p>
                </div>
                <span className="hidden font-display text-xs tabular-nums text-graphite sm:inline">
                    {formatDuration(position)} / {formatDuration(total)}
                </span>
                <button
                    type="button"
                    onClick={stop}
                    aria-label="Wiedergabe beenden"
                    className="flex h-11 w-11 shrink-0 items-center justify-center text-graphite transition-colors hover:text-ink"
                >
                    ✕
                </button>
            </div>
        </div>
    );
}
