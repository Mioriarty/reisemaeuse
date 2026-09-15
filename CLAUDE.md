# wandermäuse

Reiseblog auf Laravel 13 + Inertia 3 + React 19 + TypeScript + Tailwind 4.
Öffentliche Seite auf Deutsch, Verwaltung unter `/admin`.

## Umgebung

- Lokales PHP ist **`/opt/homebrew/bin/php`** (8.3). Das `php` im PATH ist
  XAMPP 8.0 und zu alt für Laravel 13.
- Composer entsprechend als `/opt/homebrew/bin/php /usr/local/bin/composer`.
- Lokal SQLite, in Produktion MySQL.

## Wo was liegt

- Die acht Layout-Muster eines Eintrags: `app/Enums/BlockType.php` plus je eine
  Komponente in `resources/js/Components/Blocks/`. Ein neues Muster heißt: ein
  Case im Enum, eine Komponente, ein Eintrag in `Blocks/index.tsx` und ein Fall
  in `BlockEditor.tsx` – sonst nichts.
- Die Karte ist selbst gerendertes SVG (`RouteMap.tsx`, d3-geo, GeoJSON in
  `resources/js/lib/`). **Keine Kartenkacheln von Dritten einbauen** – die
  Datenschutzerklärung sagt ausdrücklich zu, dass die Seite keine fremden
  Server kontaktiert. Das Gleiche gilt für Schriften und eingebettete Videos.

## Randbedingungen des Hostings

netcup Shared Hosting, kein Root, keine Dauerprozesse:

- Kein Queue-Worker. Alles Wiederkehrende hängt am Scheduler in
  `routes/console.php`, den ein Cronjob jede Minute anstößt.
- Node und Composer laufen dort nicht. Gebaut wird in GitHub Actions, netcup
  zieht den fertigen Branch `deploy`. Details in `DEPLOYMENT.md`.
- Bildvarianten werden beim Upload **synchron** erzeugt, nicht per Queue – die
  Queue läuft nur einmal pro Minute.

## Sonstiges

- Öffentliche Seiten müssen auf dem Handy funktionieren (390px, keine
  Querscrollbalken, Tap-Ziele ≥ 44px). Die Verwaltung ist bewusst
  Desktop-first.
- Kein `border-radius`, Hairlines statt Schatten, Farbe nur sparsam über
  `--color-accent`. Die Fotos tragen die Farbe.
- Newsletter: Double Opt-in ist in Deutschland Pflicht. Unbestätigte Adressen
  dürfen nie eine Kampagne bekommen (`Subscriber::scopeMailable`).
- Vor dem Commit: `php artisan test` und `npx tsc --noEmit`.
