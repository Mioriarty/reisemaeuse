export default function Caption({ children }: { children?: string | null }) {
    if (!children) return null;

    return (
        <figcaption className="mt-3 flex gap-3 text-sm leading-snug text-graphite">
            <span aria-hidden className="mt-2 h-px w-6 shrink-0 bg-hairline" />
            <span>{children}</span>
        </figcaption>
    );
}
