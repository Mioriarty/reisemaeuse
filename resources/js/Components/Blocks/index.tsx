import Divider from './Divider';
import Gallery from './Gallery';
import Heading from './Heading';
import ImageFull from './ImageFull';
import ImagePair from './ImagePair';
import ImageText from './ImageText';
import Quote from './Quote';
import Text from './Text';
import type { BlockProps, BlockType } from '@/types';

const REGISTRY: Record<BlockType, (props: { block: BlockProps }) => React.ReactNode> = {
    text: Text,
    heading: Heading,
    image_full: ImageFull,
    image_text: ImageText,
    image_pair: ImagePair,
    gallery: Gallery,
    quote: Quote,
    divider: Divider,
};

/**
 * Maps a stored pattern onto its component. Adding a ninth pattern is a new
 * file plus one line here plus one case in App\Enums\BlockType.
 */
export default function BlockRenderer({ blocks }: { blocks: BlockProps[] }) {
    return (
        <div className="flex flex-col gap-12 sm:gap-16">
            {blocks.map((block) => {
                const Component = REGISTRY[block.type];
                if (!Component) return null;
                return <Component key={block.id} block={block} />;
            })}
        </div>
    );
}
