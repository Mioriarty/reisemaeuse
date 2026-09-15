import { Head } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';

type Props = { title: string; html: string };

export default function Legal({ title, html }: Props) {
    return (
        <PublicLayout>
            <Head title={title} />

            <div className="mx-auto max-w-3xl px-5 pt-12 pb-24 sm:px-8 sm:pt-20">
                <h1 className="font-display text-[clamp(2rem,6vw,3.5rem)] leading-[1] font-bold tracking-tight">
                    {title}
                </h1>
                <div
                    className="prose-column mt-10 max-w-none text-[1.0625rem] text-ink-soft"
                    dangerouslySetInnerHTML={{ __html: html }}
                />
            </div>
        </PublicLayout>
    );
}
