import {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useRef,
    useState,
    type ReactNode,
} from 'react';
import type { CompositionProps } from '@/types';

type AudioState = {
    current: CompositionProps | null;
    playing: boolean;
    position: number;
    duration: number;
    toggle: (composition: CompositionProps) => void;
    seek: (seconds: number) => void;
    stop: () => void;
};

const AudioContext = createContext<AudioState | null>(null);

/**
 * Holds the single <audio> element for the whole app.
 *
 * It lives above the Inertia page, so a piece keeps playing while the visitor
 * moves from one entry to the next - which is the point of having music
 * attached to a travel diary in the first place.
 */
export function AudioProvider({ children }: { children: ReactNode }) {
    const ref = useRef<HTMLAudioElement | null>(null);
    const [current, setCurrent] = useState<CompositionProps | null>(null);
    const [playing, setPlaying] = useState(false);
    const [position, setPosition] = useState(0);
    const [duration, setDuration] = useState(0);

    const toggle = useCallback(
        (composition: CompositionProps) => {
            const el = ref.current;
            if (!el || !composition.audioUrl) return;

            if (current?.id === composition.id) {
                if (el.paused) {
                    void el.play();
                } else {
                    el.pause();
                }
                return;
            }

            setCurrent(composition);
            setPosition(0);
            setDuration(composition.durationSeconds || 0);
            el.src = composition.audioUrl;
            void el.play();
        },
        [current],
    );

    const seek = useCallback((seconds: number) => {
        const el = ref.current;
        if (!el) return;
        el.currentTime = seconds;
        setPosition(seconds);
    }, []);

    const stop = useCallback(() => {
        const el = ref.current;
        if (el) {
            el.pause();
            el.removeAttribute('src');
            el.load();
        }
        setCurrent(null);
        setPlaying(false);
        setPosition(0);
    }, []);

    useEffect(() => {
        const el = ref.current;
        if (!el) return;

        const onPlay = () => setPlaying(true);
        const onPause = () => setPlaying(false);
        const onTime = () => setPosition(el.currentTime);
        const onMeta = () => setDuration(el.duration || 0);
        const onEnded = () => {
            setPlaying(false);
            setPosition(0);
        };

        el.addEventListener('play', onPlay);
        el.addEventListener('pause', onPause);
        el.addEventListener('timeupdate', onTime);
        el.addEventListener('loadedmetadata', onMeta);
        el.addEventListener('ended', onEnded);

        return () => {
            el.removeEventListener('play', onPlay);
            el.removeEventListener('pause', onPause);
            el.removeEventListener('timeupdate', onTime);
            el.removeEventListener('loadedmetadata', onMeta);
            el.removeEventListener('ended', onEnded);
        };
    }, []);

    const value = useMemo<AudioState>(
        () => ({ current, playing, position, duration, toggle, seek, stop }),
        [current, playing, position, duration, toggle, seek, stop],
    );

    return (
        <AudioContext.Provider value={value}>
            <audio ref={ref} preload="none" />
            {children}
        </AudioContext.Provider>
    );
}

export function useAudio(): AudioState {
    const ctx = useContext(AudioContext);
    if (!ctx) {
        throw new Error('useAudio must be used inside an AudioProvider');
    }
    return ctx;
}
