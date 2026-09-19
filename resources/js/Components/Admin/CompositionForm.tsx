import { router, useForm } from '@inertiajs/react';
import { useRef } from 'react';
import { Button, Field, Panel, inputClass } from '@/Components/Admin/Ui';
import type { CompositionProps } from '@/types';

type Props = {
    postId: number;
    composition: CompositionProps | null;
};

export default function CompositionForm({ postId, composition }: Props) {
    const audioRef = useRef<HTMLInputElement>(null);
    const scoreRef = useRef<HTMLInputElement>(null);

    const form = useForm<{
        title: string;
        description: string;
        duration_seconds: number;
        audio: File | null;
        score: File | null;
    }>({
        title: composition?.title ?? '',
        description: composition?.description ?? '',
        duration_seconds: composition?.durationSeconds ?? 0,
        audio: null,
        score: null,
    });

    /**
     * Reads the real length out of the file the moment it is picked, so the
     * player can show a duration before anyone presses play.
     */
    const onAudioPicked = (file: File | null) => {
        form.setData('audio', file);
        if (!file) return;

        const url = URL.createObjectURL(file);
        const probe = new Audio();
        probe.preload = 'metadata';
        probe.onloadedmetadata = () => {
            if (Number.isFinite(probe.duration)) {
                form.setData('duration_seconds', Math.round(probe.duration));
            }
            URL.revokeObjectURL(url);
        };
        probe.src = url;
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(`/admin/eintraege/${postId}/komposition`, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                form.setData({ ...form.data, audio: null, score: null });
                if (audioRef.current) audioRef.current.value = '';
                if (scoreRef.current) scoreRef.current.value = '';
            },
        });
    };

    return (
        <Panel title="Komposition">
            <form onSubmit={submit} className="flex flex-col gap-4">
                <Field label="Titel des Stücks" error={form.errors.title}>
                    <input
                        className={inputClass}
                        value={form.data.title}
                        onChange={(e) => form.setData('title', e.target.value)}
                        required
                    />
                </Field>

                <Field label="Ein paar Worte dazu" error={form.errors.description}>
                    <textarea
                        className={inputClass}
                        rows={3}
                        value={form.data.description}
                        onChange={(e) => form.setData('description', e.target.value)}
                    />
                </Field>

                <div className="grid gap-4 md:grid-cols-2">
                    <Field
                        label="Audiodatei"
                        error={form.errors.audio}
                        hint={composition?.audioUrl ? 'Eine neue Datei ersetzt die bisherige.' : 'MP3, M4A, OGG oder WAV.'}
                    >
                        <input
                            ref={audioRef}
                            type="file"
                            accept="audio/*"
                            className={inputClass}
                            onChange={(e) => onAudioPicked(e.target.files?.[0] ?? null)}
                        />
                    </Field>

                    <Field
                        label="Noten"
                        error={form.errors.score}
                        hint={composition?.scoreUrl ? 'Eine neue Datei ersetzt die bisherige.' : 'PDF, JPG oder PNG.'}
                    >
                        <input
                            ref={scoreRef}
                            type="file"
                            accept="application/pdf,image/jpeg,image/png"
                            className={inputClass}
                            onChange={(e) => form.setData('score', e.target.files?.[0] ?? null)}
                        />
                    </Field>
                </div>

                {composition && (
                    <div className="flex flex-wrap items-center gap-4 border border-hairline p-3">
                        {composition.audioUrl && (
                            <audio controls src={composition.audioUrl} className="h-9 max-w-full" />
                        )}
                        {composition.scoreUrl && (
                            <a
                                href={composition.scoreUrl}
                                target="_blank"
                                rel="noopener"
                                className="label-xs text-graphite hover:text-ink"
                            >
                                Noten ansehen ↗
                            </a>
                        )}
                    </div>
                )}

                {form.progress && (
                    <div className="h-1 w-full bg-hairline">
                        <div className="h-full bg-ink" style={{ width: `${form.progress.percentage ?? 0}%` }} />
                    </div>
                )}

                <div className="flex items-center justify-between gap-3">
                    {composition ? (
                        <Button
                            variant="danger"
                            onClick={() => {
                                if (window.confirm('Komposition wirklich entfernen?')) {
                                    router.delete(`/admin/eintraege/${postId}/komposition`, {
                                        preserveScroll: true,
                                    });
                                }
                            }}
                        >
                            Entfernen
                        </Button>
                    ) : (
                        <span />
                    )}
                    <Button type="submit" disabled={form.processing}>
                        {form.processing ? 'Lädt hoch …' : 'Komposition speichern'}
                    </Button>
                </div>
            </form>
        </Panel>
    );
}
