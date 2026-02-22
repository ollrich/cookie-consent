# Cookie Consent

Ein schlankes, selbst-gehostetes WordPress-Cookie-Consent-Plugin, das Drittanbieter-Embeds vor der Zustimmung blockiert – vollständig DSGVO/GDPR/TDDDG-konform.

> **Hinweis:** Dieses Plugin befindet sich in aktiver Entwicklung und kann noch Fehler enthalten. Der Einsatz auf Produktivsystemen erfolgt auf eigene Verantwortung. Gefundene Bugs bitte als [Issue](../../issues) melden.

## Features

- **Kein externer Server-Kontakt** – vollständig self-hosted, keine Abhängigkeiten zu externen Consent-Diensten
- **Service-spezifische Platzhalter** mit Vorschaubildern (YouTube, SoundCloud, Mixcloud, hearthis.at) – gecacht ohne Third-Party-Request vor Consent
- **Drei gleichwertige Buttons** – keine Dark Patterns (Ablehnen, Nur Notwendige, Alle akzeptieren, Einstellungen)
- **Granulare Zustimmung** – pro Service oder Kategorie (Notwendig / Audio / Video)
- **Polylang-Integration** für mehrsprachige Websites (DE/EN) inkl. sprachspezifischer Datenschutzseiten
- **Cache-kompatibel** – serverseitiges Blockieren + clientseitige Consent-Prüfung; automatischer Cache-Flush bei Einstellungsänderungen
- **Minimaler Footprint** – JS < 15 KB, CSS < 5 KB (minifiziert), kein jQuery
- **Barrierefrei** – ARIA-Labels, Tastaturnavigation, Fokus-Management
- **Optionales Consent-Logging** – anonymisierte IPs (IPv4-Kürzung / IPv6-Maskierung), CSV-Export
- **Eingebetteter Embed-Scanner** – findet alle Embeds auf der Seite
- **Google Consent Mode v2** – optionale Integration via `gtag('consent','update')`
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

WP Super Cache, W3 Total Cache, WP Rocket, LiteSpeed Cache, Autoptimize, WP Fastest Cache, SG Optimizer, Kinsta

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
| **Allgemein** | Aktivierung, Cookie-Lebensdauer, Datenschutzseiten (DE/EN), Banner-Position, Floating Icon, GCM v2 |
| **Services & Blocking** | Services aktivieren/deaktivieren, eigene Services mit URL-Mustern anlegen |
| **Cookies** | CRUD-Interface für individuelle Cookies mit Name, Anbieter, Kategorie, Beschreibung (DE/EN), Dauer, Typ |
| **Texte & Design** | UI-Texte (Banner, Popup, Platzhalter) in DE/EN; Farbwähler für alle UI-Elemente |
| **Consent-Log** | Anonymisierte Einwilligungen einsehen, als CSV exportieren, löschen |

## Shortcode

```
[sgcc_settings]
[sgcc_settings text="Cookie-Einstellungen" class="my-link" tag="button"]
```

Attribute: `text` (Link-Text), `class` (CSS-Klassen), `tag` (`a` oder `button`)

## Datenbankschema

Optionen werden in `wp_options` gespeichert (Präfix: `sgcc_`).
Optionales Consent-Log: eigene Tabelle `wp_sgcc_consent_log` (wird bei Deinstallation entfernt).

## Sicherheit

- Hex-Farbvalidierung gegen CSS-Injection
- Nonce-Schutz auf allen Admin-Formularen
- IP-Anonymisierung: IPv4 letztes Oktett abgeschnitten, IPv6 letzte 80 Bit maskiert
- Kein `X-Forwarded-For` (nur `REMOTE_ADDR`)
- Consent-Log via `navigator.sendBeacon()` + Origin-Check (nonce-frei, caching-kompatibel)
- Alle Ausgaben: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_json_encode()`

## Entwicklung

```bash
npm install
npm run build   # minifiziert JS und CSS
```

**Anforderungen für Build:** Node.js mit `terser` und `clean-css-cli` (siehe `package.json`).

## Changelog

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
