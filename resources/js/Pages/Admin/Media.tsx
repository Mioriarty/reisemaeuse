import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Button, Panel, inputClass } from '@/Components/Admin/Ui';
import type { ImageProps } from '@/types';

type Row = ImageProps & { originalName: string | null; createdAt: string | null };
type Paginated = { data: Row[]; links: { url: string | null; label: string; active: boolean }[] };

export default function Media({ media }: { media: Paginated }) {
    const [editing, setEditing] = useState<Row | null>(null);

    const upload = useForm<{ files: File[] }>({ files: [] });

    const submit = (files: FileList | null) => {
        if (!files || files.length === 0) return;
        upload.setData('files', Array.from(files));
        upload.post('/admin/bilder', {
            forceFormData: true,
            onSuccess: () => upload.reset(),
        });
    };

    return (
        <AdminLayout title="Bilder">
            <Head title="Bilder" />

            <Panel title="Hochladen">
                <label
                    onDragOver={(e) => e.preventDefault()}
                    onDrop={(e) => {
                        e.preventDefault();
                        submit(e.dataTransfer.files);
                    }}
                    className="flex cursor-pointer flex-col items-center justify-center border border-dashed border-hairline px-6 py-10 text-center hover:border-ink"
                >
                    <span className="label-xs text-graphite">
                        Bilder hierher ziehen oder klicken – bis zu 20 auf einmal
                    </span>
                    <span className="mt-2 text-xs text-graphite">
                        Jedes Bild wird automatisch in vier Größen als WebP und JPEG abgelegt.
                    </span>
                    <input
                        type="file"
                        multiple
                        accept="image/*"
                        className="hidden"
                        onChange={(e) => submit(e.target.files)}
                    />
                </label>

                {upload.progress && (
                    <div className="mt-4 h-1 w-full bg-hairline">
                        <div className="h-full bg-ink" style={{ width: `${upload.progress.percentage ?? 0}%` }} />
                    </div>
                )}
                {upload.errors.files && <p className="mt-3 text-sm text-accent">{upload.errors.files}</p>}
            </Panel>

            <div className="mt-6 grid grid-cols-2 gap-4 md:grid-cols-4 xl:grid-cols-6">
                {media.data.map((image) => (
                    <figure key={image.id} className="m-0 border border-hairline bg-paper">
                        <div className="aspect-square" style={{ backgroundColor: image.dominantColor }}>
                            <img src={image.src} alt={image.alt} className="h-full w-full object-cover" />
                        </div>
                        <figcaption className="p-2">
                            <p className="truncate text-xs text-graphite" title={image.originalName ?? ''}>
                                {image.originalName ?? `#${image.id}`}
                            </p>
                            <p className="mt-0.5 text-xs text-graphite tabular-nums">
                                {image.width}×{image.height}
                            </p>
                            <div className="mt-2 flex gap-1">
                                <button
                                    type="button"
                                    onClick={() => setEditing(image)}
                                    className="label-xs flex-1 border border-hairline py-1 hover:bg-ink hover:text-paper"
                                >
                                    Text
                                </button>
                                <button
                                    type="button"
                                    onClick={() => {
                                        if (window.confirm('Bild wirklich löschen? Es verschwindet dann auch aus allen Einträgen.')) {
                                            router.delete(`/admin/bilder/${image.id}`, { preserveScroll: true });
                                        }
                                    }}
                                    className="label-xs flex-1 border border-accent py-1 text-accent hover:bg-accent hover:text-paper"
                                >
                                    Löschen
                                </button>
                            </div>
                        </figcaption>
                    </figure>
                ))}
            </div>

            {media.data.length === 0 && (
                <p className="mt-6 border border-hairline bg-paper px-5 py-8 text-center text-graphite">
                    Noch keine Bilder hochgeladen.
                </p>
            )}

            <Pagination links={media.links} />

            {editing && <AltDialog image={editing} onClose={() => setEditing(null)} />}
        </AdminLayout>
    );
}

function AltDialog({ image, onClose }: { image: Row; onClose: () => void }) {
    const form = useForm({ alt: image.alt ?? '', caption: image.caption ?? '' });

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-ink/60 p-6">
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    form.put(`/admin/bilder/${image.id}`, { preserveScroll: true, onSuccess: onClose });
                }}
                className="w-full max-w-md border border-ink bg-paper"
            >
                <h2 className="label-xs hairline-b px-5 py-3 text-graphite">Bildtexte</h2>
                <div className="flex flex-col gap-4 p-5">
                    <label className="block">
                        <span className="label-xs block text-graphite">Alternativtext</span>
                        <span className="mt-1 block text-xs text-graphite">
                            Beschreibt das Bild für Menschen, die es nicht sehen können.
                        </span>
                        <input
                            className={`${inputClass} mt-2`}
                            value={form.data.alt}
                            onChange={(e) => form.setData('alt', e.target.value)}
                            autoFocus
                        />
                    </label>
                    <label className="block">
                        <span className="label-xs block text-graphite">Standard-Bildunterschrift</span>
                        <input
                            className={`${inputClass} mt-2`}
                            value={form.data.caption}
                            onChange={(e) => form.setData('caption', e.target.value)}
                        />
                    </label>
                </div>
                <div className="hairline-t flex justify-end gap-3 px-5 py-3">
                    <Button variant="ghost" onClick={onClose}>
                        Abbrechen
                    </Button>
                    <Button type="submit" disabled={form.processing}>
                        Speichern
                    </Button>
                </div>
            </form>
        </div>
    );
}

function Pagination({ links }: { links: { url: string | null; label: string; active: boolean }[] }) {
    if (links.length <= 3) return null;

    return (
        <nav className="mt-6 flex flex-wrap gap-1" aria-label="Seiten">
            {links.map((link, i) => (
                <button
                    key={i}
                    type="button"
                    disabled={!link.url}
                    onClick={() => link.url && router.visit(link.url)}
                    className={`label-xs min-h-9 border px-3 ${
                        link.active ? 'border-ink bg-ink text-paper' : 'border-hairline bg-paper text-graphite'
                    } disabled:opacity-30`}
                    dangerouslySetInnerHTML={{ __html: link.label }}
                />
            ))}
        </nav>
    );
}
