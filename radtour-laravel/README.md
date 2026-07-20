# Familien-Radtourenplaner

Laravel-Umsetzung des lokalen Tourenplaners für familienfreundliche Fahrradfahrten rund um Geldern und die Maasduinen.

## Lokale Nutzung

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8081
```

Danach ist die Anwendung unter `http://127.0.0.1:8081` erreichbar.

## Zugangsdaten

Die Datei `.env` bleibt ausschließlich lokal bzw. auf dem Webserver. Zugangsschlüssel gehören niemals in JavaScript, HTML oder das Git-Repository.

```env
OPENROUTESERVICE_API_KEY=
GOOGLE_PLACES_API_KEY=
```

- `OPENROUTESERVICE_API_KEY`: wird vom Server für die Routenberechnung verwendet.
- `GOOGLE_PLACES_API_KEY`: ist für die serverseitige Suche nach Orten, Öffnungszeiten und lizenzkonformen Google-Fotos vorbereitet.

Für Produktion ist ein separater Google-Schlüssel mit **IP-Beschränkung** für den Webserver empfehlenswert. Ein auf Website-Referrer beschränkter Schlüssel gehört nur in browserseitige Google-Komponenten.

## Architektur

- `resources/views/planner.blade.php`: Kartenoberfläche auf Basis des validierten Leaflet-Prototyps
- `POST /api/route`: schützt den OpenRouteService-Schlüssel und leitet Routenanfragen weiter
- `POST /api/places/search`: vorbereitet für Google Places API (New)
- `tours`: gespeicherte Hin-/Rückwege, Stopps und Tour-Einstellungen
- `place_caches`: Basis für zeitlich begrenztes Caching externer Ortsdaten

## MySQL auf All-inkl

Für das Hosting werden in `.env` anstelle von SQLite die bereitgestellten MySQL-Daten hinterlegt:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://radtour.ki-experte-derix.de
DB_CONNECTION=mysql
DB_HOST=DEIN-MYSQL-HOST
DB_PORT=3306
DB_DATABASE=d047ac99
DB_USERNAME=d047ac99
DB_PASSWORD=DEIN-DATENBANKPASSWORT
```

Danach auf dem Server `php artisan migrate --force` ausführen.
