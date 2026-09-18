import { useEffect, useMemo, useRef, useState } from 'react';
import { geoMercator, geoPath } from 'd3-geo';
import { select } from 'd3-selection';
// Side-effect import: this is what puts .transition() on a selection.
import 'd3-transition';
import { zoom as d3zoom, zoomIdentity, type ZoomBehavior, type ZoomTransform } from 'd3-zoom';
import type { FeatureCollection } from 'geojson';
import land from '@/lib/americas.geo.json';
import type { StopProps } from '@/types';

const W = 800;
const H = 1000;

type Props = {
    stops: StopProps[];
    /** Renders this stop in the accent colour with its name beside it. */
    focusStopId?: number | null;
    onSelect?: (stop: StopProps) => void;
    /** `compact` is the band above a blog entry, `full` the standalone map. */
    variant?: 'full' | 'compact';
};

export default function RouteMap({ stops, focusStopId = null, onSelect, variant = 'full' }: Props) {
    const svgRef = useRef<SVGSVGElement | null>(null);
    const zoomRef = useRef<ZoomBehavior<SVGSVGElement, unknown> | null>(null);
    const [transform, setTransform] = useState<ZoomTransform>(zoomIdentity);
    const [hovered, setHovered] = useState<number | null>(null);

    const ordered = useMemo(
        () => [...stops].sort((a, b) => a.position - b.position),
        [stops],
    );

    const { countryPaths, points } = useMemo(() => {
        const collection = land as FeatureCollection;
        const projection = geoMercator().fitExtent(
            [
                [32, 32],
                [W - 32, H - 32],
            ],
            collection,
        );
        const pathFor = geoPath(projection);

        return {
            countryPaths: collection.features.map((feature, i) => ({
                key: (feature.properties?.iso as string) ?? String(i),
                d: pathFor(feature) ?? '',
            })),
            points: ordered.map((stop) => {
                const xy = projection([stop.lng, stop.lat]);
                return { stop, x: xy?.[0] ?? 0, y: xy?.[1] ?? 0 };
            }),
        };
    }, [ordered]);

    const routeD = useMemo(
        () => (points.length < 2 ? '' : 'M' + points.map((p) => `${p.x.toFixed(1)},${p.y.toFixed(1)}`).join('L')),
        [points],
    );

    // Stops cluster - Puno and La Paz are 300km apart and their labels collide
    // at this scale. Place labels greedily and drop the ones that would
    // overlap; the focused stop always wins its slot.
    const labelled = useMemo(() => {
        const fontSize = variant === 'compact' ? 15 : 16;
        const placed: { x1: number; y1: number; x2: number; y2: number }[] = [];
        const keep = new Set<number>();

        const order = [...points].sort((a, b) => {
            if (a.stop.id === focusStopId) return -1;
            if (b.stop.id === focusStopId) return 1;
            return a.stop.position - b.stop.position;
        });

        for (const { stop, x, y } of order) {
            const box = {
                x1: x + 6,
                y1: y - fontSize * 0.6,
                x2: x + 6 + stop.name.length * fontSize * 0.55,
                y2: y + fontSize * 0.6,
            };

            const clash = placed.some(
                (b) => box.x1 < b.x2 && box.x2 > b.x1 && box.y1 < b.y2 && box.y2 > b.y1,
            );

            if (!clash) {
                placed.push(box);
                keep.add(stop.id);
            }
        }

        return keep;
    }, [points, focusStopId, variant]);

    useEffect(() => {
        const svg = svgRef.current;
        if (!svg) return;

        const behavior = d3zoom<SVGSVGElement, unknown>()
            .scaleExtent([1, 8])
            .translateExtent([
                [0, 0],
                [W, H],
            ])
            // Deliberately does not hijack the page: a single finger scrolls
            // the article as usual, two fingers pinch the map, and the wheel
            // only zooms when it is a trackpad pinch gesture.
            .filter((event: Event) => {
                if (event.type === 'wheel') return (event as WheelEvent).ctrlKey;
                if (event.type === 'touchstart') return (event as TouchEvent).touches.length >= 2;
                return !(event as MouseEvent).button;
            })
            .on('zoom', (event) => setTransform(event.transform));

        zoomRef.current = behavior;
        select(svg).call(behavior);

        return () => {
            select(svg).on('.zoom', null);
        };
    }, []);

    const zoomBy = (factor: number) => {
        const svg = svgRef.current;
        if (!svg || !zoomRef.current) return;
        select(svg).transition().duration(220).call(zoomRef.current.scaleBy, factor);
    };

    const resetZoom = () => {
        const svg = svgRef.current;
        if (!svg || !zoomRef.current) return;
        select(svg).transition().duration(260).call(zoomRef.current.transform, zoomIdentity);
    };

    // Keep hairlines and markers visually constant while zooming in.
    const k = transform.k;
    const stroke = 1 / k;
    const square = (variant === 'compact' ? 7 : 8) / k;

    const focusPoint = points.find((p) => p.stop.id === focusStopId);

    // Die Karte reicht von Nordmexiko bis Feuerland und ist damit deutlich
    // hoeher als breit: ueber die volle Containerbreite waere sie auf einem
    // Laptop 1400px hoch. Der Rahmen behaelt das 4:5 des viewBox - genau das
    // Seitenverhaeltnis, das die Landmasse in Mercator hat - und ist in der
    // Breite gedeckelt. So fuellt das Land den Rahmen ohne Rand, und auf dem
    // Handy ist es einfach die Spaltenbreite.
    const frame =
        variant === 'compact'
            ? 'mx-auto aspect-[4/5] w-full max-w-[420px]'
            : 'mx-auto aspect-[4/5] w-full max-w-[560px]';

    return (
        <figure className={`relative m-0 bg-paper-dim ${frame}`}>
            <svg
                ref={svgRef}
                viewBox={`0 0 ${W} ${H}`}
                preserveAspectRatio="xMidYMid meet"
                className="absolute inset-0 block h-full w-full touch-pan-y select-none"
                role="img"
                aria-label={
                    focusPoint
                        ? `Karte der Reiseroute von Mexiko bis Feuerland, hervorgehoben: ${focusPoint.stop.name}`
                        : 'Karte der Reiseroute von Mexiko bis Feuerland'
                }
            >
                <g transform={transform.toString()}>
                    {countryPaths.map((country) => (
                        <path
                            key={country.key}
                            d={country.d}
                            fill="var(--color-map-land)"
                            stroke="var(--color-paper-dim)"
                            strokeWidth={stroke * 1.5}
                        />
                    ))}

                    {routeD && (
                        <path
                            d={routeD}
                            fill="none"
                            stroke="var(--color-ink)"
                            strokeWidth={stroke * 1.75}
                            strokeDasharray={`${stroke * 7} ${stroke * 5}`}
                            strokeLinejoin="round"
                            strokeLinecap="square"
                        />
                    )}

                    {points.map(({ stop, x, y }) => {
                        const isFocus = stop.id === focusStopId;
                        const isHovered = hovered === stop.id;
                        const size = isFocus ? square * 1.6 : square;

                        return (
                            <g key={stop.id}>
                                <rect
                                    x={x - size / 2}
                                    y={y - size / 2}
                                    width={size}
                                    height={size}
                                    fill={isFocus ? 'var(--color-accent)' : 'var(--color-paper)'}
                                    stroke="var(--color-ink)"
                                    strokeWidth={stroke * 1.75}
                                />
                                {/* A generous invisible hit area - the visible square is far
                                    below a comfortable tap target on a phone. */}
                                <rect
                                    x={x - 14 / k}
                                    y={y - 14 / k}
                                    width={28 / k}
                                    height={28 / k}
                                    fill="transparent"
                                    className={onSelect ? 'cursor-pointer' : undefined}
                                    onMouseEnter={() => setHovered(stop.id)}
                                    onMouseLeave={() => setHovered(null)}
                                    onClick={() => onSelect?.(stop)}
                                >
                                    <title>{stop.name}</title>
                                </rect>
                                {(isFocus || isHovered || labelled.has(stop.id)) && (
                                    <text
                                        x={x + size}
                                        y={y + 4 / k}
                                        fontSize={(variant === 'compact' ? 15 : 16) / k}
                                        fontFamily="var(--font-sans)"
                                        fontWeight={isFocus ? 600 : 500}
                                        fill="var(--color-ink)"
                                        paintOrder="stroke"
                                        stroke="var(--color-paper-dim)"
                                        strokeWidth={3 / k}
                                        className="pointer-events-none"
                                    >
                                        {stop.name}
                                    </text>
                                )}
                            </g>
                        );
                    })}
                </g>
            </svg>

            <div className="absolute right-0 bottom-0 flex">
                <MapButton label="Hineinzoomen" onClick={() => zoomBy(1.5)}>
                    +
                </MapButton>
                <MapButton label="Herauszoomen" onClick={() => zoomBy(1 / 1.5)}>
                    −
                </MapButton>
                <MapButton label="Ansicht zurücksetzen" onClick={resetZoom}>
                    ⤾
                </MapButton>
            </div>
        </figure>
    );
}

function MapButton({
    children,
    label,
    onClick,
}: {
    children: React.ReactNode;
    label: string;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            aria-label={label}
            onClick={onClick}
            className="flex h-11 w-11 items-center justify-center border-t border-l border-hairline bg-paper text-base text-ink transition-colors hover:bg-ink hover:text-paper"
        >
            {children}
        </button>
    );
}
