import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { AudioProvider } from '@/lib/audio';
import '../css/app.css';

createInertiaApp({
    title: (title) => (title ? `${title} – Wandermäuse` : 'Wandermäuse'),
    // Lazy, so each page is its own chunk. It matters here: the admin editor
    // pulls in a rich-text stack that a visitor reading on a phone must never
    // have to download.
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.tsx');
        const page = pages[`./Pages/${name}.tsx`];
        if (!page) {
            throw new Error(`Inertia page not found: ./Pages/${name}.tsx`);
        }
        return page() as never;
    },
    setup({ el, App, props }) {
        createRoot(el).render(
            <AudioProvider>
                <App {...props} />
            </AudioProvider>,
        );
    },
    progress: {
        color: '#141414',
        showSpinner: false,
    },
});
