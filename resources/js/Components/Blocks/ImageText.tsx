import Caption from '@/Components/Caption';
import Frame from '@/Components/Frame';
import type { BlockProps } from '@/types';

export default function ImageText({ block }: { block: BlockProps }) {
    const image = block.images[0];
    const html = (block.data.html as string) ?? '';
    const caption = (block.data.caption as string) ?? image?.caption;
    const imageRight = block.data.variant === 'right';

    return (
        <div className="mx-auto max-w-6xl px-5 sm:px-8">
            {/* Stacks on mobile with the picture first, whichever side it sits
                on at desktop width. */}
            <div className="flex flex-col gap-6 md:grid md:grid-cols-12 md:items-start md:gap-10">
                {image && (
                    <figure className={`m-0 md:col-span-7 ${imageRight ? 'md:order-2' : 'md:order-1'}`}>
                        <Frame image={image} sizes="(min-width: 768px) 55vw, 100vw" />
                        <Caption>{caption}</Caption>
                    </figure>
                )}
                <div
                    className={`prose-column text-[1.0625rem] text-ink-soft md:col-span-5 md:text-base ${
                        imageRight ? 'md:order-1' : 'md:order-2'
                    }`}
                    dangerouslySetInnerHTML={{ __html: html }}
                />
            </div>
        </div>
    );
}
