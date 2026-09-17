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
  Cronjob          → merkt den neuen Commit und migriert innerhalb einer Minute
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

Im WCP unter *Geplante Aufgaben* **eine** Aufgabe anlegen, jede Minute:

```sh
cd ~/httpdocs && php artisan schedule:run >> /dev/null 2>&1
```

Dieser eine Cronjob erledigt alles Wiederkehrende:

| Was | Wie oft |
|---|---|
| Queue leeren (Newsletter-Versand) | jede Minute |
| Nach einem Deploy migrieren und Caches neu bauen | jede Minute, nur bei neuem Commit |
| Geplante Einträge veröffentlichen | alle zehn Minuten |
| IP-Prüfwerte der Kommentare löschen | täglich |

Shared Hosting kann keine Dauerprozesse – deshalb kein Queue-Worker, sondern
`queue:work --stop-when-empty` im Minutentakt.

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
