# Cookie Consent

Ein schlankes, selbst-gehostetes WordPress-Cookie-Consent-Plugin, das Drittanbieter-Embeds vor der Zustimmung blockiert – vollständig DSGVO/GDPR/TDDDG-konform.

> **Hinweis:** Dieses Plugin befindet sich in aktiver Entwicklung und kann noch Fehler enthalten. Der Einsatz auf Produktivsystemen erfolgt auf eigene Verantwortung. Gefundene Bugs bitte als [Issue](../../issues) melden.

## Features

- **Kein externer Server-Kontakt** – vollständig self-hosted, keine Abhängigkeiten zu externen Consent-Diensten
- **Service-spezifische Platzhalter** mit Vorschaubildern (YouTube, SoundCloud, Mixcloud, hearthis.at) – gecacht ohne Third-Party-Request vor Consent
- **Drei gleichwertige Buttons** – keine Dark Patterns (Alle akzeptieren, Nur Notwendige, Einstellungen)
- **Granulare Zustimmung** – pro Service oder Kategorie (Notwendig / Audio / Video)
- **Polylang-Integration** für mehrsprachige Websites (DE/EN) inkl. sprachspezifischer Datenschutzseiten
- **Cache-kompatibel** – serverseitiges Blockieren + clientseitige Consent-Prüfung; automatischer Cache-Flush bei Einstellungsänderungen
- **Consent-Invalidierung** – ändert sich die Service- oder Cookie-Konfiguration, wird der Banner erneut angezeigt (Abgleich über Config-Hash)
- **Minimaler Footprint** – JS ~11 KB, CSS ~11 KB (minifiziert), kein jQuery
- **Barrierefrei** – ARIA-Labels, Tastaturnavigation, Fokus-Management
- **Optionales Consent-Logging** – anonymisierte IPs (IPv4-Kürzung / IPv6-Maskierung), CSV-Export, tägliches Aufräumen per WP-Cron
- **Eingebetteter Embed-Scanner** – findet alle Embeds auf der Seite
- **Google Consent Mode v2** – optionale Ausgabe der GCM-Defaults (alle Signale `denied`)
- **Shortcode `[sgcc_settings]`** – Cookie-Einstellungen-Link in beliebigen Inhalten platzierbar

## Unterstützte Services

| Service | Kategorie | Vorschaubild |
|---|---|---|
| YouTube | Video | Ja (lokal gecacht) |
| Vimeo | Video | Nein |
| Instagram | Video | Nein |
| SoundCloud | Audio | Ja (lokal gecacht) |
| Bandcamp | Audio | Nein |
| hearthis.at | Audio | Ja (lokal gecacht) |
| Spotify | Audio | Nein |
| Mixcloud | Audio | Ja (lokal gecacht) |

## Abhängigkeiten

### Pflicht

| Abhängigkeit | Version | Zweck |
|---|---|---|
| WordPress | ≥ 6.0 | Core-Plattform |
| PHP | ≥ 8.0 | Serversprache |

### Optional (Plugins)

| Plugin | Zweck | Verhalten ohne Plugin |
|---|---|---|
| **Polylang** | Mehrsprachigkeit (DE/EN), sprachspezifische Datenschutzseiten, Übersetzung aller UI-Strings | Fallback auf Deutsch; nur eine Datenschutzseite konfigurierbar |
| **ARVE – Advanced Responsive Video Embedder** | Spezielle Kompatibilität für ARVE-Embeds (z. B. YouTube-Playlists, absolut positionierte iframes) | Standard-Embed-Blocking ohne ARVE-spezifische Container-Anpassungen |

Das Plugin nutzt folgende Polylang-Funktionen, sofern verfügbar:
- `pll_current_language('slug')` – aktuelle Sprache ermitteln
- `pll__()` – Strings übersetzen
- `pll_get_post_translations()` – verknüpfte Übersetzungen einer Seite
- `pll_register_string()` – Strings im Polylang-Backend registrieren

#### ARVE-Kompatibilität

[ARVE](https://wordpress.org/plugins/advanced-responsive-video-embedder/) ist ein verbreitetes Plugin für responsives Video-Embedding. Cookie Consent enthält spezifische Anpassungen für ARVE-generierte Embeds:

- Korrekte Darstellung nach Consent bei ARVE-Containern mit absoluter Positionierung
- Korrekte Container-Höhe für absolut positionierte iframes (z. B. YouTube-Playlists im ARVE-Format)
- Placeholder füllt den ARVE-Container vollständig aus (keine Lücke unter dem Blocker)

ARVE ist **keine Pflichtabhängigkeit** – das Plugin funktioniert ohne ARVE vollständig. Die Anpassungen greifen automatisch, wenn ARVE installiert und aktiv ist.

### Optional (Cache-Plugins, automatischer Flush bei Einstellungsänderungen)

WP Super Cache, W3 Total Cache, WP Rocket, LiteSpeed Cache (inkl. v4+), Autoptimize, WP Fastest Cache, Hummingbird, SG Optimizer, Kinsta, Comet Cache / ZenCache

Zusätzlich wird die Action `sgcc_cache_flushed` ausgelöst – damit lassen sich weitere Caches anbinden.

### Theme-Abhängigkeiten

**Keine.** Das Plugin ist theme-unabhängig und funktioniert mit jedem WordPress-Theme.
Embeds im Theme-Template (z. B. im Beitrags-Header über `oembed_dataparse`) werden ebenfalls serverseitig korrekt blockiert.

## Installation

1. Ordner `schongeil-cookie-consent` nach `/wp-content/plugins/` hochladen
2. Plugin im WordPress-Backend unter „Plugins" aktivieren
3. Einstellungen unter **Einstellungen → Cookie Consent** konfigurieren

## Einstellungen

Das Admin-Interface bietet fünf Tabs:

| Tab | Inhalt |
|---|---|
| **Allgemein** | Aktivierung, Cookie-Lebensdauer, Datenschutzseiten (DE/EN), Banner-Position, Zusatz-Link (DE/EN), Floating Icon, Consent-Log (Aktivierung + Aufbewahrungsdauer), GCM v2 |
| **Services & Blocking** | Services aktivieren/deaktivieren, eigene Services mit URL-Mustern anlegen, bearbeiten und löschen; Embed-Scanner |
| **Cookies** | CRUD-Interface für individuelle Cookies mit Name, Anbieter, Kategorie, Beschreibung (DE/EN), Dauer, Typ |
| **Texte & Design** | Banner-Texte (Titel, Beschreibung, drei Button-Labels) in DE/EN; Farbwähler für alle UI-Elemente |
| **Consent-Log** | Anonymisierte Einwilligungen einsehen, nach Datum filtern, als CSV exportieren, löschen |

Popup- und Platzhalter-Texte sind im Admin-UI nicht editierbar – sie kommen aus der Service-Registry und lassen sich mit Polylang über die String-Übersetzungen anpassen.

## Shortcode

```
[sgcc_settings]
[sgcc_settings text="Cookie-Einstellungen" class="my-link" tag="button"]
```

Attribute: `text` (Link-Text), `class` (CSS-Klassen), `tag` (`a` oder `button`)

## Google Consent Mode v2

Ist GCM v2 aktiviert, gibt das Plugin im `<head>` die GCM-Defaults aus – `analytics_storage`, `ad_storage`, `ad_user_data` und `ad_personalization` stehen auf `denied`.

**Diese Signale bleiben auf `denied`.** Der Consent-Dialog deckt ausschließlich Embed-Dienste ab (Audio/Video); eine Kategorie für Analytics oder Werbung existiert nicht. Eine Einwilligung in YouTube-Embeds ist keine Einwilligung in Google Analytics, deshalb wird daraus kein `granted` abgeleitet. Wer Analytics oder Ads einwilligungsbasiert steuern will, braucht eine eigene Consent-Kategorie – die ist derzeit nicht implementiert.

## Datenbankschema

Optionen werden in `wp_options` gespeichert (Präfix: `sgcc_`).
Optionales Consent-Log: eigene Tabelle `wp_sgcc_consent_log`.
Thumbnails werden unter `wp-content/uploads/sgcc-thumbnails/` zwischengespeichert.

Bei der Deinstallation werden Optionen, die Log-Tabelle, der Thumbnail-Cache, alle `sgcc_`-Transients und das Cron-Event `sgcc_daily_log_cleanup` entfernt.

## Sicherheit

- Hex-Farbvalidierung gegen CSS-Injection
- Nonce-Schutz auf allen Admin-Formularen
- IP-Anonymisierung: IPv4 letztes Oktett abgeschnitten, IPv6 letzte 80 Bit maskiert
- Kein `X-Forwarded-For` (nur `REMOTE_ADDR`)
- Consent-Log via `navigator.sendBeacon()` + Origin-Check (nonce-frei, caching-kompatibel)
- Alle Ausgaben: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_json_encode()`

## Entwicklung

Build-Tools installieren:

```bash
npm install
```

Es gibt kein `build`-Script – die Minifizierung wird direkt aufgerufen. JavaScript mit `terser`:

```bash
npx terser assets/js/sgcc-frontend.js --compress --mangle -o assets/js/sgcc-frontend.min.js
```

```bash
npx terser assets/js/sgcc-admin.js --compress --mangle -o assets/js/sgcc-admin.min.js
```

CSS mit `clean-css-cli`:

```bash
npx cleancss -o assets/css/sgcc-frontend.min.css assets/css/sgcc-frontend.css
```

```bash
npx cleancss -o assets/css/sgcc-admin.min.css assets/css/sgcc-admin.css
```

Die `.min`-Dateien sind eingecheckt und müssen nach jeder Änderung an den Quelldateien neu erzeugt werden – das Frontend lädt sie, sofern `SCRIPT_DEBUG` nicht gesetzt ist.

**Anforderungen für Build:** Node.js mit `terser` und `clean-css-cli` (siehe `package.json`).

### Testing

```bash
composer install
composer test   # oder: vendor/bin/phpunit
```

**Anforderungen:** PHP ≥ 8.0, Composer. Tests laufen ohne WordPress-Installation (minimale WP-Stubs im Bootstrap).

## Changelog

### 1.7
- Fix: IPv6-Anonymisierung maskiert jetzt auch komprimierte Adressen (z. B. `2001:db8::1`) korrekt – binäres Maskieren der letzten 80 Bit via `inet_pton`
- Fix: Config-Hash wird nach dem Speichern berechnet (`updated_option` statt `update_option`) und umfasst nur noch consent-relevante Optionen
- Neu: Banner öffnet sich erneut, wenn sich die Service-/Cookie-Konfiguration seit der gespeicherten Einwilligung geändert hat
- Fix: Google Consent Mode v2 – Embed-Einwilligung setzt `analytics`/`ad`-Signale nicht mehr auf `granted`; die Defaults bleiben `denied`
- Fix: Polylang-Übersetzungen der Service-Texte (Platzhalter) greifen jetzt tatsächlich
- Fix: CSV-Export des Consent-Logs berücksichtigt den Datumsfilter
- Fix: Backslashes bei Apostrophen in Cookie- und Service-Formularen (fehlendes `wp_unslash`)
- Fix: Custom-Link im Banner – Sprach-Fallback gilt für URL und Text gemeinsam
- Fix: Instagram-URL-Fallback erkennt auch `/reel/`- und `/tv/`-Links
- Neu: Log-Aufräumen läuft täglich per WP-Cron statt nur beim Besuch der Einstellungsseite
- Verbesserung: Fehlgeschlagene Thumbnail-Lookups werden 12 h gemerkt (kein wiederholtes Remote-Warten beim Seitenaufbau)
- Verbesserung: Kein globaler Object-Cache-Flush mehr bei jedem Speichern; Hummingbird-Erkennung korrigiert
- Verbesserung: Sprach-Helfer im Trait `SGCC_L10n` zusammengeführt; tote Config-Keys aus dem Inline-Script entfernt
- Verbesserung: Hex-Farb-Validierung erlaubt nur noch gültige CSS-Längen (3/4/6/8)
- Bereinigung: `uninstall.php` entfernt jetzt auch Thumbnail-Cache, Transients und Cron-Event; ungenutzte Option `sgcc_categories` entfernt

### 1.6
- Neu: PHPUnit-Test-Suite für kritische Regex-Patterns (Service-Erkennung, IP-Anonymisierung, Hex-Farb-Validierung, Embed-Blocking)
- Neu: Custom Services CRUD komplett (Bearbeiten + Löschen im Admin-UI)
- Verbesserung: Custom Services visuell in der Services-Tabelle als „custom" gekennzeichnet

### 1.5.1
- Fix: Consent-Log schlug fehl, wenn IP-Anonymisierung auf Hash stand (SHA-256 = 64 Zeichen, Spalte nur 45)
- Entfernt: IP-Hash-Option komplett entfernt – Truncation ist DSGVO-konform und funktioniert zuverlässig
- Bereinigung: Admin-UI, Aktivierungs-Defaults und `uninstall.php` von `sgcc_consent_log_ip_method` bereinigt

### 1.5
- Sicherheit: Hex-Farbwert-Validierung bei CSS-Custom-Properties gegen CSS-Injection
- Sicherheit: IP-Ermittlung verwendet ausschließlich `REMOTE_ADDR`
- Fix: Polylang-Übersetzung funktioniert jetzt auch in Embed-Platzhaltern
- Fix: Consent-Log funktionierte nicht auf gecachten Seiten
- Fix: Kinsta Cache-Flush nutzt korrekte Action
- Neu: Google Consent Mode v2 via `gtag('consent','update')`
- Verbesserung: Consent-Log nutzt Origin-Check statt Nonce

### 1.3.11
- Neu: Shortcode `[sgcc_settings]`

### 1.3.10
- Neu: Mixcloud- und hearthis.at-Artwork serverseitig gecacht

### 1.3.8 – 1.3.9
- Neu: YouTube- und SoundCloud-Thumbnails serverseitig gecacht

### 1.3.7
- Neu: Automatischer Cache-Flush bei Einstellungsänderungen

### Ältere Versionen
Vollständiges Changelog: [`readme.txt`](readme.txt)

## Lizenz

GPL-2.0+ – siehe https://www.gnu.org/licenses/gpl-2.0.html
