import type { BlockProps } from '@/types';

export default function Divider({ block }: { block: BlockProps }) {
    const glyph = (block.data.glyph as string) ?? '';

    return (
        <div className="mx-auto max-w-5xl px-5 sm:px-8">
            <div className="flex items-center gap-4">
                <span className="h-px flex-1 bg-hairline" />
                {glyph && <span className="font-display text-sm text-graphite">{glyph}</span>}
                <span className="h-px flex-1 bg-hairline" />
            </div>
        </div>
    );
}
