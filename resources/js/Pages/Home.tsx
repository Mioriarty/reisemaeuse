import { Head, Link, router } from '@inertiajs/react';
import NewsletterForm from '@/Components/NewsletterForm';
import PostCard from '@/Components/PostCard';
import RouteMap from '@/Components/RouteMap';
import PublicLayout from '@/Layouts/PublicLayout';
import type { PostCard as PostCardType, StopProps } from '@/types';

type Props = {
    posts: PostCardType[];
    stops: StopProps[];
    intro: { kilometres: number | null; countries: number; stopCount: number };
};

export default function Home({ posts, stops, intro }: Props) {
    return (
        <PublicLayout>
            <Head title="" />

            <section className="mx-auto max-w-6xl px-5 pt-12 pb-16 sm:px-8 sm:pt-20 sm:pb-24">
                <p className="label-xs text-graphite">Südamerika</p>
                <h1 className="mt-4 max-w-4xl font-display text-[clamp(2.5rem,9vw,5.5rem)] leading-[0.95] font-bold tracking-tight text-balance">
                    Ein Reisetagebuch mit Bildern, Karte und Musik.
                </h1>
                <p className="mt-6 max-w-xl text-lg leading-relaxed text-graphite">
                    Wir schreiben auf, wo wir waren – und zu jedem Eintrag gibt es ein kleines Stück Musik,
                    das dort entstanden ist.
                </p>

                <dl className="hairline-t mt-10 grid grid-cols-2 gap-px bg-hairline sm:grid-cols-3">
                    <Stat label="Stationen" value={String(intro.stopCount)} />
                    <Stat label="Länder" value={String(intro.countries)} />
                    <Stat label="Einträge" value={String(posts.length)} />
                </dl>
            </section>

            {stops.length > 0 && (
                <section className="mx-auto max-w-6xl px-5 sm:px-8" aria-label="Die Route">
                    <div className="hairline-t flex items-baseline justify-between py-4">
                        <h2 className="label-xs text-graphite">Die Route</h2>
                        <Link href="/reise" className="label-xs hover:text-accent">
                            Alle Stationen ↗
                        </Link>
                    </div>
                    <RouteMap
                        stops={stops}
                        onSelect={(stop) => router.visit(`/reise#${stop.slug}`)}
                    />
                </section>
            )}

            <section className="mx-auto mt-20 max-w-6xl px-5 sm:px-8" aria-label="Neueste Einträge">
                <h2 className="label-xs pb-4 text-graphite">Neueste Einträge</h2>
                {posts.length === 0 ? (
                    <p className="hairline-t py-10 text-graphite">Noch nichts geschrieben. Bald mehr.</p>
                ) : (
                    posts.map((post, i) => <PostCard key={post.id} post={post} index={i} />)
                )}
                {posts.length > 0 && (
                    <div className="hairline-t py-6">
                        <Link href="/blog" className="label-xs hover:text-accent">
                            Alle Einträge ↗
                        </Link>
                    </div>
                )}
            </section>

            <section className="mx-auto mt-20 max-w-2xl px-5 sm:px-8">
                <NewsletterForm />
            </section>
        </PublicLayout>
    );
}

function Stat({ label, value }: { label: string; value: string }) {
    return (
        <div className="bg-paper px-4 py-6">
            <dt className="label-xs text-graphite">{label}</dt>
            <dd className="mt-2 font-display text-3xl font-bold tabular-nums sm:text-4xl">{value}</dd>
        </div>
    );
}
