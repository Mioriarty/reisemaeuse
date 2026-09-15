import type { BlockProps } from '@/types';

export default function Heading({ block }: { block: BlockProps }) {
    const text = (block.data.text as string) ?? '';
    const label = (block.data.label as string) ?? '';

    return (
        <div className="mx-auto max-w-5xl px-5 sm:px-8">
            {label && <p className="label-xs mb-3 text-graphite">{label}</p>}
            <h2 className="font-display text-3xl leading-[1.05] font-bold tracking-tight text-balance sm:text-4xl md:text-5xl">
                {text}
            </h2>
        </div>
    );
}
