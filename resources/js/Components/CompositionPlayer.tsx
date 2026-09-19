import { useAudio } from '@/lib/audio';
import { formatDuration } from '@/lib/format';
import { PauseIcon, PlayIcon } from '@/Components/PlayIcon';
import type { CompositionProps } from '@/types';

/**
 * The piece that belongs to an entry, sitting between the map and the text.
 */
export default function CompositionPlayer({ composition }: { composition: CompositionProps }) {
    const { current, playing, position, duration, toggle, seek } = useAudio();

    const isActive = current?.id === composition.id;
    const total = (isActive && duration) || composition.durationSeconds || 0;
    const elapsed = isActive ? position : 0;
    const progress = total > 0 ? Math.min(100, (elapsed / total) * 100) : 0;

    return (
        <section aria-label="Komposition zu diesem Eintrag" className="mx-auto max-w-5xl px-5 sm:px-8">
            <div className="border border-ink bg-paper">
                <div className="flex items-stretch">
                    <button
                        type="button"
                        onClick={() => toggle(composition)}
                        disabled={!composition.audioUrl}
                        aria-label={isActive && playing ? 'Pause' : `${composition.title} abspielen`}
                        className="flex w-16 shrink-0 items-center justify-center border-r border-ink bg-ink text-paper transition-colors hover:bg-accent disabled:cursor-not-allowed disabled:opacity-40 sm:w-20"
                    >
                        {isActive && playing ? <PauseIcon size={20} /> : <PlayIcon size={20} />}
                    </button>

                    <div className="min-w-0 flex-1 px-4 py-4 sm:px-6 sm:py-5">
                        <p className="label-xs text-graphite">Komposition</p>
                        <h2 className="mt-1 font-display text-2xl font-medium leading-[1.15] text-balance sm:text-[1.75rem]">
                            {composition.title}
                        </h2>
                        {composition.description && (
                            <p className="mt-2 max-w-prose text-sm leading-relaxed text-graphite">
                                {composition.description}
                            </p>
                        )}
                    </div>
                </div>

                <div className="hairline-t flex items-center gap-3 px-4 py-3 sm:px-6">
                    <span className="text-xs tabular-nums text-graphite">
                        {formatDuration(elapsed)}
                    </span>
                    <input
                        type="range"
                        min={0}
                        max={total || 1}
                        step={0.5}
                        value={elapsed}
                        disabled={!isActive}
                        onChange={(e) => seek(Number(e.target.value))}
                        aria-label="Position in der Komposition"
                        className="seek h-8 flex-1 cursor-pointer disabled:cursor-default"
                        style={{
                            backgroundImage: `linear-gradient(to right, var(--color-ink) ${progress}%, var(--color-hairline) ${progress}%)`,
                            backgroundSize: '100% 2px',
                            backgroundPosition: 'center',
                            backgroundRepeat: 'no-repeat',
                        }}
                    />
                    <span className="text-xs tabular-nums text-graphite">
                        {formatDuration(total)}
                    </span>
                </div>

                {composition.scoreUrl && (
                    <div className="hairline-t">
                        <a
                            href={composition.scoreUrl}
                            target="_blank"
                            rel="noopener"
                            className="label-xs flex items-center justify-between px-4 py-4 transition-colors hover:bg-ink hover:text-paper sm:px-6"
                        >
                            <span>Noten ansehen{composition.scoreIsPdf ? ' (PDF)' : ''}</span>
                            <span aria-hidden>↗</span>
                        </a>
                    </div>
                )}
            </div>

            {composition.scoreUrl && !composition.scoreIsPdf && (
                <figure className="m-0 mt-4 border border-hairline bg-white p-3 sm:p-5">
                    <img
                        src={composition.scoreUrl}
                        alt={`Notenbild zu „${composition.title}“`}
                        loading="lazy"
                        className="block w-full"
                    />
                </figure>
            )}
        </section>
    );
}
