<?php
/**
 * Each package translates with its own text domain.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Packaging;

use PHPUnit\Framework\TestCase as Base;

/**
 * Text domains.
 */
final class TextDomainTest extends Base {

	/**
	 * Translation calls naming the wrong domain in a folder.
	 *
	 * @param string $dir   Folder.
	 * @param string $wrong Domain that must not appear.
	 * @return string[] "file:line" entries.
	 */
	private function offenders( $dir, $wrong ) {
		$found = array();
		$files = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) );
		foreach ( $files as $file ) {
			if ( 'php' !== $file->getExtension() || false !== strpos( $file->getPathname(), '/vendor/' ) ) {
				continue;
			}
			foreach ( file( $file->getPathname() ) as $number => $line ) {
				if ( preg_match( "/\b(?:__|_e|_x|_n|_nx|esc_html__|esc_html_e|esc_html_x|esc_attr__|esc_attr_e|esc_attr_x)\s*\(.*,\s*'" . preg_quote( $wrong, '/' ) . "'\s*\)/", $line ) ) {
					$found[] = $file->getFilename() . ':' . ( $number + 1 );
				}
			}
		}
		return $found;
	}

	public function test_theme_and_plugin_use_their_own_domain() {
		$root = dirname( __DIR__, 3 );
		$this->assertSame( array(), $this->offenders( $root . '/plugin/qe-edu-core', 'qe-edu' ), 'Plugin strings use qe-edu-core.' );
		$this->assertSame( array(), $this->offenders( $root . '/theme/qe-edu', 'qe-edu-core' ), 'Theme strings use qe-edu.' );
		$this->assertStringContainsString( 'Text Domain:       qe-edu-core', (string) file_get_contents( $root . '/plugin/qe-edu-core/qe-edu-core.php' ) );
		$this->assertStringContainsString( 'Text Domain: qe-edu', (string) file_get_contents( $root . '/theme/qe-edu/style.css' ) );
	}
}
