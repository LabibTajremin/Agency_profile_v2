<?php
/**
 * Site icon made from the logo.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Plugin;

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use QeEdu\Core\Settings\SiteIcon;

/**
 * SiteIcon.
 */
final class SiteIconTest extends PluginTestCase {

	/** @var string[] Temporary files. */
	private $files = array();

	/** @var array<int,mixed> Fake attachments. */
	private $attachments = array();

	/** @var int Last attachment id. */
	private $next = 100;

	/** @var string|false Upload error. */
	private $upload_error = false;

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'get_the_title' )->justReturn( 'Logo' );
		Functions\when( 'is_wp_error' )->alias(
			static function ( $thing ) {
				return $thing instanceof \WP_Error;
			}
		);
		Functions\when( 'get_post' )->alias(
			function ( $id ) {
				return isset( $this->attachments[ $id ] ) ? (object) array( 'ID' => $id ) : null;
			}
		);
		Functions\when( 'wp_upload_bits' )->alias(
			function ( $name, $deprecated, $bits ) {
				$file          = $this->temp( $bits );
				$this->files[] = $file;
				return array(
					'file'  => $file,
					'error' => $this->upload_error,
				);
			}
		);
		Functions\when( 'wp_insert_attachment' )->alias(
			function ( $args, $file ) {
				$id                       = ++$this->next;
				$this->attachments[ $id ] = array_merge( $args, array( 'file' => $file ) );
				return $id;
			}
		);
		Functions\when( 'wp_generate_attachment_metadata' )->justReturn( array() );
		Functions\when( 'wp_update_attachment_metadata' )->justReturn( true );
	}

	protected function tearDown(): void {
		foreach ( $this->files as $file ) {
			if ( is_file( $file ) ) {
				unlink( $file );
			}
		}
		parent::tearDown();
	}

	/**
	 * Write bytes to a temporary file.
	 *
	 * @param string $bytes Bytes.
	 * @return string Path.
	 */
	private function temp( $bytes ) {
		$file = tempnam( sys_get_temp_dir(), 'qe-icon' );
		file_put_contents( $file, $bytes );
		$this->files[] = $file;
		return $file;
	}

	/**
	 * A PNG of the given size, filled red.
	 *
	 * @param int $width  Width.
	 * @param int $height Height.
	 * @return string Path.
	 */
	private function png( $width, $height ) {
		$image = imagecreatetruecolor( $width, $height );
		imagefill( $image, 0, 0, imagecolorallocate( $image, 200, 0, 0 ) );
		ob_start();
		imagepng( $image );
		return $this->temp( (string) ob_get_clean() );
	}

	public function test_wide_logo_becomes_a_centred_square() {
		$png  = SiteIcon::square_png( $this->png( 500, 100 ) );
		$icon = imagecreatefromstring( $png );

		$this->assertSame( array( 512, 512 ), array( imagesx( $icon ), imagesy( $icon ) ) );
		$this->assertSame( 127, imagecolorsforindex( $icon, imagecolorat( $icon, 256, 10 ) )['alpha'], 'Transparent above the logo.' );
		$this->assertSame( 0, imagecolorsforindex( $icon, imagecolorat( $icon, 256, 256 ) )['alpha'], 'Logo in the middle.' );
		$this->assertSame( 200, imagecolorsforindex( $icon, imagecolorat( $icon, 5, 256 ) )['red'], 'Full width used.' );
	}

	/**
	 * A 600×120 logo drawn from rectangles [x1, y1, x2, y2, [r, g, b]].
	 *
	 * @param array<int,array<int,mixed>> $blocks      Blocks.
	 * @param bool                        $transparent Transparent (else white) background.
	 * @return \GdImage
	 */
	private function logo( $blocks, $transparent = true ) {
		$image = imagecreatetruecolor( 600, 120 );
		imagealphablending( $image, false );
		imagesavealpha( $image, true );
		imagefill( $image, 0, 0, $transparent ? imagecolorallocatealpha( $image, 0, 0, 0, 127 ) : imagecolorallocate( $image, 255, 255, 255 ) );
		foreach ( $blocks as $b ) {
			imagefilledrectangle( $image, $b[0], $b[1], $b[2], $b[3], imagecolorallocate( $image, $b[4][0], $b[4][1], $b[4][2] ) );
		}
		return $image;
	}

	public function test_symbol_of_a_wide_logo_is_used() {
		$symbol = array( 10, 10, 109, 109, array( 200, 0, 0 ) );
		$name   = array( 140, 40, 590, 80, array( 0, 0, 200 ) );

		$this->assertSame( array( 10, 10, 100, 100 ), SiteIcon::mark_box( $this->logo( array( $symbol, $name ) ) ) );
		$this->assertSame( array( 10, 10, 100, 100 ), SiteIcon::mark_box( $this->logo( array( $symbol, $name ), false ) ), 'White background too.' );

		$this->assertNull( SiteIcon::mark_box( $this->logo( array( array( 10, 0, 19, 119, array( 200, 0, 0 ) ), $name ) ) ), 'A thin bar is not a symbol.' );
		$this->assertNull( SiteIcon::mark_box( $this->logo( array( array( 100, 10, 500, 100, array( 200, 0, 0 ) ) ) ) ), 'One block, no separate name.' );
		$this->assertNull( SiteIcon::mark_box( $this->logo( array() ) ), 'Blank.' );
		$this->assertNull( SiteIcon::mark_box( imagecreatetruecolor( 100, 100 ) ), 'Not wide.' );

		// The icon is the symbol, filling the square.
		$image = $this->logo( array( $symbol, $name ) );
		ob_start();
		imagepng( $image );
		$icon = imagecreatefromstring( SiteIcon::square_png( $this->temp( (string) ob_get_clean() ) ) );
		foreach ( array( array( 20, 20 ), array( 256, 256 ), array( 490, 490 ) ) as $point ) {
			$colour = imagecolorsforindex( $icon, imagecolorat( $icon, $point[0], $point[1] ) );
			$this->assertSame( array( 200, 0 ), array( $colour['red'], $colour['alpha'] ), implode( ',', $point ) );
		}
	}

	public function test_unusable_files() {
		$this->assertSame( '', SiteIcon::square_png( '' ) );
		$this->assertSame( '', SiteIcon::square_png( '/nope/logo.png' ) );
		$this->assertSame( '', SiteIcon::square_png( $this->temp( '<svg xmlns="http://www.w3.org/2000/svg"/>' ) ), 'SVG is not a raster image.' );
	}

	public function test_from_logo_makes_the_square_copy_once() {
		$this->assertSame( 0, SiteIcon::from_logo( 0 ) );

		$logo = $this->png( 300, 100 );
		Functions\when( 'get_attached_file' )->justReturn( $logo );
		$this->attachments[5] = array();

		$icon = SiteIcon::from_logo( 5 );
		$this->assertSame( 101, $icon );
		$this->assertSame( 'image/png', $this->attachments[ $icon ]['post_mime_type'] );
		$this->assertSame( 'site-icon', $this->meta[ $icon ]['_wp_attachment_context'] );
		$this->assertSame( $icon, $this->meta[5][ SiteIcon::META ] );
		$this->assertSame( array( 512, 512 ), array_slice( getimagesize( $this->attachments[ $icon ]['file'] ), 0, 2 ) );

		$this->assertSame( $icon, SiteIcon::from_logo( 5 ), 'Reused.' );

		unset( $this->attachments[ $icon ] );
		$this->assertSame( 102, SiteIcon::from_logo( 5 ), 'Made again when the copy was deleted.' );
	}

	public function test_from_logo_falls_back_to_the_logo() {
		Functions\when( 'get_attached_file' )->justReturn( '' );
		$this->assertSame( 7, SiteIcon::from_logo( 7 ), 'Unreadable (e.g. SVG): the logo itself.' );

		Functions\when( 'get_attached_file' )->justReturn( $this->png( 64, 64 ) );
		$this->upload_error = 'Disk full';
		$this->assertSame( 7, SiteIcon::from_logo( 7 ) );

		$this->upload_error = false;
		Functions\when( 'wp_insert_attachment' )->justReturn( new \WP_Error( 'x', 'y' ) );
		$this->assertSame( 7, SiteIcon::from_logo( 7 ) );
		Functions\when( 'wp_insert_attachment' )->justReturn( 0 );
		$this->assertSame( 7, SiteIcon::from_logo( 7 ) );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_loads_the_image_functions_when_needed() {
		$dir = ABSPATH . 'wp-admin/includes';
		if ( ! is_dir( $dir ) ) {
			mkdir( $dir, 0777, true );
		}
		file_put_contents( $dir . '/image.php', '<?php $GLOBALS["qe_image_loaded"] = true;' );
		\Patchwork\redefine(
			'function_exists',
			static function ( $name ) {
				return 'wp_generate_attachment_metadata' === $name ? ! empty( $GLOBALS['qe_image_loaded'] ) : \Patchwork\relay();
			}
		);
		Functions\when( 'get_attached_file' )->justReturn( $this->png( 64, 64 ) );
		SiteIcon::from_logo( 9 );
		unlink( $dir . '/image.php' );
		$this->assertTrue( $GLOBALS['qe_image_loaded'] );
	}
}
