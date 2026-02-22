<?php
/**
 * Embed Scanner
 *
 * Scans published content for third-party embeds.
 *
 * @package SchonGeil_Cookie_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SGCC_Scanner {

    /**
     * Scan all published posts and pages for embeds.
     *
     * @param int $limit Max number of posts to scan (0 = all).
     * @return array Scan results.
     */
    public static function scan( $limit = 0 ) {
        $args = array(
            'post_type'      => array( 'post', 'page' ),
            'post_status'    => 'publish',
            'posts_per_page' => $limit > 0 ? $limit : -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'no_found_rows'  => true,
        );

        $query   = new WP_Query( $args );
        $results = array();

        if ( ! $query->have_posts() ) {
            return $results;
        }

        $services = SGCC_Services::get_all();

        while ( $query->have_posts() ) {
            $query->the_post();

            $post_id      = get_the_ID();
            $content      = get_the_content();
            $post_title   = get_the_title();
            $post_url     = get_edit_post_link( $post_id, 'raw' );
            $found_embeds = array();

            // 1. Scan for iframes.
            if ( preg_match_all( '/<iframe\b[^>]*src=["\']([^"\']+)["\']/i', $content, $matches ) ) {
                foreach ( $matches[1] as $src ) {
                    $service_key = SGCC_Services::identify_service( $src );
                    if ( $service_key ) {
                        $found_embeds[] = array(
                            'service' => $service_key,
                            'url'     => $src,
                            'type'    => 'iframe',
                        );
                    }
                }
            }

            // 2. Scan for oEmbed URLs (plain URLs on their own line).
            $oembed_patterns = array(
                'youtube'    => '/https?:\/\/(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/shorts\/)([a-zA-Z0-9_-]+)/i',
                'vimeo'      => '/https?:\/\/(?:www\.)?(?:vimeo\.com\/|player\.vimeo\.com\/video\/)(\d+)/i',
                'soundcloud' => '/https?:\/\/soundcloud\.com\/[a-zA-Z0-9_-]+\/[a-zA-Z0-9_-]+/i',
                'bandcamp'   => '/https?:\/\/[a-zA-Z0-9_-]+\.bandcamp\.com\//i',
                'hearthis'   => '/https?:\/\/hearthis\.at\/[a-zA-Z0-9_-]+\//i',
                'spotify'    => '/https?:\/\/open\.spotify\.com\/(?:track|album|playlist|episode)\/[a-zA-Z0-9]+/i',
                'mixcloud'   => '/https?:\/\/(?:www\.)?mixcloud\.com\/[a-zA-Z0-9_-]+\/[a-zA-Z0-9_-]+/i',
                'instagram'  => '/https?:\/\/(?:www\.)?instagram\.com\/(?:p|reel)\/[a-zA-Z0-9_-]+/i',
            );

            foreach ( $oembed_patterns as $service_key => $pattern ) {
                if ( preg_match_all( $pattern, $content, $matches ) ) {
                    foreach ( $matches[0] as $url ) {
                        $found_embeds[] = array(
                            'service' => $service_key,
                            'url'     => $url,
                            'type'    => 'oembed',
                        );
                    }
                }
            }

            // 3. Scan for known shortcodes.
            $shortcode_patterns = array(
                '/\[youtube\b[^\]]*\]/i',
                '/\[soundcloud\b[^\]]*\]/i',
                '/\[spotify\b[^\]]*\]/i',
                '/\[bandcamp\b[^\]]*\]/i',
                '/\[instagram\b[^\]]*\]/i',
                '/\[embed\b[^\]]*\]/i',
            );

            foreach ( $shortcode_patterns as $pattern ) {
                if ( preg_match_all( $pattern, $content, $matches ) ) {
                    foreach ( $matches[0] as $shortcode ) {
                        $found_embeds[] = array(
                            'service' => 'shortcode',
                            'url'     => $shortcode,
                            'type'    => 'shortcode',
                        );
                    }
                }
            }

            if ( ! empty( $found_embeds ) ) {
                $results[] = array(
                    'post_id'    => $post_id,
                    'post_title' => $post_title,
                    'edit_url'   => $post_url,
                    'view_url'   => get_permalink( $post_id ),
                    'embeds'     => $found_embeds,
                );
            }
        }

        wp_reset_postdata();

        return $results;
    }

    /**
     * Summarize scan results by service.
     *
     * @param array $results Raw scan results.
     * @return array Summary grouped by service.
     */
    public static function summarize( $results ) {
        $summary  = array();
        $services = SGCC_Services::get_all();

        foreach ( $results as $result ) {
            foreach ( $result['embeds'] as $embed ) {
                $key = $embed['service'];
                if ( ! isset( $summary[ $key ] ) ) {
                    $name = isset( $services[ $key ] ) ? $services[ $key ]['name'] : ucfirst( $key );
                    $enabled = isset( $services[ $key ] ) ? ! empty( $services[ $key ]['enabled'] ) : false;
                    $summary[ $key ] = array(
                        'name'     => $name,
                        'count'    => 0,
                        'posts'    => array(),
                        'blocking' => $enabled,
                    );
                }
                $summary[ $key ]['count']++;

                // Add post reference (avoid duplicates).
                $post_found = false;
                foreach ( $summary[ $key ]['posts'] as $p ) {
                    if ( $p['post_id'] === $result['post_id'] ) {
                        $post_found = true;
                        break;
                    }
                }
                if ( ! $post_found ) {
                    $summary[ $key ]['posts'][] = array(
                        'post_id'    => $result['post_id'],
                        'post_title' => $result['post_title'],
                        'edit_url'   => $result['edit_url'],
                        'view_url'   => $result['view_url'],
                    );
                }
            }
        }

        // Sort by count descending.
        uasort( $summary, function ( $a, $b ) {
            return $b['count'] - $a['count'];
        } );

        return $summary;
    }
}
