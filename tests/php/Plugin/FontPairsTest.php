<?php
/**
 * Font pair tests.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Plugin;

use QeEdu\Core\Colors\FontPairs;

/**
 * Font pairs.
 */
final class FontPairsTest extends PluginTestCase {

	public function test_twelve_pairs() {
		$pairs = FontPairs::all();
		$this->assertCount( 12, $pairs );
		foreach ( $pairs as $pair ) {
			$this->assertNotSame( '', $pair['label'] );
			$this->assertNotSame( '', $pair['heading'] );
			$this->assertNotSame( '', $pair['body'] );
		}
	}

	public function test_get_and_fallback() {
		$this->assertSame( 'Poppins', FontPairs::get( 'poppins-inter' )['heading'] );
		$this->assertSame( 'montserrat-open-sans', FontPairs::get( 'missing' )['slug'] );
	}

	public function test_google_url_and_stack() {
		$this->assertSame(
			'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Source+Sans+3:wght@400;600;700&display=swap',
			FontPairs::google_url( 'playfair-source-sans' )
		);
		$this->assertSame( "'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif", FontPairs::stack( 'Inter' ) );
	}
}
