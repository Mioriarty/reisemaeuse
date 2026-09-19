# wandermaeuse.de auf netcup einrichten

Einmalige Einrichtung. Danach reicht `git push` auf `main` – alles Weitere läuft
automatisch.

## Wie das Deployment funktioniert

Der netcup-Webspace kann **kein** `npm run build` und **kein** `composer install`
zuverlässig ausführen: Node gibt es erst ab Webhosting 4000, und Composer in der
Shell scheitert je nach Paket am Speicherlimit. Außerdem werden netcups
„Zusätzliche Bereitstellungsaktionen“ nicht auf allen Verträgen tatsächlich
ausgeführt.

Deshalb:

```
  push auf main
        │
        ▼
  GitHub Actions   → Tests, composer install --no-dev, npm run build
        │
        ▼
  Branch `deploy`  → fertiger Stand inklusive vendor/ und public/build/
        │
        ▼
  netcup Git       → checkt `deploy` nach httpdocs/ aus
        │
        ▼
  Cronjob          → merkt den neuen Commit und migriert beim nächsten Lauf
```

Auf dem Webspace wird also nur noch ausgecheckt und `php artisan` ausgeführt.

## 1. PHP-Version

Zwei Stellen, beide brauchen **8.3 oder neuer** (Laravel 13 setzt 8.3 voraus):

- **Web:** im WCP unter *PHP-Einstellungen* die Version wählen.
- **Shell:** kommt aus der Datei `/conf/phpversion` auf dem Webspace – die
  Web-Einstellung gilt dort *nicht*. Eine Anleitung liegt in
  `/conf-options/phpversion.readme`. Prüfen mit:

  ```sh
  ssh dein-user@dein-webspace
  php -v
  ```

Der gleiche Wert muss in `.github/workflows/deploy.yml` unter `php-version`
stehen, damit `vendor/` zur Laufzeitumgebung passt.

## 1a. Upload-Grenzen

Die Standardwerte des Webspace sind fuer ein Fotoblog zu eng. Beobachtet waren:
`upload_max_filesize 8M`, `post_max_size 8M`, `memory_limit 128M`,
`max_input_time 60`.

Zwei Grenzen, die man leicht verwechselt:

- **`upload_max_filesize`** gilt je Datei. Wird sie ueberschritten, kommt die
  Datei leer bei Laravel an, und die Regel `image` meldet, das sei kein Bild.
  Die Fehlermeldung zeigt also auf das Foto statt auf die Ursache.
- **`post_max_size`** gilt fuer die **ganze Anfrage**. Die Verwaltung nimmt bis
  zu 20 Bilder auf einmal an – drei Handyfotos sprengen 8M bereits. Wird sie
  ueberschritten, verwirft PHP den kompletten Rumpf, also auch das CSRF-Feld:
  Laravel antwortet dann mit **419 Page Expired**, nicht mit einem
  Validierungsfehler. Wer 419 sieht, sucht an der falschen Stelle.

Zielwerte:

| Einstellung | Standard | Ziel | Warum |
|---|---|---|---|
| `upload_max_filesize` | 8M | 32M | `MediaController` erlaubt 24 MB je Datei; PHP muss darueber liegen |
| `post_max_size` | 8M | 256M | muss den ganzen Stapel fassen, nicht die einzelne Datei |
| `memory_limit` | 128M | 512M | ein Foto wird als Bitmap ausgepackt: Breite x Hoehe x 4 Byte. 12 MP sind rund 50 MB, 48 MP rund 200 MB |
| `max_input_time` | 60 | 300 | Zeit zum Empfangen des Uploads – ein grosser Stapel dauert laenger |
| `max_execution_time` | 180 | 300 | die Bildvarianten entstehen synchron, ohne Queue-Worker |

Zwei Wege, sie zu setzen:

1. **Im WCP unter *PHP-Einstellungen*.** Der verlaesslichere Weg, weil er
   unabhaengig davon wirkt, wie PHP ausgefuehrt wird.
2. **Ueber `public/.user.ini`.** Liegt im Repository und faehrt beim Deploy von
   selbst mit. Greift nur, wenn PHP als FPM oder CGI laeuft – bei netcup ist
   das der Fall, unter `mod_php` waere die Datei wirkungslos. Aenderungen
   wirken erst nach bis zu fuenf Minuten (`user_ini.cache_ttl`).

Setzt das WCP die Werte per `php_admin_value`, gewinnt es gegen `.user.ini`.
Wenn nach einem Deploy nichts passiert, also dort nachsehen. Pruefen laesst es
sich per SSH mit `php -i | grep upload_max_filesize` – das zeigt allerdings die
Shell-Konfiguration, nicht die des Webs. Verlaesslich ist nur ein echter
Upload-Versuch.

`max_file_uploads` (Standard 20) laesst sich per `.user.ini` **nicht** setzen,
die Einstellung ist `PHP_INI_SYSTEM`. Der Standard passt aber genau zu
`files => max:20` im `MediaController`.

## 2. Dokumentenstamm

Laravel darf nur den Ordner `public/` ausliefern – sonst liegen `.env` und
`vendor/` im Web.

- Dokumentenstamm der Domain auf **`httpdocs/public`** setzen (im CCP für die
  Hauptdomain, im WCP für Subdomains).
- `open_basedir` muss **`httpdocs`** umfassen, also den Ordner *über* dem
  Dokumentenstamm. Sonst kommt Laravel nicht an `vendor/` und `storage/` und
  die Seite bleibt weiß.

## 3. Git-Deployment im WCP

- Repository: `https://github.com/Mioriarty/wandermaeuse.git`
- Branch: **`deploy`** (nicht `main` – auf `main` fehlen `vendor/` und die
  gebauten Assets)
- Zielverzeichnis: `httpdocs`
- Privates Repository: einen SSH-Schlüssel auf dem Webspace erzeugen und den
  öffentlichen Teil auf GitHub als *Deploy Key* (nur Lesen) hinterlegen.
  Zugangsdaten in der URL lehnt netcup ab.
- Optional als Bereitstellungsaktion: `bash deploy/post-deploy.sh`.
  Falls das nicht läuft, ist das nicht schlimm – der Cronjob aus Schritt 6
  erledigt dasselbe.

## 4. Datenbank und Postfach

- Im WCP eine **MySQL-Datenbank** anlegen.
- Ein Postfach `newsletter@wandermaeuse.de` anlegen; die Zugangsdaten kommen in
  die `.env`.

## 5. `.env` anlegen

Die `.env` liegt **nicht** im Repository. Einmalig per SSH:

```sh
cd ~/httpdocs
cp .env.example .env
nano .env          # DB_*, MAIL_* und APP_URL eintragen

php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan wandermaeuse:admin --name="Moritz" --email="du@example.org"
```

`APP_DEBUG=false` und `APP_ENV=production` müssen gesetzt sein.

## 6. Cronjob

Im WCP unter *Geplante Aufgaben* **eine** Aufgabe anlegen, so oft der Tarif
es zulässt:

```sh
cd ~/httpdocs && php artisan schedule:run >> /dev/null 2>&1
```

Dieser eine Cronjob erledigt alles Wiederkehrende: Queue leeren
(Newsletter-Versand), nach einem Deploy migrieren und Caches neu bauen,
geplante Einträge veröffentlichen, IP-Prüfwerte der Kommentare löschen.

Shared Hosting kann keine Dauerprozesse – deshalb kein Queue-Worker, sondern
`queue:work --stop-when-empty` bei jedem Lauf.

### Warum alle Aufgaben auf `* * * * *` stehen

Auf diesem Vertrag läuft der Cron nur **stündlich**, und netcup sagt nicht zu,
zu welcher Minute. `schedule:run` führt eine Aufgabe aber nur aus, wenn die
*aktuelle Minute* zu ihrem Cron-Ausdruck passt. Alles Engere als `* * * * *`
kann den einen Tick deshalb dauerhaft verfehlen – `dailyAt('03:30')` hat genau
das getan und die IP-Prüfwerte nie gelöscht, obwohl die Datenschutzerklärung
das zusagt.

Darum steht in `routes/console.php` jede Aufgabe auf `everyMinute()` und ist
idempotent geschrieben: der Takt kommt vom Cronjob, nicht vom Ausdruck.
`tests/Feature/ScheduleTest.php` hält das fest. Wenn der Cron später doch
minutentaktig laufen darf, bleibt alles korrekt – es wird nur wieder pünktlich.

## 7. GitHub

Es sind **keine** Secrets nötig: Der Workflow schreibt nur in das eigene
Repository und nutzt dafür das automatisch bereitgestellte Token. Der Branch
`deploy` wird bei jedem Build neu geschrieben (force push) – dort also niemals
von Hand etwas ändern.

## Fehlersuche

| Symptom | Ursache |
|---|---|
| Weiße Seite, 500er | `open_basedir` umfasst `httpdocs` nicht, oder `.env` fehlt |
| `.env` im Browser erreichbar | Dokumentenstamm zeigt auf `httpdocs` statt `httpdocs/public` |
| Bilder fehlen (404) | `php artisan storage:link` erneut ausführen |
| Upload sagt „ist kein Bild“, obwohl es eins ist | Datei groesser als `upload_max_filesize` – PHP verwirft sie vor Laravel. Schritt 1a |
| Upload mehrerer Bilder endet mit 419 Page Expired | Stapel groesser als `post_max_size` – PHP verwirft den Rumpf samt CSRF-Feld. Schritt 1a |
| Neue Migration nicht eingespielt | `php artisan wandermaeuse:post-deploy` von Hand ausführen |
| Newsletter bleibt bei „wird versendet“ | Cronjob läuft nicht – Schritt 6 prüfen |
| Anmeldung zum Newsletter liefert 500, Log zeigt `535` | `MAIL_HOST`/`MAIL_USERNAME`/`MAIL_PASSWORD` falsch. `MAIL_HOST` muss der netcup-Mailserver sein (`dig +short MX wandermaeuse.de`), danach `php artisan config:clear` |
| Composer-Fehler beim Deploy | Falscher Branch: netcup muss `deploy` ziehen, nicht `main` |

## Sicherungen

netcups Backups decken Webspace und Datenbank ab. Selbst hochgeladene Bilder,
Audiodateien und Noten liegen unter `storage/app/public` – die sind **nicht**
im Git-Repository und hängen allein an diesen Backups. Vor größeren Umbauten
lohnt sich ein eigener Abzug:

```sh
cd ~/httpdocs
tar czf ~/medien-$(date +%F).tar.gz storage/app/public
php artisan db:show   # zeigt, welche Datenbank gesichert werden muss
```
