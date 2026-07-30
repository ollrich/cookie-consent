<?php
/**
 * Tests fuer Hex-Color-Validierung (SGCC_Core).
 *
 * Der Regex wird isoliert getestet, da SGCC_Core beim Instanziieren das
 * gesamte Plugin bootstrappt. Der Pattern stammt aus
 * SGCC_Core::inject_custom_colors() und erlaubt nur die gueltigen
 * CSS-Hex-Laengen 3, 4, 6 und 8.
 *
 * @package SchonGeil_Cookie_Consent\Tests
 */

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class Test_Core extends TestCase {

    /**
     * Exakter Regex aus SGCC_Core::inject_custom_colors().
     */
    private const HEX_COLOR_PATTERN = '/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/';

    /* ==================================================================
       Gueltige Hex-Farben
       ================================================================== */

    public static function validColorProvider(): array {
        return array(
            '3-digit'        => array( '#fff' ),
            '3-digit upper'  => array( '#FFF' ),
            '4-digit alpha'  => array( '#fffa' ),
            '6-digit'        => array( '#1a1a2e' ),
            '6-digit upper'  => array( '#16213E' ),
            '8-digit rgba'   => array( '#1a1a2eff' ),
            'mixed case'     => array( '#aAbBcC' ),
            'all zeros 3'    => array( '#000' ),
            'all zeros 6'    => array( '#000000' ),
        );
    }

    #[DataProvider('validColorProvider')]
    public function test_valid_hex_colors_pass( string $color ): void {
        $this->assertSame( 1, preg_match( self::HEX_COLOR_PATTERN, $color ) );
    }

    /* ==================================================================
       Ungueltige Hex-Farben – Fallback auf #000000
       ================================================================== */

    public static function invalidColorProvider(): array {
        return array(
            'no hash'         => array( '1a1a2e' ),
            'too short 1'     => array( '#f' ),
            'too short 2'     => array( '#ff' ),
            '5-digit'         => array( '#12345' ),
            '7-digit'         => array( '#1234567' ),
            'too long 9'      => array( '#fffffffff' ),
            'css injection'   => array( '#000;background:url(evil)' ),
            'expression'      => array( 'expression(alert(1))' ),
            'with spaces'     => array( '# fff' ),
            'non hex chars'   => array( '#gggggg' ),
            'empty'           => array( '' ),
            'just hash'       => array( '#' ),
            'rgb format'      => array( 'rgb(0,0,0)' ),
            'double hash'     => array( '##fff' ),
            'url injection'   => array( '#000}body{background:red' ),
            'semicolon'       => array( '#000;' ),
        );
    }

    #[DataProvider('invalidColorProvider')]
    public function test_invalid_hex_colors_fail( string $color ): void {
        $this->assertSame( 0, preg_match( self::HEX_COLOR_PATTERN, $color ) );
    }
}
