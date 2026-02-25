<?php
/**
 * Embed Blocker
 *
 * Filters content to block third-party embeds before consent.
 * Uses server-side content filtering for cache compatibility.
 *
 * @package SchonGeil_Cookie_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SGCC_Blocker {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        if ( ! get_option( 'sgcc_enabled', 1 ) ) {
            return;
        }

        // Filter the_content at very late priority (after oEmbeds etc.).
        add_filter( 'the_content', array( $this, 'filter_content' ), 999 );

        // Filter oEmbed HTML – catches oEmbeds within the_content via [embed] shortcode.
        add_filter( 'embed_oembed_html', array( $this, 'filter_oembed' ), 999, 4 );

        // Filter widget text.
        add_filter( 'widget_text', array( $this, 'filter_content' ), 999 );

        // Also filter the video embed handler output (wp_video_shortcode).
        add_filter( 'embed_handler_html', array( $this, 'filter_embed_handler' ), 999, 3 );

        // Filter oEmbed data→HTML conversion – catches ALL wp_oembed_get() calls,
        // including direct calls from themes (e.g. featured video areas).
        // This is essential for themes like Rosana that call wp_oembed_get() directly.
        add_filter( 'oembed_dataparse', array( $this, 'filter_oembed_dataparse' ), 999, 3 );
    }

    /**
     * Filter oEmbed HTML at the data→HTML conversion stage.
     *
     * This catches ALL calls to wp_oembed_get(), including direct calls from
     * theme templates that render video embeds outside the_content filter.
     *
     * @param string $return The HTML result of the oEmbed.
     * @param object $data   The oEmbed response data.
     * @param string $url    The oEmbed URL.
     * @return string Filtered HTML with blocked placeholder if applicable.
     */
    public function filter_oembed_dataparse( $return, $data, $url ) {
        if ( is_admin() || empty( $return ) ) {
            return $return;
        }

        // Skip if already blocked by our plugin.
        if ( false !== strpos( $return, 'data-sgcc-src' ) || false !== strpos( $return, 'sgcc-blocked-iframe' ) ) {
            return $return;
        }

        // Check if the URL matches a known service.
        $service_key = SGCC_Services::identify_service( $url );

        // If the output contains an iframe, always try to block it –
        // even if the oEmbed URL didn't match (the iframe src may match).
        // This is essential for SoundCloud, Mixcloud etc. where the oEmbed URL
        // (e.g. soundcloud.com/user/track) differs from the iframe src
        // (e.g. w.soundcloud.com/player/...).
        if ( false !== strpos( $return, '<iframe' ) ) {
            return preg_replace_callback(
                '/<iframe\b[^>]*>.*?<\/iframe>/is',
                array( $this, 'replace_iframe' ),
                $return
            );
        }

        // For non-iframe content, we need a service match.
        if ( false === $service_key ) {
            return $return;
        }

        // For non-iframe oEmbeds (e.g., Instagram blockquote), wrap them.
        return $this->create_placeholder_for_oembed( $return, $url, $service_key );
    }

    /**
     * Filter content to replace iframes and known embed markup with placeholders.
     */
    public function filter_content( $content ) {
        if ( is_admin() || empty( $content ) ) {
            return $content;
        }

        // 1. Block Instagram blockquotes (pasted as raw HTML, not oEmbed).
        $content = preg_replace_callback(
            '/<blockquote[^>]*class=["\'][^"\']*instagram-media[^"\']*["\'][^>]*>.*?<\/blockquote>\s*(?:<script[^>]*instagram\.com\/embed\.js[^>]*><\/script>)?/is',
            array( $this, 'replace_instagram_blockquote' ),
            $content
        );

        // 2. Remove any remaining standalone Instagram embed.js scripts (already handled above or orphaned).
        $content = preg_replace( '/<script[^>]*instagram\.com\/embed\.js[^>]*><\/script>/is', '', $content );

        // 3. Match all iframe tags.
        $pattern = '/<iframe\b[^>]*>.*?<\/iframe>/is';
        return preg_replace_callback( $pattern, array( $this, 'replace_iframe' ), $content );
    }

    /**
     * Replace an Instagram blockquote embed with a placeholder.
     */
    private function replace_instagram_blockquote( $matches ) {
        $html = $matches[0];

        // Extract Instagram URL from data-instgrm-permalink.
        $url = '';
        if ( preg_match( '/data-instgrm-permalink=["\']([^"\']+)["\']/i', $html, $url_match ) ) {
            $url = $url_match[1];
        } elseif ( preg_match( '/href=["\']([^"\']*instagram\.com\/p\/[^"\']+)["\']/i', $html, $url_match ) ) {
            $url = $url_match[1];
        }

        $service_key = 'instagram';
        // Use base64 encoding for HTML to avoid double-encoding issues with esc_attr().
        $blocked_html = '<div class="sgcc-blocked-embed" data-sgcc-service="' . esc_attr( $service_key ) . '" data-sgcc-html-b64="' . base64_encode( $html ) . '" data-sgcc-url="' . esc_attr( $url ) . '" style="display:none;"></div>';

        return $this->build_placeholder( $blocked_html, $service_key, $url, '100%', '480', null, null );
    }

    /**
     * Filter oEmbed HTML output – catches YouTube, Vimeo, etc.
     */
    public function filter_oembed( $html, $url, $attr, $post_id ) {
        if ( is_admin() ) {
            return $html;
        }

        // Check if the oEmbed URL matches any known service.
        $service_key = SGCC_Services::identify_service( $url );
        if ( false === $service_key ) {
            return $html;
        }

        // If the output contains an iframe, filter it.
        if ( false !== strpos( $html, '<iframe' ) ) {
            return preg_replace_callback(
                '/<iframe\b[^>]*>.*?<\/iframe>/is',
                array( $this, 'replace_iframe' ),
                $html
            );
        }

        // For non-iframe oEmbeds (e.g., Instagram blockquote), wrap them.
        return $this->create_placeholder_for_oembed( $html, $url, $service_key );
    }

    /**
     * Filter embed handler HTML.
     */
    public function filter_embed_handler( $html, $url, $attr ) {
        if ( is_admin() ) {
            return $html;
        }

        $service_key = SGCC_Services::identify_service( $url );
        if ( false === $service_key ) {
            return $html;
        }

        if ( false !== strpos( $html, '<iframe' ) ) {
            return preg_replace_callback(
                '/<iframe\b[^>]*>.*?<\/iframe>/is',
                array( $this, 'replace_iframe' ),
                $html
            );
        }

        return $html;
    }

    /**
     * Replace a single iframe with a placeholder.
     */
    private function replace_iframe( $matches ) {
        $iframe_html = $matches[0];

        // Extract src attribute.
        if ( ! preg_match( '/src=["\']([^"\']+)["\']/i', $iframe_html, $src_match ) ) {
            return $iframe_html;
        }

        $src = $src_match[1];
        $service_key = SGCC_Services::identify_service( $src );

        if ( false === $service_key ) {
            return $iframe_html;
        }

        // Extract dimensions.
        $width  = $this->extract_attribute( $iframe_html, 'width' );
        $height = $this->extract_attribute( $iframe_html, 'height' );

        // Also extract style dimensions if present.
        $style_width  = null;
        $style_height = null;
        if ( preg_match( '/style=["\']([^"\']*)["\']/', $iframe_html, $style_match ) ) {
            if ( preg_match( '/width:\s*([^;]+)/', $style_match[1], $sw ) ) {
                $style_width = trim( $sw[1] );
            }
            if ( preg_match( '/height:\s*([^;]+)/', $style_match[1], $sh ) ) {
                $style_height = trim( $sh[1] );
            }
        }

        // Rewrite the iframe: src -> data-sgcc-src.
        $blocked_iframe = preg_replace(
            '/\bsrc=["\']([^"\']+)["\']/i',
            'data-sgcc-src="' . esc_attr( $src ) . '"',
            $iframe_html
        );

        // Add blocking classes.
        if ( preg_match( '/class=["\']([^"\']*)["\']/', $blocked_iframe ) ) {
            $blocked_iframe = preg_replace(
                '/class=["\']([^"\']*)["\']/',
                'class="$1 sgcc-blocked-iframe"',
                $blocked_iframe
            );
        } else {
            $blocked_iframe = str_replace( '<iframe', '<iframe class="sgcc-blocked-iframe"', $blocked_iframe );
        }

        $blocked_iframe = str_replace(
            '<iframe',
            '<iframe data-sgcc-service="' . esc_attr( $service_key ) . '"',
            $blocked_iframe
        );

        return $this->build_placeholder( $blocked_iframe, $service_key, $src, $width, $height, $style_width, $style_height );
    }

    /**
     * Create placeholder for non-iframe oEmbed content.
     */
    private function create_placeholder_for_oembed( $html, $url, $service_key ) {
        // Use base64 encoding for HTML to avoid double-encoding issues with esc_attr().
        $blocked_html = '<div class="sgcc-blocked-embed" data-sgcc-service="' . esc_attr( $service_key ) . '" data-sgcc-html-b64="' . base64_encode( $html ) . '" data-sgcc-url="' . esc_attr( $url ) . '" style="display:none;"></div>';
        return $this->build_placeholder( $blocked_html, $service_key, $url, '100%', '450', null, null );
    }

    /**
     * Build the placeholder HTML wrapper.
     * CRITICAL: The placeholder inherits the FULL width of its parent and uses
     * the iframe's aspect ratio to determine height, to ensure proper layout.
     */
    private function build_placeholder( $blocked_content, $service_key, $original_src, $width, $height, $style_width = null, $style_height = null ) {
        $services    = SGCC_Services::get_all();
        $service     = $services[ $service_key ] ?? array();
        $texts       = SGCC_Services::get_service_texts( $service_key );
        $icon_url    = SGCC_Services::get_icon_url( $service_key );
        $thumbnail   = SGCC_Services::get_thumbnail_url( $service_key, $original_src );
        $privacy_url = $this->get_privacy_page_url();

        // Build inline style – ALWAYS use 100% width to match content area.
        $style_parts = array( 'width:100%', 'max-width:100%' );

        // Determine height: use style height, attribute height, or a sensible default.
        if ( $style_height ) {
            $style_parts[] = 'min-height:' . $style_height;
        } elseif ( $height ) {
            $h = is_numeric( $height ) ? $height . 'px' : $height;
            $style_parts[] = 'min-height:' . $h;
        } else {
            $style_parts[] = 'min-height:300px';
        }

        $style = implode( ';', $style_parts );

        $privacy_link_text = $this->get_text( 'privacy_link', 'Datenverarbeitungserklärung', 'Privacy policy' );
        $load_text   = $texts['load'] ?? $this->get_text( 'load', 'Inhalt laden', 'Load content' );
        $always_text = $texts['always'] ?? '';
        $privacy_notice = $texts['privacy'] ?? '';
        $title_text  = $texts['title'] ?? ( $service['name'] ?? '' );

        ob_start();
        ?>
        <div class="sgcc-placeholder" data-sgcc-service="<?php echo esc_attr( $service_key ); ?>" data-sgcc-category="<?php echo esc_attr( $service['category'] ?? 'video' ); ?>" style="<?php echo esc_attr( $style ); ?>">
            <?php if ( $thumbnail ) : ?>
                <div class="sgcc-placeholder__thumbnail" style="background-image:url('<?php echo esc_url( $thumbnail ); ?>')"></div>
            <?php endif; ?>
            <div class="sgcc-placeholder__overlay">
                <div class="sgcc-placeholder__content">
                    <img class="sgcc-placeholder__icon" src="<?php echo esc_url( $icon_url ); ?>" alt="<?php echo esc_attr( $title_text ); ?>" width="48" height="48" loading="lazy" />
                    <h4 class="sgcc-placeholder__title"><?php echo esc_html( $title_text ); ?></h4>
                    <?php if ( $privacy_notice ) : ?>
                        <p class="sgcc-placeholder__notice"><?php echo esc_html( $privacy_notice ); ?></p>
                    <?php endif; ?>
                    <button class="sgcc-placeholder__load-btn" type="button" data-sgcc-action="load-single">
                        <?php echo esc_html( $load_text ); ?>
                    </button>
                    <?php if ( $always_text ) : ?>
                        <label class="sgcc-placeholder__always">
                            <input type="checkbox" class="sgcc-placeholder__always-checkbox" data-sgcc-action="load-always" />
                            <span><?php echo esc_html( $always_text ); ?></span>
                        </label>
                    <?php endif; ?>
                    <?php if ( $privacy_url ) : ?>
                        <a class="sgcc-placeholder__privacy-link" href="<?php echo esc_url( $privacy_url ); ?>"><?php echo esc_html( $privacy_link_text ); ?></a>
                    <?php endif; ?>
                    <?php if ( 'instagram' === $service_key ) : ?>
                        <?php
                        $tp_hint = ( 'en' === $this->get_current_lang() )
                            ? 'If the post does not display after unblocking, check your browser\'s Enhanced Tracking Protection settings.'
                            : 'Sollte der Beitrag nach dem Entblocken nicht angezeigt werden, prüfe die Erweiterte Tracking-Schutz Einstellungen deines Browsers.';
                        ?>
                        <p class="sgcc-placeholder__tp-hint"><?php echo esc_html( $tp_hint ); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="sgcc-placeholder__embed" style="display:none;">
                <?php echo $blocked_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function extract_attribute( $html, $attr ) {
        if ( preg_match( '/' . $attr . '=["\']([^"\']*)["\']/', $html, $matches ) ) {
            return $matches[1];
        }
        if ( preg_match( '/' . $attr . '=(\d+)/', $html, $matches ) ) {
            return $matches[1];
        }
        return null;
    }

    private function get_privacy_page_url() {
        $lang = $this->get_current_lang();

        $page_id = absint( get_option( 'sgcc_privacy_page_id_' . $lang, 0 ) );

        if ( ! $page_id ) {
            // Fallback to other language.
            $fallback_lang = ( 'en' === $lang ) ? 'de' : 'en';
            $page_id = absint( get_option( 'sgcc_privacy_page_id_' . $fallback_lang, 0 ) );
        }

        if ( ! $page_id ) {
            $page_id = absint( get_option( 'wp_page_for_privacy_policy', 0 ) );
        }

        if ( ! $page_id ) {
            return false;
        }

        return get_permalink( $page_id );
    }

    private function get_text( $key, $default_de, $default_en ) {
        $lang = $this->get_current_lang();

        // Use Polylang string translation if available.
        if ( function_exists( 'pll__' ) ) {
            $translated = pll__( $default_de );
            if ( $translated !== $default_de || 'de' === $lang ) {
                return $translated;
            }
            return $default_en;
        }

        $custom_texts = get_option( 'sgcc_texts', array() );
        if ( isset( $custom_texts[ $lang ][ $key ] ) && ! empty( $custom_texts[ $lang ][ $key ] ) ) {
            return $custom_texts[ $lang ][ $key ];
        }
        return ( 'en' === $lang ) ? $default_en : $default_de;
    }

    private function get_current_lang() {
        if ( function_exists( 'pll_current_language' ) ) {
            $lang = pll_current_language( 'slug' );
            return ( 'en' === $lang ) ? 'en' : 'de';
        }
        return 'de';
    }
}
