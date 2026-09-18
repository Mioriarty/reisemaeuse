import type { BlockProps } from '@/types';

export default function Quote({ block }: { block: BlockProps }) {
    const text = (block.data.text as string) ?? '';
    const attribution = (block.data.attribution as string) ?? '';

    return (
        <div className="mx-auto max-w-4xl px-5 sm:px-8">
            <blockquote className="hairline-t hairline-b py-8 sm:py-12">
                <p className="font-display text-[clamp(1.75rem,4.5vw,3rem)] leading-[1.1] text-balance italic">
                    {text}
                </p>
                {attribution && <footer className="label-xs mt-6 text-graphite">{attribution}</footer>}
            </blockquote>
        </div>
    );
}
