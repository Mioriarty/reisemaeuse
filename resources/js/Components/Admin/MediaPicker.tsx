import { useMemo, useState } from 'react';
import { Button } from '@/Components/Admin/Ui';
import type { ImageProps } from '@/types';

type Props = {
    library: ImageProps[];
    selected: number | null;
    onSelect: (id: number | null) => void;
    label?: string;
};

/** Picks a single picture out of the library. */
export default function MediaPicker({ library, selected, onSelect, label = 'Bild' }: Props) {
    const [open, setOpen] = useState(false);
    const current = useMemo(() => library.find((m) => m.id === selected) ?? null, [library, selected]);

    return (
        <div>
            <span className="label-xs block text-graphite">{label}</span>

            <div className="mt-2 flex items-start gap-3">
                <div className="h-24 w-32 shrink-0 border border-hairline bg-paper-deep">
                    {current && (
                        <img src={current.src} alt="" className="h-full w-full object-cover" />
                    )}
                </div>
                <div className="flex flex-col gap-2">
                    <Button variant="ghost" onClick={() => setOpen((o) => !o)}>
                        {open ? 'Schließen' : current ? 'Anderes wählen' : 'Bild wählen'}
                    </Button>
                    {current && (
                        <Button variant="ghost" onClick={() => onSelect(null)}>
                            Entfernen
                        </Button>
                    )}
                </div>
            </div>

            {open && (
                <div className="mt-3 grid max-h-72 grid-cols-4 gap-2 overflow-y-auto border border-hairline p-2 lg:grid-cols-6">
                    {library.length === 0 && (
                        <p className="col-span-full p-4 text-sm text-graphite">
                            Noch keine Bilder. Lade zuerst welche unter „Bilder“ hoch.
                        </p>
                    )}
                    {library.map((image) => (
                        <button
                            key={image.id}
                            type="button"
                            onClick={() => {
                                onSelect(image.id);
                                setOpen(false);
                            }}
                            className={`aspect-square overflow-hidden border-2 ${
                                image.id === selected ? 'border-accent' : 'border-transparent hover:border-ink'
                            }`}
                        >
                            <img src={image.src} alt={image.alt} className="h-full w-full object-cover" />
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}

/** Picks an ordered set of pictures, for the gallery pattern. */
export function MultiMediaPicker({
    library,
    selected,
    onChange,
}: {
    library: ImageProps[];
    selected: number[];
    onChange: (ids: number[]) => void;
}) {
    const [open, setOpen] = useState(false);

    const toggle = (id: number) => {
        onChange(selected.includes(id) ? selected.filter((x) => x !== id) : [...selected, id]);
    };

    const move = (index: number, delta: number) => {
        const next = [...selected];
        const target = index + delta;
        if (target < 0 || target >= next.length) return;
        [next[index], next[target]] = [next[target], next[index]];
        onChange(next);
    };

    return (
        <div>
            <div className="flex items-center justify-between">
                <span className="label-xs text-graphite">Bilder ({selected.length})</span>
                <Button variant="ghost" onClick={() => setOpen((o) => !o)}>
                    {open ? 'Schließen' : 'Auswählen'}
                </Button>
            </div>

            {selected.length > 0 && (
                <ul className="mt-3 flex flex-wrap gap-2">
                    {selected.map((id, index) => {
                        const image = library.find((m) => m.id === id);
                        return (
                            <li key={id} className="w-24 border border-hairline">
                                <div className="h-20 bg-paper-deep">
                                    {image && <img src={image.src} alt="" className="h-full w-full object-cover" />}
                                </div>
                                <div className="flex">
                                    <button type="button" onClick={() => move(index, -1)} aria-label="Nach vorn" className="flex-1 py-1 text-xs hover:bg-ink hover:text-paper">
                                        ←
                                    </button>
                                    <button type="button" onClick={() => toggle(id)} aria-label="Entfernen" className="flex-1 py-1 text-xs hover:bg-accent hover:text-paper">
                                        ✕
                                    </button>
                                    <button type="button" onClick={() => move(index, 1)} aria-label="Nach hinten" className="flex-1 py-1 text-xs hover:bg-ink hover:text-paper">
                                        →
                                    </button>
                                </div>
                            </li>
                        );
                    })}
                </ul>
            )}

            {open && (
                <div className="mt-3 grid max-h-72 grid-cols-4 gap-2 overflow-y-auto border border-hairline p-2 lg:grid-cols-6">
                    {library.map((image) => (
                        <button
                            key={image.id}
                            type="button"
                            onClick={() => toggle(image.id)}
                            className={`aspect-square overflow-hidden border-2 ${
                                selected.includes(image.id) ? 'border-accent' : 'border-transparent hover:border-ink'
                            }`}
                        >
                            <img src={image.src} alt={image.alt} className="h-full w-full object-cover" />
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
