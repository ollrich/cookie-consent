<?php
/**
 * Services Registry
 *
 * Manages known third-party embed services and their URL patterns.
 * Services are grouped into categories: necessary, audio, video.
 *
 * @package SchonGeil_Cookie_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SGCC_Services {

    private static $default_services = array();

    /**
     * Get default categories.
     */
    public static function get_default_categories() {
        return array(
            'necessary' => array(
                'name_de'  => 'Notwendig',
                'name_en'  => 'Necessary',
                'desc_de'  => 'Notwendige Cookies sind für die Grundfunktionen der Website erforderlich.',
                'desc_en'  => 'Necessary cookies are required for the basic functions of the website.',
                'required' => true,
            ),
            'audio' => array(
                'name_de'  => 'Audio',
                'name_en'  => 'Audio',
                'desc_de'  => 'Erlaubt das Laden von eingebetteten Audio-Inhalten wie Musik-Playern. Beim Laden werden Daten an die jeweiligen Drittanbieter übermittelt.',
                'desc_en'  => 'Allows loading embedded audio content such as music players. Loading transmits data to the respective providers.',
                'required' => false,
            ),
            'video' => array(
                'name_de'  => 'Video / Social',
                'name_en'  => 'Video / Social',
                'desc_de'  => 'Erlaubt das Laden von eingebetteten Video- und Social-Media-Inhalten. Beim Laden werden Daten an die jeweiligen Drittanbieter übermittelt.',
                'desc_en'  => 'Allows loading embedded video and social media content. Loading transmits data to the respective providers.',
                'required' => false,
            ),
        );
    }

    /**
     * Get all default services.
     */
    public static function get_defaults() {
        if ( empty( self::$default_services ) ) {
            self::$default_services = array(
                'youtube' => array(
                    'name'     => 'YouTube',
                    'category' => 'video',
                    'enabled'  => true,
                    'patterns' => array(
                        'youtube.com/embed/',
                        'youtube-nocookie.com/embed/',
                        'youtube.com/watch',
                        'youtu.be/',
                        'youtube.com/v/',
                        'youtube.com/shorts/',
                    ),
                    'oembed_patterns' => array(
                        '#https?://(?:www\.)?youtube\.com/watch\?v=([a-zA-Z0-9_-]+)#i',
                        '#https?://youtu\.be/([a-zA-Z0-9_-]+)#i',
                        '#https?://(?:www\.)?youtube\.com/shorts/([a-zA-Z0-9_-]+)#i',
                    ),
                    'icon'          => 'placeholder-youtube.svg',
                    'thumbnail'     => true,
                    'thumbnail_url' => 'https://img.youtube.com/vi/{VIDEO_ID}/hqdefault.jpg',
                    'texts' => array(
                        'de' => array( 'title' => 'YouTube-Video', 'allow' => 'YouTube-Video zulassen', 'privacy' => 'Beim Laden werden Daten an YouTube übermittelt.', 'load' => 'Inhalt laden', 'always' => 'YouTube immer zulassen' ),
                        'en' => array( 'title' => 'YouTube Video', 'allow' => 'Allow YouTube video', 'privacy' => 'Loading this content transmits data to YouTube.', 'load' => 'Load content', 'always' => 'Always allow YouTube' ),
                    ),
                ),
                'vimeo' => array(
                    'name'     => 'Vimeo',
                    'category' => 'video',
                    'enabled'  => true,
                    'patterns' => array(
                        'player.vimeo.com/video/',
                        'vimeo.com/',
                    ),
                    'oembed_patterns' => array(
                        '#https?://(?:www\.)?vimeo\.com/(\d+)#i',
                        '#https?://player\.vimeo\.com/video/(\d+)#i',
                    ),
                    'icon'      => 'placeholder-vimeo.svg',
                    'thumbnail' => false,
                    'texts' => array(
                        'de' => array( 'title' => 'Vimeo-Video', 'allow' => 'Vimeo-Video zulassen', 'privacy' => 'Beim Laden werden Daten an Vimeo übermittelt.', 'load' => 'Inhalt laden', 'always' => 'Vimeo immer zulassen' ),
                        'en' => array( 'title' => 'Vimeo Video', 'allow' => 'Allow Vimeo video', 'privacy' => 'Loading this content transmits data to Vimeo.', 'load' => 'Load content', 'always' => 'Always allow Vimeo' ),
                    ),
                ),
                'soundcloud' => array(
                    'name'     => 'SoundCloud',
                    'category' => 'audio',
                    'enabled'  => true,
                    'patterns' => array(
                        'w.soundcloud.com/player/',
                        'api.soundcloud.com/',
                    ),
                    'oembed_patterns' => array(
                        '#https?://soundcloud\.com/[a-zA-Z0-9_-]+/[a-zA-Z0-9_-]+#i',
                    ),
                    'icon'      => 'placeholder-soundcloud.svg',
                    'thumbnail' => true,
                    'texts' => array(
                        'de' => array( 'title' => 'SoundCloud-Player', 'allow' => 'SoundCloud-Player zulassen', 'privacy' => 'Beim Laden werden Daten an SoundCloud übermittelt.', 'load' => 'Inhalt laden', 'always' => 'SoundCloud immer zulassen' ),
                        'en' => array( 'title' => 'SoundCloud Player', 'allow' => 'Allow SoundCloud player', 'privacy' => 'Loading this content transmits data to SoundCloud.', 'load' => 'Load content', 'always' => 'Always allow SoundCloud' ),
                    ),
                ),
                'bandcamp' => array(
                    'name'     => 'Bandcamp',
                    'category' => 'audio',
                    'enabled'  => true,
                    'patterns' => array(
                        'bandcamp.com/EmbeddedPlayer/',
                    ),
                    'icon'      => 'placeholder-bandcamp.svg',
                    'thumbnail' => false,
                    'texts' => array(
                        'de' => array( 'title' => 'Bandcamp-Player', 'allow' => 'Bandcamp-Player zulassen', 'privacy' => 'Beim Laden werden Daten an Bandcamp übermittelt.', 'load' => 'Inhalt laden', 'always' => 'Bandcamp immer zulassen' ),
                        'en' => array( 'title' => 'Bandcamp Player', 'allow' => 'Allow Bandcamp player', 'privacy' => 'Loading this content transmits data to Bandcamp.', 'load' => 'Load content', 'always' => 'Always allow Bandcamp' ),
                    ),
                ),
                'hearthis' => array(
                    'name'     => 'hearthis.at',
                    'category' => 'audio',
                    'enabled'  => true,
                    'patterns' => array(
                        'hearthis.at/',
                    ),
                    'icon'      => 'placeholder-hearthis.svg',
                    'thumbnail' => true,
                    'texts' => array(
                        'de' => array( 'title' => 'hearthis.at-Player', 'allow' => 'hearthis.at-Player zulassen', 'privacy' => 'Beim Laden werden Daten an hearthis.at übermittelt.', 'load' => 'Inhalt laden', 'always' => 'hearthis.at immer zulassen' ),
                        'en' => array( 'title' => 'hearthis.at Player', 'allow' => 'Allow hearthis.at player', 'privacy' => 'Loading this content transmits data to hearthis.at.', 'load' => 'Load content', 'always' => 'Always allow hearthis.at' ),
                    ),
                ),
                'instagram' => array(
                    'name'     => 'Instagram',
                    'category' => 'video',
                    'enabled'  => true,
                    'patterns' => array(
                        'instagram.com/p/',
                        'instagram.com/reel/',
                        'instagram.com/embed',
                    ),
                    'icon'      => 'placeholder-instagram.svg',
                    'thumbnail' => false,
                    'texts' => array(
                        'de' => array( 'title' => 'Instagram-Beitrag', 'allow' => 'Instagram-Beitrag zulassen', 'privacy' => 'Beim Laden werden Daten an Instagram übermittelt.', 'load' => 'Inhalt laden', 'always' => 'Instagram immer zulassen' ),
                        'en' => array( 'title' => 'Instagram Post', 'allow' => 'Allow Instagram post', 'privacy' => 'Loading this content transmits data to Instagram.', 'load' => 'Load content', 'always' => 'Always allow Instagram' ),
                    ),
                ),
                'spotify' => array(
                    'name'     => 'Spotify',
                    'category' => 'audio',
                    'enabled'  => true,
                    'patterns' => array(
                        'open.spotify.com/embed/',
                    ),
                    'oembed_patterns' => array(
                        '#https?://open\.spotify\.com/(?:track|album|playlist|episode)/[a-zA-Z0-9]+#i',
                    ),
                    'icon'      => 'placeholder-spotify.svg',
                    'thumbnail' => false,
                    'texts' => array(
                        'de' => array( 'title' => 'Spotify-Player', 'allow' => 'Spotify-Player zulassen', 'privacy' => 'Beim Laden werden Daten an Spotify übermittelt.', 'load' => 'Inhalt laden', 'always' => 'Spotify immer zulassen' ),
                        'en' => array( 'title' => 'Spotify Player', 'allow' => 'Allow Spotify player', 'privacy' => 'Loading this content transmits data to Spotify.', 'load' => 'Load content', 'always' => 'Always allow Spotify' ),
                    ),
                ),
                'mixcloud' => array(
                    'name'     => 'Mixcloud',
                    'category' => 'audio',
                    'enabled'  => true,
                    'patterns' => array(
                        'player-widget.mixcloud.com/',
                        'mixcloud.com/widget/',
                    ),
                    'oembed_patterns' => array(
                        '#https?://(?:www\.)?mixcloud\.com/[a-zA-Z0-9_-]+/[a-zA-Z0-9_-]+#i',
                    ),
                    'icon'      => 'placeholder-mixcloud.svg',
                    'thumbnail' => true,
                    'texts' => array(
                        'de' => array( 'title' => 'Mixcloud-Player', 'allow' => 'Mixcloud-Player zulassen', 'privacy' => 'Beim Laden werden Daten an Mixcloud übermittelt.', 'load' => 'Inhalt laden', 'always' => 'Mixcloud immer zulassen' ),
                        'en' => array( 'title' => 'Mixcloud Player', 'allow' => 'Allow Mixcloud player', 'privacy' => 'Loading this content transmits data to Mixcloud.', 'load' => 'Load content', 'always' => 'Always allow Mixcloud' ),
                    ),
                ),
            );
        }

        return self::$default_services;
    }

    public static function get_all() {
        $defaults  = self::get_defaults();
        $overrides = get_option( 'sgcc_services', array() );
        $custom    = get_option( 'sgcc_custom_services', array() );

        foreach ( $overrides as $key => $override ) {
            if ( isset( $defaults[ $key ] ) ) {
                $defaults[ $key ] = wp_parse_args( $override, $defaults[ $key ] );
            }
        }

        foreach ( $custom as $key => $service ) {
            if ( ! isset( $defaults[ $key ] ) ) {
                $defaults[ $key ] = wp_parse_args( $service, array(
                    'name' => '', 'category' => 'video', 'enabled' => true, 'patterns' => array(),
                    'icon' => 'placeholder-generic.svg', 'thumbnail' => false,
                    'texts' => array(
                        'de' => array( 'title' => '', 'allow' => '', 'privacy' => '', 'load' => 'Inhalt laden', 'always' => '' ),
                        'en' => array( 'title' => '', 'allow' => '', 'privacy' => '', 'load' => 'Load content', 'always' => '' ),
                    ),
                ) );
            }
        }

        return $defaults;
    }

    public static function get_enabled() {
        return array_filter( self::get_all(), function ( $s ) { return ! empty( $s['enabled'] ); } );
    }

    public static function get_enabled_by_category() {
        $services = self::get_enabled();
        $grouped  = array();
        foreach ( $services as $key => $service ) {
            $cat = $service['category'] ?? 'video';
            $grouped[ $cat ][ $key ] = $service;
        }
        return $grouped;
    }

    public static function identify_service( $url ) {
        $services = self::get_enabled();
        foreach ( $services as $key => $service ) {
            foreach ( $service['patterns'] as $pattern ) {
                if ( false !== strpos( $url, $pattern ) ) {
                    return $key;
                }
            }
        }
        return false;
    }

    public static function get_youtube_video_id( $url ) {
        $patterns = array(
            '/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/',
            '/youtube-nocookie\.com\/embed\/([a-zA-Z0-9_-]+)/',
            '/youtu\.be\/([a-zA-Z0-9_-]+)/',
            '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/',
            '/youtube\.com\/shorts\/([a-zA-Z0-9_-]+)/',
            '/youtube\.com\/v\/([a-zA-Z0-9_-]+)/',
        );
        foreach ( $patterns as $pattern ) {
            if ( preg_match( $pattern, $url, $matches ) ) {
                return $matches[1];
            }
        }
        return false;
    }

    public static function get_thumbnail_url( $service_key, $embed_url ) {
        $services = self::get_all();
        if ( ! isset( $services[ $service_key ] ) || empty( $services[ $service_key ]['thumbnail'] ) ) {
            return false;
        }
        if ( 'youtube' === $service_key ) {
            $video_id = self::get_youtube_video_id( $embed_url );
            if ( $video_id ) {
                return self::get_local_youtube_thumbnail( $video_id );
            }
        }
        if ( 'soundcloud' === $service_key ) {
            return self::get_local_soundcloud_thumbnail( $embed_url );
        }
        if ( 'mixcloud' === $service_key ) {
            return self::get_local_mixcloud_thumbnail( $embed_url );
        }
        if ( 'hearthis' === $service_key ) {
            return self::get_local_hearthis_thumbnail( $embed_url );
        }
        return false;
    }

    /* ==================================================================
       Thumbnail Cache Infrastructure
       ==================================================================
       All thumbnails are cached locally in wp-content/uploads/sgcc-thumbnails/
       so that visitors never make third-party requests before consent.
       ================================================================== */

    /**
     * Ensure the thumbnail cache directory exists.
     *
     * @return array { 'dir' => string, 'url' => string } or false on failure.
     */
    private static function get_cache_paths() {
        $upload_dir = wp_upload_dir();
        $cache_dir  = $upload_dir['basedir'] . '/sgcc-thumbnails';
        $cache_url  = $upload_dir['baseurl'] . '/sgcc-thumbnails';

        if ( ! file_exists( $cache_dir ) ) {
            wp_mkdir_p( $cache_dir );
            $index = $cache_dir . '/index.php';
            if ( ! file_exists( $index ) ) {
                file_put_contents( $index, '<?php // Silence is golden.' );
            }
        }

        return array( 'dir' => $cache_dir, 'url' => $cache_url );
    }

    /**
     * Download a remote image and store it in the thumbnail cache.
     *
     * @param string $remote_url URL to download.
     * @param string $file_path  Local file path to save to.
     * @param int    $min_size   Minimum byte size to accept (filters placeholder images).
     * @return bool True on success.
     */
    private static function download_and_cache( $remote_url, $file_path, $min_size = 1000 ) {
        $response = wp_remote_get( $remote_url, array(
            'timeout'   => 15,
            'sslverify' => true,
        ) );

        // Retry without strict SSL if it failed (some CDN certs mismatch on shared hosts).
        if ( is_wp_error( $response ) ) {
            $response = wp_remote_get( $remote_url, array(
                'timeout'   => 15,
                'sslverify' => false,
            ) );
        }

        if ( is_wp_error( $response ) ) {
            return false;
        }
        if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        if ( empty( $body ) || strlen( $body ) < $min_size ) {
            return false;
        }

        $content_type = wp_remote_retrieve_header( $response, 'content-type' );
        if ( false === strpos( $content_type, 'image/' ) ) {
            return false;
        }

        return false !== file_put_contents( $file_path, $body );
    }

    /**
     * Get a locally cached YouTube thumbnail.
     *
     * @param string $video_id The YouTube video ID.
     * @return string|false     Local URL to the thumbnail, or false on failure.
     */
    public static function get_local_youtube_thumbnail( $video_id ) {
        $video_id = preg_replace( '/[^a-zA-Z0-9_-]/', '', $video_id );
        if ( empty( $video_id ) ) {
            return false;
        }

        $cache     = self::get_cache_paths();
        $file_path = $cache['dir'] . '/yt-' . $video_id . '.jpg';
        $file_url  = $cache['url'] . '/yt-' . $video_id . '.jpg';

        if ( file_exists( $file_path ) ) {
            return $file_url;
        }

        // Try hqdefault first, fall back to default.
        $qualities = array( 'hqdefault', 'default' );
        foreach ( $qualities as $quality ) {
            $remote_url = 'https://img.youtube.com/vi/' . $video_id . '/' . $quality . '.jpg';
            if ( self::download_and_cache( $remote_url, $file_path ) ) {
                return $file_url;
            }
        }

        return false;
    }

    /**
     * Get a locally cached SoundCloud thumbnail via oEmbed API.
     *
     * Extracts the track/playlist URL from the iframe src parameter,
     * queries SoundCloud's oEmbed endpoint for the artwork URL,
     * then downloads and caches the image locally.
     *
     * @param string $embed_url The iframe src (e.g. w.soundcloud.com/player/?url=...).
     * @return string|false     Local URL to the thumbnail, or false on failure.
     */
    public static function get_local_soundcloud_thumbnail( $embed_url ) {
        // Extract the track/playlist URL from the iframe's url= parameter.
        $track_url = self::extract_soundcloud_track_url( $embed_url );
        if ( ! $track_url ) {
            return false;
        }

        // Create a stable filename from the track URL.
        $hash      = md5( $track_url );
        $cache     = self::get_cache_paths();
        $file_path = $cache['dir'] . '/sc-' . $hash . '.jpg';
        $file_url  = $cache['url'] . '/sc-' . $hash . '.jpg';

        if ( file_exists( $file_path ) ) {
            return $file_url;
        }

        // Query SoundCloud oEmbed API for the thumbnail URL.
        $oembed_url = 'https://soundcloud.com/oembed?format=json&url=' . rawurlencode( $track_url );
        $response   = wp_remote_get( $oembed_url, array(
            'timeout'   => 8,
            'sslverify' => true,
        ) );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return false;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $data['thumbnail_url'] ) ) {
            return false;
        }

        // SoundCloud returns e.g. ...t500x500.jpg – use it directly.
        $artwork_url = $data['thumbnail_url'];

        if ( self::download_and_cache( $artwork_url, $file_path, 500 ) ) {
            return $file_url;
        }

        return false;
    }

    /**
     * Extract the original track/playlist URL from a SoundCloud player iframe src.
     *
     * Iframe src formats:
     *   - url=https%3A//api.soundcloud.com/tracks/620411685         (numeric ID – works with oEmbed)
     *   - url=https%3A//api.soundcloud.com/tracks/soundcloud%3Atracks%3A2225...  (URN format – needs conversion)
     *   - url=https%3A//soundcloud.com/user/track-name               (user URL – works with oEmbed)
     *
     * @param string $iframe_src The iframe src URL.
     * @return string|false       A URL suitable for the SoundCloud oEmbed API, or false.
     */
    private static function extract_soundcloud_track_url( $iframe_src ) {
        // Decode HTML entities (e.g. &amp; → &) that may survive from raw HTML extraction.
        $iframe_src = html_entity_decode( $iframe_src );

        // Parse the iframe URL query string.
        $parsed = wp_parse_url( $iframe_src );
        if ( empty( $parsed['query'] ) ) {
            return false;
        }
        parse_str( $parsed['query'], $params );

        if ( empty( $params['url'] ) ) {
            return false;
        }

        $url = $params['url'];

        // Handle URN-encoded format: api.soundcloud.com/tracks/soundcloud%3Atracks%3A12345
        // or already decoded: api.soundcloud.com/tracks/soundcloud:tracks:12345
        // Convert to: api.soundcloud.com/tracks/12345
        if ( preg_match( '#api\.soundcloud\.com/tracks/soundcloud[:%]3Atracks[:%]3A(\d+)#i', $url, $urn_match ) ) {
            return 'https://api.soundcloud.com/tracks/' . $urn_match[1];
        }

        // Handle URN format for playlists: soundcloud:playlists:12345
        if ( preg_match( '#api\.soundcloud\.com/playlists/soundcloud[:%]3Aplaylists[:%]3A(\d+)#i', $url, $urn_match ) ) {
            return 'https://api.soundcloud.com/playlists/' . $urn_match[1];
        }

        return $url;
    }

    /**
     * Get a locally cached Mixcloud thumbnail via oEmbed API.
     *
     * Extracts the show URL from the iframe's feed= parameter,
     * queries Mixcloud's oEmbed endpoint for the artwork URL,
     * then downloads and caches the image locally.
     *
     * @param string $embed_url The iframe src (e.g. player-widget.mixcloud.com/widget/iframe/?feed=...).
     * @return string|false     Local URL to the thumbnail, or false on failure.
     */
    public static function get_local_mixcloud_thumbnail( $embed_url ) {
        $show_url = self::extract_mixcloud_show_url( $embed_url );
        if ( ! $show_url ) {
            return false;
        }

        $hash      = md5( $show_url );
        $cache     = self::get_cache_paths();
        $file_path = $cache['dir'] . '/mc-' . $hash . '.jpg';
        $file_url  = $cache['url'] . '/mc-' . $hash . '.jpg';

        if ( file_exists( $file_path ) ) {
            return $file_url;
        }

        // Query Mixcloud oEmbed API for the thumbnail URL.
        // Use app.mixcloud.com directly (www.mixcloud.com redirects to app.mixcloud.com).
        $oembed_url = 'https://app.mixcloud.com/oembed/?format=json&url=' . rawurlencode( $show_url );
        $response   = wp_remote_get( $oembed_url, array(
            'timeout'   => 10,
            'sslverify' => true,
        ) );

        // Fallback to www if app endpoint fails.
        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            $oembed_url = 'https://www.mixcloud.com/oembed/?format=json&url=' . rawurlencode( $show_url );
            $response   = wp_remote_get( $oembed_url, array(
                'timeout'   => 10,
                'sslverify' => true,
            ) );
        }

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return false;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $data['image'] ) ) {
            return false;
        }

        if ( self::download_and_cache( $data['image'], $file_path, 500 ) ) {
            return $file_url;
        }

        return false;
    }

    /**
     * Extract the show URL from a Mixcloud player iframe src.
     *
     * Iframe src format:
     *   https://player-widget.mixcloud.com/widget/iframe/?hide_cover=1&feed=%2Fusername%2Fshow-name%2F
     *
     * @param string $iframe_src The iframe src URL.
     * @return string|false       A full Mixcloud show URL, or false.
     */
    private static function extract_mixcloud_show_url( $iframe_src ) {
        // Decode HTML entities (e.g. &amp; → &) that may survive from raw HTML extraction.
        $iframe_src = html_entity_decode( $iframe_src );

        $parsed = wp_parse_url( $iframe_src );
        if ( empty( $parsed['query'] ) ) {
            return false;
        }
        parse_str( $parsed['query'], $params );

        if ( empty( $params['feed'] ) ) {
            return false;
        }

        $feed = $params['feed'];

        // If the feed is already a full URL (oEmbed format), use it directly.
        if ( preg_match( '#^https?://#i', $feed ) ) {
            return $feed;
        }

        // Relative path: ensure it starts with a slash and prepend domain.
        if ( '/' !== $feed[0] ) {
            $feed = '/' . $feed;
        }

        return 'https://www.mixcloud.com' . $feed;
    }

    /**
     * Get a locally cached hearthis.at thumbnail via oEmbed API.
     *
     * Uses the embed URL to query hearthis.at's oEmbed endpoint
     * for the artwork URL, then downloads and caches the image locally.
     *
     * @param string $embed_url The iframe src (e.g. hearthis.at/embed/12345/).
     * @return string|false     Local URL to the thumbnail, or false on failure.
     */
    public static function get_local_hearthis_thumbnail( $embed_url ) {
        $track_url = self::extract_hearthis_track_url( $embed_url );
        if ( ! $track_url ) {
            return false;
        }

        $hash      = md5( $track_url );
        $cache     = self::get_cache_paths();
        $file_path = $cache['dir'] . '/ht-' . $hash . '.jpg';
        $file_url  = $cache['url'] . '/ht-' . $hash . '.jpg';

        if ( file_exists( $file_path ) ) {
            return $file_url;
        }

        // Query hearthis.at oEmbed API for the thumbnail URL.
        $oembed_url = 'https://hearthis.at/oembed/?format=json&url=' . rawurlencode( $track_url );
        $response   = wp_remote_get( $oembed_url, array(
            'timeout'   => 8,
            'sslverify' => true,
        ) );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return false;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $data['thumbnail_url'] ) ) {
            return false;
        }

        if ( self::download_and_cache( $data['thumbnail_url'], $file_path, 500 ) ) {
            return $file_url;
        }

        return false;
    }

    /**
     * Extract a usable track URL from a hearthis.at embed iframe src.
     *
     * Iframe src formats:
     *   - https://hearthis.at/embed/12345/
     *   - https://app.hearthis.at/embed/12345/?...
     *   - https://hearthis.at/username/track-name/embed/
     *
     * For numeric embed IDs, constructs: https://hearthis.at/embed/12345/
     * For path-based embeds, extracts the track URL.
     *
     * @param string $iframe_src The iframe src URL.
     * @return string|false       A URL suitable for the hearthis.at oEmbed API, or false.
     */
    private static function extract_hearthis_track_url( $iframe_src ) {
        // Decode HTML entities (e.g. &amp; → &) that may survive from raw HTML extraction.
        $iframe_src = html_entity_decode( $iframe_src );

        // Strip query string for cleaner matching.
        $url = preg_replace( '/\?.*$/', '', $iframe_src );

        // Format: hearthis.at/username/track-name/embed/ → hearthis.at/username/track-name/
        if ( preg_match( '#hearthis\.at/([a-zA-Z0-9_-]+/[a-zA-Z0-9_-]+)/embed/?#i', $url, $match ) ) {
            return 'https://hearthis.at/' . $match[1] . '/';
        }

        // Format: hearthis.at/embed/12345/ → use directly as oEmbed input.
        if ( preg_match( '#hearthis\.at/embed/(\d+)/?#i', $url, $match ) ) {
            return 'https://hearthis.at/embed/' . $match[1] . '/';
        }

        // Fallback: return cleaned URL.
        if ( false !== strpos( $url, 'hearthis.at' ) ) {
            return $url;
        }

        return false;
    }

    public static function get_icon_url( $service_key ) {
        $services = self::get_all();
        $icon = isset( $services[ $service_key ]['icon'] ) ? $services[ $service_key ]['icon'] : 'placeholder-generic.svg';
        return SGCC_PLUGIN_URL . 'assets/images/' . $icon;
    }

    public static function get_service_texts( $service_key ) {
        $services = self::get_all();
        $lang     = 'de';
        if ( function_exists( 'pll_current_language' ) ) {
            $current = pll_current_language( 'slug' );
            $lang    = ( 'en' === $current ) ? 'en' : 'de';
        }
        if ( isset( $services[ $service_key ]['texts'][ $lang ] ) ) {
            return $services[ $service_key ]['texts'][ $lang ];
        }
        if ( isset( $services[ $service_key ]['texts']['de'] ) ) {
            return $services[ $service_key ]['texts']['de'];
        }
        return array( 'title' => $services[ $service_key ]['name'] ?? '', 'allow' => '', 'privacy' => '', 'load' => 'Inhalt laden', 'always' => '' );
    }
}
