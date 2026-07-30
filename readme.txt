=== SchonGeil Cookie Consent ===
Contributors: schongeil
Tags: cookie consent, GDPR, DSGVO, embed blocking, privacy
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.7
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lean, self-hosted cookie consent plugin that blocks third-party embeds before consent with service-specific placeholders.

== Description ==

SchonGeil Cookie Consent is a lightweight, self-hosted WordPress plugin that blocks embedded third-party content (YouTube, Vimeo, SoundCloud, Bandcamp, hearthis.at, Instagram, Spotify, Mixcloud) until the user gives consent – fully GDPR/DSGVO/TDDDG compliant.

**Key Features:**

* No external server connections – fully self-hosted
* Service-specific placeholders with preview images (YouTube, SoundCloud, Mixcloud, hearthis.at)
* Three equal buttons – no dark patterns
* Polylang integration for multilingual sites (DE/EN)
* Cache-compatible: server-side blocking + client-side consent
* Minimal footprint: JS < 15 KB, CSS < 5 KB
* Accessible: ARIA labels, keyboard navigation, focus management
* Optional consent logging with anonymized IPs
* Built-in embed scanner to find all embeds across your site
* Google Consent Mode v2 support (optional)

== Installation ==

1. Upload the `schongeil-cookie-consent` folder to `/wp-content/plugins/`
2. Activate the plugin through the "Plugins" menu
3. Go to Settings > Cookie Consent to configure

== Changelog ==

= 1.7 =
* Fix: IPv6-Anonymisierung maskiert jetzt auch komprimierte Adressen (z.B. 2001:db8::1) korrekt – binäres Maskieren der letzten 80 Bit via inet_pton
* Fix: Config-Hash wird jetzt NACH dem Speichern der Optionen berechnet (updated_option statt update_option) und umfasst nur noch consent-relevante Einstellungen
* Neu: Banner öffnet sich erneut, wenn sich die Service-/Cookie-Konfiguration seit der gespeicherten Einwilligung geändert hat (Config-Hash-Abgleich im Frontend)
* Fix: Google Consent Mode v2 – Embed-Einwilligung setzt analytics/ad-Signale nicht mehr auf "granted"; die Defaults bleiben "denied" (das Plugin hat keine Analytics-/Ads-Kategorie)
* Fix: Polylang-Übersetzungen der Service-Texte (Platzhalter) greifen jetzt tatsächlich
* Fix: CSV-Export des Consent-Logs berücksichtigt den Datumsfilter
* Fix: Backslashes bei Apostrophen in Cookie-/Service-Formularen (fehlendes wp_unslash)
* Fix: Custom-Link im Banner – Sprach-Fallback gilt jetzt für URL und Text gemeinsam
* Fix: Instagram-URL-Fallback erkennt auch /reel/- und /tv/-Links
* Neu: Log-Aufräumen läuft täglich per WP-Cron statt nur beim Besuch der Einstellungsseite
* Verbesserung: Fehlgeschlagene Thumbnail-Lookups werden 12 h gemerkt (kein wiederholtes Remote-Warten beim Seitenaufbau)
* Verbesserung: Kein globaler Object-Cache-Flush mehr bei jedem Speichern; Hummingbird-Erkennung korrigiert
* Verbesserung: Sprach-Helfer in gemeinsamen Trait zusammengeführt; tote Config-Keys aus dem Inline-Script entfernt (kleineres Markup)
* Verbesserung: Hex-Farb-Validierung erlaubt nur noch gültige CSS-Längen (3/4/6/8)
* Bereinigung: uninstall.php entfernt jetzt auch Thumbnail-Cache, Transients und Cron-Event; ungenutzte Option sgcc_categories entfernt

= 1.6 =
* Neu: PHPUnit-Test-Suite für kritische Regex-Patterns (Service-Erkennung, IP-Anonymisierung, Hex-Farb-Validierung, Embed-Blocking)
* Neu: Custom Services können jetzt bearbeitet und gelöscht werden (CRUD komplett)
* Verbesserung: Custom Services werden in der Services-Tabelle visuell als „custom" gekennzeichnet

= 1.5.1 =
* Fix: Consent-Log schlug fehl wenn IP-Anonymisierung auf Hash stand (SHA-256 = 64 Zeichen, Spalte nur 45)
* Entfernt: IP-Hash-Option komplett entfernt – Truncation ist DSGVO-konform und funktioniert zuverlässig
* Bereinigung: Admin-UI, Aktivierungs-Defaults und uninstall.php von sgcc_consent_log_ip_method bereinigt

= 1.5 =
* Sicherheit: Hex-Farbwert-Validierung bei CSS-Custom-Properties gegen CSS-Injection
* Sicherheit: IP-Ermittlung verwendet jetzt ausschließlich REMOTE_ADDR (kein spoofbares X-Forwarded-For mehr)
* Sicherheit: Zusätzliche absint()-Absicherung bei Log-Retention-Wert
* Fix: Polylang-Übersetzung (pll__) funktioniert jetzt auch in Embed-Platzhaltern (SGCC_Blocker)
* Fix: Aktivierungs-Defaults für sgcc_custom_link_url_de/en und sgcc_floating_icon_side korrigiert
* Fix: CSV-Export berücksichtigt jetzt Datumsfilter
* Fix: Veralteter Versions-Fallback im Frontend-JS entfernt
* Fix: Kinsta Cache-Flush nutzt jetzt korrekte Action statt falscher Klassen-Referenz
* Fix: Consent-Log funktionierte nicht auf gecachten Seiten (Nonce in gecachtem HTML abgelaufen)
* Fix: Consent-Log Request wurde durch sofortiges page reload abgebrochen (jetzt via sendBeacon)
* Neu: Google Consent Mode v2 wird per gtag('consent','update') bei Consent-Änderung aktualisiert
* Verbesserung: Deduplizierte Sprachermittlung im Blocker (DRY-Prinzip)
* Verbesserung: Consent-Log nutzt Origin-Check statt Nonce (caching-kompatibel)
* Bereinigung: Verwaiste Option sgcc_custom_link_url aus uninstall.php entfernt

= 1.3.11 =
* Neu: Shortcode [sgcc_settings] zum Öffnen des Cookie-Einstellungen-Popups
* Nutzung in Menüs, Footern, Seiteninhalten oder Widgets möglich
* Attribute: text (Link-Text), class (CSS-Klassen), tag (a oder button)

= 1.3.10 =
* Neu: Mixcloud-Artwork wird serverseitig gecacht (via oEmbed API → lokaler Cache)
* Neu: hearthis.at-Artwork wird serverseitig gecacht (via oEmbed API → lokaler Cache)
* Verbesserung: Vorschaubilder jetzt für alle Audio-Services mit Cover-Art (YouTube, SoundCloud, Mixcloud, hearthis.at)

= 1.3.9 =
* Fix: SoundCloud-Thumbnails bei URN-Format (soundcloud:tracks:ID) in Gutenberg HTML-Blöcken
* Fix: Erkennung beider SoundCloud-Embed-Formate (numerische Track-ID und URN-kodierte ID)

= 1.3.8 =
* Neu: YouTube-Thumbnails werden serverseitig gecacht (wp-content/uploads/sgcc-thumbnails/)
* Neu: SoundCloud-Artwork wird serverseitig gecacht (via oEmbed API → lokaler Cache)
* Verbesserung: Kein Third-Party-Request an Google oder SoundCloud vor Consent – vollständig DSGVO-konform
* Verbesserung: Gemeinsame Thumbnail-Cache-Infrastruktur für alle Services

= 1.3.7 =
* Neu: Automatischer Cache-Flush bei Änderung von Plugin-Einstellungen
* Neu: Unterstützung für WP Super Cache, W3 Total Cache, WP Rocket, LiteSpeed, Autoptimize, WP Fastest Cache, SG Optimizer u.a.
* Neu: Config-Hash zur Erkennung veralteter gecachter Konfigurationen
* Verbesserung: Plugin vollständig caching-kompatibel

= 1.3.6 =
* Neu: Link zur Datenverarbeitungserklärung im Konfig-Popup
* Änderung: "Datenschutzerklärung" überall in "Datenverarbeitungserklärung" umbenannt
* Änderung: Floating Icon wird nur noch auf Desktop-Geräten angezeigt (Windows, macOS, Linux)

= 1.3.5 =
* Fix: Embeds im Beitrags-Header (Theme-Templates) werden jetzt korrekt geblockt (SoundCloud, Mixcloud etc.)
* Fix: oEmbed-Dataparse prüft jetzt immer iframe-src, auch wenn die Original-URL keinen Service-Match hat
* Verbesserung: Alle Placeholder-Icons einheitlich überarbeitet (konsistenter Stroke-Stil, viewBox 24x24)

= 1.3.4 =
* Neu: Mixcloud-Embeds werden jetzt erkannt und geblockt (Kategorie: Audio)
* Änderung: Kategorie "Video" umbenannt in "Video / Social" (wegen Instagram)

= 1.3.3 =
* Fix: Placeholder-Hintergrund jetzt dunkel (passend zu Dark Themes) – Text bei Vimeo/Services ohne Thumbnail lesbar
* Fix: Placeholder füllt ARVE-Container vollständig aus (keine Lücke mehr unter dem Blocker)

= 1.3.2 =
* Fix: ARVE-kompatible Embeds (z.B. YouTube-Playlists) werden jetzt korrekt angezeigt nach Consent
* Fix: Absolut positionierte iframes (ARVE-Plugin) erhalten korrekte Container-Höhe

= 1.0.0 =
* Initial release
