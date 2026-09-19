import Caption from '@/Components/Caption';
import Frame from '@/Components/Frame';
import type { BlockProps } from '@/types';

export default function ImagePair({ block }: { block: BlockProps }) {
    const [left, right] = block.images;
    if (!left && !right) return null;

    const caption = (block.data.caption as string) ?? '';

    return (
        <figure className="m-0 px-5 sm:px-8">
            {/* Cropped to a shared 4:5 so the two sit as an even pair. */}
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5">
                {left && <Frame image={left} ratio={4 / 5} sizes="(min-width: 640px) 50vw, 100vw" />}
                {right && <Frame image={right} ratio={4 / 5} sizes="(min-width: 640px) 50vw, 100vw" />}
            </div>
            <Caption>{caption}</Caption>
        </figure>
    );
}
