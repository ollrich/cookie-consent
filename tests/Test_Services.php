<?php
/**
 * Tests fuer SGCC_Services – Service-Erkennung und YouTube-ID-Extraktion.
 *
 * @package SchonGeil_Cookie_Consent\Tests
 */

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class Test_Services extends TestCase {

    protected function setUp(): void {
        $GLOBALS['sgcc_test_options'] = array();

        // Statischen Service-Cache zuruecksetzen.
        $ref = new ReflectionProperty( SGCC_Services::class, 'default_services' );
        $ref->setValue( null, array() );
    }

    /* ==================================================================
       identify_service() – YouTube URL-Formate
       ================================================================== */

    public static function youtubeUrlProvider(): array {
        return array(
            'embed'         => array( 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube' ),
            'nocookie'      => array( 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', 'youtube' ),
            'watch'         => array( 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'youtube' ),
            'short url'     => array( 'https://youtu.be/dQw4w9WgXcQ', 'youtube' ),
            'v format'      => array( 'https://www.youtube.com/v/dQw4w9WgXcQ', 'youtube' ),
            'shorts'        => array( 'https://www.youtube.com/shorts/dQw4w9WgXcQ', 'youtube' ),
            'with params'   => array( 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=120', 'youtube' ),
        );
    }

    #[DataProvider('youtubeUrlProvider')]
    public function test_identify_service_youtube( string $url, string $expected ): void {
        $this->assertSame( $expected, SGCC_Services::identify_service( $url ) );
    }

    /* ==================================================================
       identify_service() – alle anderen Services
       ================================================================== */

    public static function otherServicesProvider(): array {
        return array(
            'vimeo player'      => array( 'https://player.vimeo.com/video/123456', 'vimeo' ),
            'vimeo direct'      => array( 'https://vimeo.com/123456', 'vimeo' ),
            'soundcloud player' => array( 'https://w.soundcloud.com/player/?url=track', 'soundcloud' ),
            'soundcloud api'    => array( 'https://api.soundcloud.com/tracks/123', 'soundcloud' ),
            'bandcamp'          => array( 'https://bandcamp.com/EmbeddedPlayer/album=123', 'bandcamp' ),
            'hearthis'          => array( 'https://hearthis.at/user/track/embed/', 'hearthis' ),
            'instagram post'    => array( 'https://www.instagram.com/p/ABC123/', 'instagram' ),
            'instagram reel'    => array( 'https://www.instagram.com/reel/ABC123/', 'instagram' ),
            'instagram embed'   => array( 'https://www.instagram.com/embed/', 'instagram' ),
            'spotify'           => array( 'https://open.spotify.com/embed/track/123', 'spotify' ),
            'mixcloud widget'   => array( 'https://player-widget.mixcloud.com/widget/123', 'mixcloud' ),
            'mixcloud www'      => array( 'https://www.mixcloud.com/widget/iframe/', 'mixcloud' ),
        );
    }

    #[DataProvider('otherServicesProvider')]
    public function test_identify_service_others( string $url, string $expected ): void {
        $this->assertSame( $expected, SGCC_Services::identify_service( $url ) );
    }

    /* ==================================================================
       identify_service() – nicht erkannte URLs → false
       ================================================================== */

    public static function unknownUrlProvider(): array {
        return array(
            'google'         => array( 'https://www.google.com/' ),
            'random site'    => array( 'https://example.com/video/embed' ),
            'empty string'   => array( '' ),
            'partial match'  => array( 'https://example.com/embed/abc' ),
            'plain text'     => array( 'just some text' ),
        );
    }

    #[DataProvider('unknownUrlProvider')]
    public function test_identify_service_returns_false_for_unknown( string $url ): void {
        $this->assertFalse( SGCC_Services::identify_service( $url ) );
    }

    /* ==================================================================
       identify_service() – Custom Services
       ================================================================== */

    public function test_identify_service_finds_custom_service(): void {
        $GLOBALS['sgcc_test_options']['sgcc_custom_services'] = array(
            'tiktok' => array(
                'name'     => 'TikTok',
                'category' => 'video',
                'enabled'  => true,
                'patterns' => array( 'tiktok.com/embed/', 'tiktok.com/@' ),
                'icon'     => 'placeholder-generic.svg',
                'texts'    => array( 'de' => array(), 'en' => array() ),
            ),
        );

        // Cache zuruecksetzen damit neue Options geladen werden.
        $ref = new ReflectionProperty( SGCC_Services::class, 'default_services' );
        $ref->setValue( null, array() );

        $this->assertSame( 'tiktok', SGCC_Services::identify_service( 'https://www.tiktok.com/embed/v2/123' ) );
    }

    public function test_identify_service_disabled_service_not_matched(): void {
        $GLOBALS['sgcc_test_options']['sgcc_services'] = array(
            'youtube' => array( 'enabled' => false ),
        );

        $ref = new ReflectionProperty( SGCC_Services::class, 'default_services' );
        $ref->setValue( null, array() );

        $this->assertFalse( SGCC_Services::identify_service( 'https://www.youtube.com/embed/abc' ) );
    }

    /* ==================================================================
       get_youtube_video_id() – ID-Extraktion
       ================================================================== */

    public static function youtubeVideoIdProvider(): array {
        return array(
            'embed'       => array( 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'dQw4w9WgXcQ' ),
            'nocookie'    => array( 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', 'dQw4w9WgXcQ' ),
            'short url'   => array( 'https://youtu.be/dQw4w9WgXcQ', 'dQw4w9WgXcQ' ),
            'watch'       => array( 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ' ),
            'shorts'      => array( 'https://www.youtube.com/shorts/dQw4w9WgXcQ', 'dQw4w9WgXcQ' ),
            'v format'    => array( 'https://www.youtube.com/v/dQw4w9WgXcQ', 'dQw4w9WgXcQ' ),
            'with params' => array( 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=120', 'dQw4w9WgXcQ' ),
            'underscore'  => array( 'https://youtu.be/a_B-c1D_efg', 'a_B-c1D_efg' ),
            'hyphen'      => array( 'https://youtu.be/abc-123-XYZ', 'abc-123-XYZ' ),
        );
    }

    #[DataProvider('youtubeVideoIdProvider')]
    public function test_get_youtube_video_id( string $url, string $expected_id ): void {
        $this->assertSame( $expected_id, SGCC_Services::get_youtube_video_id( $url ) );
    }

    public function test_get_youtube_video_id_returns_false_for_non_youtube(): void {
        $this->assertFalse( SGCC_Services::get_youtube_video_id( 'https://vimeo.com/123' ) );
    }

    public function test_get_youtube_video_id_returns_false_for_empty(): void {
        $this->assertFalse( SGCC_Services::get_youtube_video_id( '' ) );
    }
}
