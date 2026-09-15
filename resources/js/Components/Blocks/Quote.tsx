import type { BlockProps } from '@/types';

export default function Quote({ block }: { block: BlockProps }) {
    const text = (block.data.text as string) ?? '';
    const attribution = (block.data.attribution as string) ?? '';

    return (
        <div className="mx-auto max-w-4xl px-5 sm:px-8">
            <blockquote className="hairline-t hairline-b py-8 sm:py-12">
                <p className="font-display text-2xl leading-[1.15] font-medium tracking-tight text-balance sm:text-3xl md:text-4xl">
                    {text}
                </p>
                {attribution && <footer className="label-xs mt-6 text-graphite">{attribution}</footer>}
            </blockquote>
        </div>
    );
}
