<?php
/**
 * Palette tests, including the Phase 3 contrast gate.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Plugin;

use QeEdu\Core\Colors\Palette;

/**
 * Palette.
 */
final class PaletteTest extends PluginTestCase {

	public function test_twelve_accents_with_unique_colours() {
		$accents = Palette::accents();
		$this->assertCount( 12, $accents );
		$this->assertSame( 'qe-crimson', array_key_first( $accents ) );
		$this->assertCount( 12, array_unique( array_column( $accents, 'primary' ) ) );
	}

	/**
	 * Gate: every palette reaches 4.5:1 for button text, hover text and links.
	 */
	public function test_every_palette_meets_contrast() {
		foreach ( array_keys( Palette::accents() ) as $slug ) {
			$p = Palette::resolve( $slug );
			$this->assertGreaterThanOrEqual( 4.5, Palette::contrast( $p['primary'], $p['on_primary'] ), "$slug on primary" );
			$this->assertGreaterThanOrEqual( 4.5, Palette::contrast( $p['dark'], $p['on_dark'] ), "$slug on dark" );
			$this->assertGreaterThanOrEqual( 4.5, Palette::contrast( $p['link'], Palette::LIGHT_SURFACE ), "$slug link" );
			$this->assertGreaterThanOrEqual( 4.5, Palette::contrast( $p['link_dark_mode'], Palette::DARK_SURFACE ), "$slug dark-mode link" );
		}
	}

	/**
	 * Gate: dark mode keeps text and buttons readable for every palette.
	 */
	public function test_every_palette_meets_contrast_in_dark_mode() {
		foreach ( array_keys( Palette::accents() ) as $slug ) {
			$p = Palette::resolve( $slug );
			$this->assertGreaterThanOrEqual( 4.5, Palette::contrast( $p['dm_primary'], Palette::DARK_SURFACE ), "$slug dark text" );
			$this->assertGreaterThanOrEqual( 4.5, Palette::contrast( $p['dm_primary'], $p['dm_on_primary'] ), "$slug dark button" );
			$this->assertGreaterThanOrEqual( 4.5, Palette::contrast( $p['dm_hover'], $p['dm_on_hover'] ), "$slug dark hover" );
		}
	}

	public function test_dark_mode_accent_for_any_colour() {
		foreach ( array( '#000000', '#140342', '#777777', '#FFFFFF', '#0000FF' ) as $color ) {
			$dm = Palette::dark_mode_accent( $color );
			$this->assertGreaterThanOrEqual( 4.5, Palette::contrast( $dm, Palette::DARK_SURFACE ), $color );
			$this->assertGreaterThanOrEqual( 4.5, max( Palette::contrast( $dm, '#FFFFFF' ), Palette::contrast( $dm, '#111111' ) ), $color );
		}
	}

	public function test_custom_colours_always_meet_contrast() {
		foreach ( array( '#FFFF00', '#777777', '#000000', '#FFFFFF', '#0F8A5F', '#E2574C', '#808080', '#3366FF' ) as $color ) {
			$p = Palette::resolve( 'custom', $color );
			$this->assertSame( 'custom', $p['slug'] );
			$this->assertGreaterThanOrEqual( 4.5, Palette::contrast( $p['primary'], $p['on_primary'] ), $color );
			$this->assertGreaterThanOrEqual( 4.5, Palette::contrast( $p['dark'], $p['on_dark'] ), $color );
			$this->assertGreaterThanOrEqual( 4.5, Palette::contrast( $p['link'], Palette::LIGHT_SURFACE ), $color );
			$this->assertGreaterThanOrEqual( 4.5, Palette::contrast( $p['link_dark_mode'], Palette::DARK_SURFACE ), $color );
		}
	}

	public function test_readable_custom_keeps_good_colours_and_fixes_mid_tones() {
		$this->assertSame( '#3366FF', Palette::readable_custom( '#36f' ) );
		$this->assertSame( '#FFFF00', Palette::readable_custom( '#FFFF00' ) );
		$fixed = Palette::readable_custom( '#777777' );
		$this->assertNotSame( '#777777', $fixed );
		$this->assertGreaterThanOrEqual( 4.5, Palette::contrast( $fixed, '#FFFFFF' ) );
	}

	public function test_resolve_falls_back_to_default() {
		$this->assertSame( 'qe-crimson', Palette::resolve( 'nope' )['slug'] );
		$this->assertSame( 'qe-crimson', Palette::resolve( 'custom', 'not-a-colour' )['slug'] );

		$crimson = Palette::resolve( 'qe-crimson' );
		$this->assertSame( '#A41E34', $crimson['primary'] );
		$this->assertSame( 'rgb(164 30 52 / 8%)', $crimson['soft'] );
		$this->assertSame( '#FFFFFF', $crimson['on_primary'] );
	}

	public function test_documented_fixes_for_spec_palettes() {
		$this->assertSame( '#0F875D', Palette::resolve( 'emerald' )['primary'] );
		$this->assertSame( '#111111', Palette::resolve( 'sunset-coral' )['on_primary'] );
		$this->assertSame( '#FFFFFF', Palette::resolve( 'sunset-coral' )['on_dark'] );
	}

	public function test_normalize() {
		$this->assertSame( '#AABBCC', Palette::normalize( 'abc' ) );
		$this->assertSame( '#A41E34', Palette::normalize( ' #a41e34 ' ) );
		$this->assertSame( '', Palette::normalize( '#12' ) );
		$this->assertSame( '', Palette::normalize( array() ) );
	}

	public function test_maths() {
		$this->assertSame( array( 164, 30, 52 ), Palette::rgb( '#A41E34' ) );
		$this->assertEqualsWithDelta( 21.0, Palette::contrast( '#000000', '#FFFFFF' ), 0.001 );
		$this->assertEqualsWithDelta( 1.0, Palette::contrast( '#777777', '#777777' ), 0.001 );
		$this->assertSame( '#808080', Palette::mix( '#000000', '#FFFFFF', 0.5 ) );
		$this->assertSame( 'rgb(0 0 0 / 50%)', Palette::rgba( '#000', 0.5 ) );
	}

	public function test_text_on() {
		$this->assertSame( '#FFFFFF', Palette::text_on( '#000000' ) );
		$this->assertSame( '#111111', Palette::text_on( '#FFFF00' ) );
		$this->assertSame( '#FFFFFF', Palette::text_on( '#0000AA', '#111111' ) );
		$this->assertSame( '#111111', Palette::text_on( '#FFFFFF', '#111111' ) );
	}

	public function test_ensure_contrast() {
		$this->assertSame( '#000000', Palette::ensure_contrast( '#000000', '#FFFFFF' ), 'Already passing' );

		$darkened = Palette::ensure_contrast( '#E89B12', '#FFFFFF' );
		$this->assertGreaterThanOrEqual( 4.5, Palette::contrast( $darkened, '#FFFFFF' ) );

		$lightened = Palette::ensure_contrast( '#140342', '#0E0B1F' );
		$this->assertGreaterThanOrEqual( 4.5, Palette::contrast( $lightened, '#0E0B1F' ) );

		// Worst case mid-grey background still terminates with a passing colour.
		$grey = Palette::ensure_contrast( '#767676', '#767676' );
		$this->assertGreaterThanOrEqual( 4.5, Palette::contrast( $grey, '#767676' ) );
	}
}
