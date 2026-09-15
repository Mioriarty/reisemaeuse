import type { ImageProps } from '@/types';

type Props = {
    image: ImageProps;
    sizes?: string;
    className?: string;
    priority?: boolean;
    /** Crop to a fixed ratio instead of honouring the photo's own. */
    ratio?: number;
};

/**
 * A photograph in a hard-edged frame.
 *
 * The box is sized from the stored aspect ratio and painted in the image's
 * dominant colour before the file arrives, so nothing on the page jumps while
 * a long entry loads over a slow connection.
 */
export default function Frame({ image, sizes = '100vw', className = '', priority = false, ratio }: Props) {
    const aspect = ratio ?? image.aspectRatio ?? 1;

    return (
        <div
            className={`relative w-full overflow-hidden ${className}`}
            style={{ aspectRatio: String(aspect), backgroundColor: image.dominantColor }}
        >
            <picture>
                <source type="image/webp" srcSet={image.srcset} sizes={sizes} />
                <img
                    src={image.src}
                    srcSet={image.jpegSrcset}
                    sizes={sizes}
                    alt={image.alt}
                    width={image.width}
                    height={image.height}
                    loading={priority ? 'eager' : 'lazy'}
                    decoding={priority ? 'sync' : 'async'}
                    fetchPriority={priority ? 'high' : 'auto'}
                    className="absolute inset-0 h-full w-full object-cover"
                />
            </picture>
        </div>
    );
}
