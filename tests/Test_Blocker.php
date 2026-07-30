<?php
/**
 * Tests fuer SGCC_Blocker – Embed-Blocking und Regex-Patterns.
 *
 * @package SchonGeil_Cookie_Consent\Tests
 */

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class Test_Blocker extends TestCase {

    private SGCC_Blocker $blocker;

    protected function setUp(): void {
        $GLOBALS['sgcc_test_options'] = array( 'sgcc_enabled' => 1 );

        // Blocker-Singleton zuruecksetzen.
        $ref = new ReflectionProperty( SGCC_Blocker::class, 'instance' );
        $ref->setValue( null, null );

        // Services-Cache zuruecksetzen.
        $ref2 = new ReflectionProperty( SGCC_Services::class, 'default_services' );
        $ref2->setValue( null, array() );

        $this->blocker = SGCC_Blocker::get_instance();
    }

    /* ==================================================================
       iframe-Blocking: bekannte Services werden geblockt
       ================================================================== */

    public function test_youtube_iframe_is_blocked(): void {
        $input  = '<p>Text</p><iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" width="560" height="315"></iframe>';
        $output = $this->blocker->filter_content( $input );

        // src= wird zu data-sgcc-src= umgeschrieben; pruefen, dass kein alleinstehender src= mehr da ist.
        $this->assertDoesNotMatchRegularExpression( '/\ssrc="https:\/\/www\.youtube\.com\/embed\//', $output );
        $this->assertStringContainsString( 'data-sgcc-src', $output );
        $this->assertStringContainsString( 'sgcc-blocked-iframe', $output );
        $this->assertStringContainsString( 'data-sgcc-service="youtube"', $output );
    }

    public function test_vimeo_iframe_is_blocked(): void {
        $input  = '<iframe src="https://player.vimeo.com/video/123456" width="640" height="360"></iframe>';
        $output = $this->blocker->filter_content( $input );

        $this->assertStringContainsString( 'data-sgcc-service="vimeo"', $output );
        $this->assertStringContainsString( 'sgcc-blocked-iframe', $output );
    }

    public function test_soundcloud_iframe_is_blocked(): void {
        $input  = '<iframe src="https://w.soundcloud.com/player/?url=https://api.soundcloud.com/tracks/123" width="100%" height="166"></iframe>';
        $output = $this->blocker->filter_content( $input );

        $this->assertStringContainsString( 'data-sgcc-service="soundcloud"', $output );
        $this->assertStringContainsString( 'sgcc-blocked-iframe', $output );
    }

    public function test_spotify_iframe_is_blocked(): void {
        $input  = '<iframe src="https://open.spotify.com/embed/track/abc123" width="300" height="380"></iframe>';
        $output = $this->blocker->filter_content( $input );

        $this->assertStringContainsString( 'data-sgcc-service="spotify"', $output );
    }

    /* ==================================================================
       iframe-Blocking: unbekannte iframes bleiben unveraendert
       ================================================================== */

    public function test_unknown_iframe_is_not_blocked(): void {
        $input  = '<iframe src="https://example.com/widget"></iframe>';
        $output = $this->blocker->filter_content( $input );

        $this->assertStringContainsString( 'src="https://example.com/widget"', $output );
        $this->assertStringNotContainsString( 'data-sgcc-src', $output );
        $this->assertStringNotContainsString( 'sgcc-blocked-iframe', $output );
    }

    /* ==================================================================
       Leerer / normaler Content
       ================================================================== */

    public function test_empty_content_returns_empty(): void {
        $this->assertSame( '', $this->blocker->filter_content( '' ) );
    }

    public function test_content_without_iframes_unchanged(): void {
        $input = '<p>Hello world</p><div class="gallery">Images</div>';
        $this->assertSame( $input, $this->blocker->filter_content( $input ) );
    }

    /* ==================================================================
       Mehrere iframes in einem Content
       ================================================================== */

    public function test_multiple_iframes_in_content(): void {
        $input = '<iframe src="https://www.youtube.com/embed/abc"></iframe><p>text</p><iframe src="https://player.vimeo.com/video/123"></iframe>';
        $output = $this->blocker->filter_content( $input );

        $this->assertStringContainsString( 'data-sgcc-service="youtube"', $output );
        $this->assertStringContainsString( 'data-sgcc-service="vimeo"', $output );
    }

    /* ==================================================================
       Instagram-Blockquote-Blocking
       ================================================================== */

    public function test_instagram_blockquote_is_blocked(): void {
        $input = '<blockquote class="instagram-media" data-instgrm-permalink="https://www.instagram.com/p/ABC123/">content</blockquote>';
        $output = $this->blocker->filter_content( $input );

        $this->assertStringNotContainsString( '<blockquote', $output );
        $this->assertStringContainsString( 'sgcc-blocked-embed', $output );
        $this->assertStringContainsString( 'data-sgcc-service="instagram"', $output );
    }

    public function test_instagram_blockquote_with_script_is_blocked(): void {
        $input = '<blockquote class="instagram-media" data-instgrm-permalink="https://www.instagram.com/p/ABC/">text</blockquote><script async src="//www.instagram.com/embed.js"></script>';
        $output = $this->blocker->filter_content( $input );

        $this->assertStringNotContainsString( '<blockquote', $output );
        $this->assertStringNotContainsString( 'instagram.com/embed.js', $output );
        $this->assertStringContainsString( 'sgcc-blocked-embed', $output );
    }

    public function test_instagram_reel_href_fallback_extracts_url(): void {
        $input  = '<blockquote class="instagram-media"><a href="https://www.instagram.com/reel/XYZ789/">reel</a></blockquote>';
        $output = $this->blocker->filter_content( $input );

        $this->assertStringContainsString( 'sgcc-blocked-embed', $output );
        $this->assertStringContainsString( 'data-sgcc-url="https://www.instagram.com/reel/XYZ789/"', $output );
    }

    public function test_non_instagram_blockquote_not_blocked(): void {
        $input = '<blockquote class="twitter-tweet">some tweet</blockquote>';
        $output = $this->blocker->filter_content( $input );

        $this->assertStringContainsString( '<blockquote', $output );
        $this->assertStringNotContainsString( 'sgcc-blocked', $output );
    }

    /* ==================================================================
       extract_attribute() – private Methode via Reflection
       ================================================================== */

    public static function attributeProvider(): array {
        return array(
            'double quotes'  => array( '<iframe width="560" height="315">', 'width', '560' ),
            'single quotes'  => array( "<iframe width='560'>", 'width', '560' ),
            'no quotes'      => array( '<iframe width=560>', 'width', '560' ),
            'height'         => array( '<iframe height="315">', 'height', '315' ),
            'missing attr'   => array( '<iframe src="x">', 'width', null ),
            'percent width'  => array( '<iframe width="100%">', 'width', '100%' ),
            'empty value'    => array( '<iframe width="">', 'width', '' ),
        );
    }

    #[DataProvider('attributeProvider')]
    public function test_extract_attribute( string $html, string $attr, ?string $expected ): void {
        $method = new ReflectionMethod( SGCC_Blocker::class, 'extract_attribute' );

        $result = $method->invoke( $this->blocker, $html, $attr );
        $this->assertSame( $expected, $result );
    }

    /* ==================================================================
       src-Extraktions-Regex (inline Test)
       ================================================================== */

    public static function srcExtractionProvider(): array {
        return array(
            'double quotes' => array( '<iframe src="https://example.com/video" width="560">', 'https://example.com/video' ),
            'single quotes' => array( "<iframe src='https://example.com/video'>", 'https://example.com/video' ),
            'with params'   => array( '<iframe src="https://youtube.com/embed/abc?rel=0">', 'https://youtube.com/embed/abc?rel=0' ),
            'no src'        => array( '<iframe width="560"></iframe>', null ),
        );
    }

    #[DataProvider('srcExtractionProvider')]
    public function test_src_extraction_regex( string $iframe, ?string $expected_src ): void {
        $pattern = '/src=["\']([^"\']+)["\']/i';
        if ( preg_match( $pattern, $iframe, $matches ) ) {
            $this->assertSame( $expected_src, $matches[1] );
        } else {
            $this->assertNull( $expected_src );
        }
    }

    /* ==================================================================
       Style-Dimension-Regex (inline Test)
       ================================================================== */

    public static function styleDimensionProvider(): array {
        return array(
            'width and height' => array( 'width: 100%; height: 500px;', '100%', '500px' ),
            'only width'       => array( 'width: 640px;', '640px', null ),
            'only height'      => array( 'height: 300px;', null, '300px' ),
            'no dimensions'    => array( 'border: none;', null, null ),
            'px values'        => array( 'width: 800px; height: 450px;', '800px', '450px' ),
        );
    }

    #[DataProvider('styleDimensionProvider')]
    public function test_style_dimension_regex( string $style, ?string $expected_width, ?string $expected_height ): void {
        $width  = preg_match( '/width:\s*([^;]+)/', $style, $w ) ? trim( $w[1] ) : null;
        $height = preg_match( '/height:\s*([^;]+)/', $style, $h ) ? trim( $h[1] ) : null;

        $this->assertSame( $expected_width, $width );
        $this->assertSame( $expected_height, $height );
    }
}
