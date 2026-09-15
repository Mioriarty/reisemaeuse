import type { BlockProps } from '@/types';

export default function Text({ block }: { block: BlockProps }) {
    const html = (block.data.html as string) ?? '';

    return (
        <div
            className="prose-column mx-auto px-5 text-[1.0625rem] text-ink-soft sm:px-8 md:text-lg"
            dangerouslySetInnerHTML={{ __html: html }}
        />
    );
}
