import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState, type ReactNode } from 'react';
import MiniPlayer from '@/Components/MiniPlayer';
import type { SharedProps } from '@/types';

const NAV = [
    { href: '/blog', label: 'Einträge' },
    { href: '/reise', label: 'Die Route' },
    { href: '/newsletter', label: 'Newsletter' },
];

export default function PublicLayout({ children }: { children: ReactNode }) {
    const { url, props } = usePage<SharedProps>();
    const [menuOpen, setMenuOpen] = useState(false);
    const flash = props.flash;

    // Any navigation closes the menu, including the browser back button.
    useEffect(() => setMenuOpen(false), [url]);

    return (
        <div className="flex min-h-screen flex-col">
            <header className="sticky top-0 z-50 border-b border-ink bg-paper/95 backdrop-blur-sm">
                <div className="mx-auto flex max-w-6xl items-center justify-between px-5 py-4 sm:px-8">
                    <Link href="/" className="font-display text-lg font-bold tracking-tight">
                        Reisemäuse
                    </Link>

                    <nav className="hidden gap-8 sm:flex" aria-label="Hauptnavigation">
                        {NAV.map((item) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={`label-xs transition-colors hover:text-accent ${
                                    url.startsWith(item.href) ? 'text-ink' : 'text-graphite'
                                }`}
                            >
                                {item.label}
                            </Link>
                        ))}
                    </nav>

                    <button
                        type="button"
                        onClick={() => setMenuOpen((open) => !open)}
                        aria-expanded={menuOpen}
                        aria-controls="mobile-nav"
                        className="label-xs -mr-2 flex h-11 items-center px-2 sm:hidden"
                    >
                        {menuOpen ? 'Schließen' : 'Menü'}
                    </button>
                </div>

                {menuOpen && (
                    <nav id="mobile-nav" className="border-t border-hairline sm:hidden" aria-label="Hauptnavigation">
                        {NAV.map((item) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                className="label-xs block border-b border-hairline px-5 py-4 last:border-b-0"
                            >
                                {item.label}
                            </Link>
                        ))}
                    </nav>
                )}
            </header>

            {flash?.success && (
                <p role="status" className="border-b border-ink bg-ink px-5 py-3 text-center text-sm text-paper sm:px-8">
                    {flash.success}
                </p>
            )}
            {flash?.error && (
                <p role="alert" className="border-b border-accent bg-accent px-5 py-3 text-center text-sm text-paper sm:px-8">
                    {flash.error}
                </p>
            )}

            <main className="flex-1">{children}</main>

            <MiniPlayer />

            <footer className="mt-20 border-t border-ink">
                <div className="mx-auto max-w-6xl px-5 py-10 sm:px-8 sm:py-14">
                    <div className="flex flex-col gap-8 sm:flex-row sm:justify-between">
                        <div>
                            <p className="font-display text-lg font-bold tracking-tight">Reisemäuse</p>
                            <p className="mt-2 max-w-xs text-sm leading-relaxed text-graphite">
                                Ein Reisetagebuch aus Südamerika – mit Bildern, Karte und zu jedem Eintrag
                                einer kleinen Komposition.
                            </p>
                        </div>
                        <nav className="flex flex-col gap-3" aria-label="Rechtliches">
                            <Link href="/impressum" className="label-xs text-graphite hover:text-ink">
                                Impressum
                            </Link>
                            <Link href="/datenschutz" className="label-xs text-graphite hover:text-ink">
                                Datenschutz
                            </Link>
                            <a href="/feed.xml" className="label-xs text-graphite hover:text-ink">
                                RSS
                            </a>
                        </nav>
                    </div>
                </div>
            </footer>
        </div>
    );
}
