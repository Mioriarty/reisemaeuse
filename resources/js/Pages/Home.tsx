import { Head, Link, router } from '@inertiajs/react';
import Frame from '@/Components/Frame';
import NewsletterForm from '@/Components/NewsletterForm';
import PostRail from '@/Components/PostRail';
import RouteMap from '@/Components/RouteMap';
import PublicLayout from '@/Layouts/PublicLayout';
import type { PostCard as PostCardType, StopProps } from '@/types';

type Props = {
    posts: PostCardType[];
    stops: StopProps[];
    intro: { kilometres: number | null; countries: number; stopCount: number };
};

/*
 * Damit der Titel auf jedem Foto lesbar bleibt, liegt ein Verlauf darueber:
 * oben dunkel fuer Titel und Unterzeile, unten dunkel fuer den Hinweis nach
 * unten, in der Mitte fast nichts, damit das Bild Bild bleibt.
 */
const SCRIM =
    'linear-gradient(to bottom, rgba(0,0,0,0.55) 0%, rgba(0,0,0,0.22) 38%,' +
    ' rgba(0,0,0,0.14) 62%, rgba(0,0,0,0.52) 100%)';

export default function Home({ posts, stops, intro }: Props) {
    // Das Titelbild ist das Aufmacherfoto des juengsten Eintrags, der eines hat.
    const cover = posts.find((post) => post.cover)?.cover ?? null;

    return (
        <PublicLayout>
            <Head title="" />

            <section
                className="relative isolate flex h-[calc(100svh-3.5rem)] flex-col overflow-hidden sm:h-[calc(100svh-4.5rem)]"
                aria-label="Wandermäuse"
            >
                {cover ? (
                    <Frame image={cover} fill priority sizes="100vw" />
                ) : (
                    <div className="absolute inset-0 bg-paper-deep" />
                )}
                <div aria-hidden className="absolute inset-0" style={{ backgroundImage: SCRIM }} />

                <div className="relative flex h-full flex-col px-5 pt-[22vh] pb-8 sm:px-8 sm:pt-[26vh] sm:pb-10">
                    <h1 className="max-w-[14ch] font-display text-[clamp(3rem,12vw,9rem)] leading-[0.92] text-white">
                        Wandermäuse
                    </h1>
                    <p className="label-xs mt-5 text-white/85 sm:mt-6">
                        Ein Reisetagebuch aus Südamerika
                    </p>

                    <a
                        href="#eintraege"
                        className="label-xs mt-auto inline-flex min-h-11 items-center gap-3 self-start text-white/85 transition-colors hover:text-white"
                    >
                        Einträge
                        <span aria-hidden className="text-base leading-none">
                            ↓
                        </span>
                    </a>
                </div>
            </section>

            <div className="mt-20 sm:mt-28">
                {posts.length === 0 ? (
                    <p className="mx-auto max-w-[100rem] px-5 text-graphite sm:px-8">
                        Noch nichts geschrieben. Bald mehr.
                    </p>
                ) : (
                    <PostRail posts={posts} />
                )}
            </div>

            {stops.length > 0 && (
                <section className="mx-auto mt-24 max-w-[100rem] px-5 sm:mt-32 sm:px-8" aria-label="Die Route">
                    {/* Die Karte ist schmal und hoch. Neben dem Text statt darunter
                        steht sie auf breiten Schirmen nicht allein in der Mitte. */}
                    <div className="grid gap-10 lg:grid-cols-12 lg:items-center lg:gap-16">
                        <div className="lg:col-span-5">
                            <h2 className="font-display text-[clamp(2rem,5vw,3.5rem)] leading-none">
                                Die Route
                            </h2>
                            <p className="label-xs mt-4 flex flex-wrap gap-x-5 gap-y-1 text-graphite">
                                <span>{intro.stopCount} Stationen</span>
                                <span>{intro.countries} Länder</span>
                                <span>{posts.length} Einträge</span>
                            </p>
                            <p className="mt-6 max-w-sm text-lg leading-relaxed text-graphite">
                                Von Norden nach Süden, Station für Station. Tippe einen Punkt an, um
                                nachzulesen, was dort passiert ist.
                            </p>
                            <Link
                                href="/reise"
                                className="label-xs link-underline mt-8 inline-flex min-h-11 items-center"
                            >
                                Alle Stationen
                            </Link>
                        </div>

                        <div className="lg:col-span-7">
                            <RouteMap
                                stops={stops}
                                onSelect={(stop) => router.visit(`/reise#${stop.slug}`)}
                            />
                        </div>
                    </div>
                </section>
            )}

            <section className="mx-auto mt-24 max-w-2xl px-5 sm:mt-32 sm:px-8">
                <NewsletterForm />
            </section>
        </PublicLayout>
    );
}
