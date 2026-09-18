import { Head } from '@inertiajs/react';
import NewsletterForm from '@/Components/NewsletterForm';
import PublicLayout from '@/Layouts/PublicLayout';

export default function NewsletterIndex() {
    return (
        <PublicLayout>
            <Head title="Newsletter" />

            <div className="mx-auto max-w-2xl px-5 pt-12 pb-20 sm:px-8 sm:pt-20">
                <p className="label-xs text-graphite">Newsletter</p>
                <h1 className="mt-4 font-display text-[clamp(2.5rem,7vw,4.5rem)] leading-[1]">
                    Nichts verpassen
                </h1>
                <p className="mt-6 text-lg leading-relaxed text-graphite">
                    Wir schreiben unregelmäßig – immer dann, wenn wir irgendwo angekommen sind und ein
                    neues Stück fertig ist. Melde dich an und bekomme eine kurze Nachricht, sobald ein
                    Eintrag online ist.
                </p>

                <div className="mt-10">
                    <NewsletterForm compact />
                </div>

                <p className="mt-6 text-sm leading-relaxed text-graphite">
                    Nach der Anmeldung schicken wir dir eine E-Mail mit einem Bestätigungslink. Erst wenn
                    du darauf klickst, bist du angemeldet. Deine Adresse nutzen wir ausschließlich für
                    diesen Newsletter und geben sie nicht weiter.
                </p>
            </div>
        </PublicLayout>
    );
}
