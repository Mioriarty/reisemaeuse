import type { BlockProps } from '@/types';

export default function Heading({ block }: { block: BlockProps }) {
    const text = (block.data.text as string) ?? '';
    const label = (block.data.label as string) ?? '';

    return (
        <div className="mx-auto max-w-5xl px-5 sm:px-8">
            {label && <p className="label-xs mb-3 text-graphite">{label}</p>}
            <h2 className="font-display text-[clamp(2rem,5vw,3.5rem)] leading-[1.02] text-balance">
                {text}
            </h2>
        </div>
    );
}
