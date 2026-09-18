import { useForm } from '@inertiajs/react';
import { formatShortDate } from '@/lib/format';
import type { CommentProps } from '@/types';

type Props = {
    postSlug: string;
    comments: CommentProps[];
};

export default function CommentSection({ postSlug, comments }: Props) {
    const { data, setData, post, processing, errors, reset, wasSuccessful } = useForm({
        author_name: '',
        author_email: '',
        body: '',
        // Honeypot: a real person never fills this in, bots fill in everything.
        website: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/blog/${postSlug}/kommentare`, {
            preserveScroll: true,
            onSuccess: () => reset('body', 'website'),
        });
    };

    return (
        <section aria-label="Kommentare" className="mx-auto max-w-5xl px-5 sm:px-8">
            <h2 className="label-xs hairline-b pb-3 text-graphite">
                {comments.length === 0
                    ? 'Kommentare'
                    : `${comments.length} ${comments.length === 1 ? 'Kommentar' : 'Kommentare'}`}
            </h2>

            {comments.length > 0 && (
                <ul className="mt-8 flex flex-col gap-8">
                    {comments.map((comment) => (
                        <li key={comment.id} className="grid gap-2 sm:grid-cols-12 sm:gap-6">
                            <div className="sm:col-span-3">
                                <p className="text-sm font-semibold">{comment.authorName}</p>
                                <p className="mt-0.5 text-xs text-graphite">
                                    {formatShortDate(comment.createdAt)}
                                </p>
                            </div>
                            <p className="text-[1.0625rem] leading-relaxed whitespace-pre-line text-ink-soft sm:col-span-9">
                                {comment.body}
                            </p>
                        </li>
                    ))}
                </ul>
            )}

            <form onSubmit={submit} className="mt-12 border border-ink">
                <div className="hairline-b px-5 py-4 sm:px-6">
                    <h3 className="font-display text-2xl font-medium leading-none">Schreib uns etwas</h3>
                    <p className="mt-1 text-sm text-graphite">
                        Dein Kommentar erscheint sofort. Die E-Mail-Adresse ist freiwillig und wird nie
                        veröffentlicht.
                    </p>
                </div>

                <div className="grid gap-px bg-hairline sm:grid-cols-2">
                    <Field
                        label="Name"
                        value={data.author_name}
                        onChange={(v) => setData('author_name', v)}
                        error={errors.author_name}
                        required
                        autoComplete="name"
                    />
                    <Field
                        label="E-Mail (optional)"
                        type="email"
                        value={data.author_email}
                        onChange={(v) => setData('author_email', v)}
                        error={errors.author_email}
                        autoComplete="email"
                    />
                </div>

                <div className="hairline-t bg-paper px-5 py-4 sm:px-6">
                    <label className="label-xs block text-graphite" htmlFor="comment-body">
                        Kommentar
                    </label>
                    <textarea
                        id="comment-body"
                        rows={5}
                        required
                        value={data.body}
                        onChange={(e) => setData('body', e.target.value)}
                        className="mt-2 w-full resize-y border border-hairline bg-paper px-3 py-2 text-base focus:border-ink focus:outline-none"
                    />
                    {errors.body && <p className="mt-2 text-sm text-accent">{errors.body}</p>}
                </div>

                {/* Hidden from people, visible to bots. */}
                <div aria-hidden className="absolute left-[-9999px] h-0 w-0 overflow-hidden">
                    <label htmlFor="website">Website</label>
                    <input
                        id="website"
                        type="text"
                        tabIndex={-1}
                        autoComplete="off"
                        value={data.website}
                        onChange={(e) => setData('website', e.target.value)}
                    />
                </div>

                <div className="hairline-t flex items-center justify-between gap-4 px-5 py-4 sm:px-6">
                    {wasSuccessful ? (
                        <p role="status" className="text-sm text-graphite">
                            Danke, dein Kommentar steht oben.
                        </p>
                    ) : (
                        <span />
                    )}
                    <button
                        type="submit"
                        disabled={processing}
                        className="label-xs min-h-11 bg-ink px-6 text-paper transition-colors hover:bg-accent disabled:opacity-40"
                    >
                        {processing ? 'Wird gesendet …' : 'Abschicken'}
                    </button>
                </div>
            </form>
        </section>
    );
}

function Field({
    label,
    value,
    onChange,
    error,
    type = 'text',
    required = false,
    autoComplete,
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
    type?: string;
    required?: boolean;
    autoComplete?: string;
}) {
    const id = `field-${label.replace(/\W+/g, '-').toLowerCase()}`;

    return (
        <div className="bg-paper px-5 py-4 sm:px-6">
            <label className="label-xs block text-graphite" htmlFor={id}>
                {label}
            </label>
            <input
                id={id}
                type={type}
                required={required}
                autoComplete={autoComplete}
                value={value}
                onChange={(e) => onChange(e.target.value)}
                className="mt-2 w-full border border-hairline bg-paper px-3 py-2 text-base focus:border-ink focus:outline-none"
            />
            {error && <p className="mt-2 text-sm text-accent">{error}</p>}
        </div>
    );
}
