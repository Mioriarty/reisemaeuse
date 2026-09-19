import Caption from '@/Components/Caption';
import Frame from '@/Components/Frame';
import type { BlockProps } from '@/types';

export default function Gallery({ block }: { block: BlockProps }) {
    if (block.images.length === 0) return null;

    const caption = (block.data.caption as string) ?? '';

    return (
        <figure className="m-0 px-5 sm:px-8">
            <div className="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3">
                {block.images.map((image) => (
                    <Frame
                        key={image.id}
                        image={image}
                        ratio={4 / 5}
                        sizes="(min-width: 768px) 33vw, 50vw"
                    />
                ))}
            </div>
            <Caption>{caption}</Caption>
        </figure>
    );
}
