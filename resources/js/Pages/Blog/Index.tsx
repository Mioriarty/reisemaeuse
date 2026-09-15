import { Head } from '@inertiajs/react';
import PostCard from '@/Components/PostCard';
import PublicLayout from '@/Layouts/PublicLayout';
import type { PostCard as PostCardType } from '@/types';

type Group = { country: string; posts: PostCardType[] };

export default function BlogIndex({ groups, total }: { groups: Group[]; total: number }) {
    let running = -1;

    return (
        <PublicLayout>
            <Head title="Einträge" />

            <div className="mx-auto max-w-6xl px-5 pt-12 pb-16 sm:px-8 sm:pt-20">
                <p className="label-xs text-graphite">{total} Einträge</p>
                <h1 className="mt-4 font-display text-[clamp(2.25rem,7vw,4.5rem)] leading-[0.95] font-bold tracking-tight">
                    Alle Einträge
                </h1>
            </div>

            <div className="mx-auto max-w-6xl px-5 sm:px-8">
                {groups.length === 0 && (
                    <p className="hairline-t py-10 text-graphite">Noch nichts geschrieben. Bald mehr.</p>
                )}

                {groups.map((group) => (
                    <section key={group.country} className="mb-16" aria-label={group.country}>
                        <h2 className="label-xs sticky top-[57px] z-10 bg-paper py-3 text-graphite">
                            {group.country}
                        </h2>
                        {group.posts.map((post) => {
                            running += 1;
                            return <PostCard key={post.id} post={post} index={running} />;
                        })}
                    </section>
                ))}
            </div>
        </PublicLayout>
    );
}
