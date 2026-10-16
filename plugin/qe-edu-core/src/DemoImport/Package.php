<?php
/**
 * The demo package shipped in /demo: manifest, Elementor JSON and images.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\DemoImport;

defined( 'ABSPATH' ) || exit;

/**
 * Demo package reader.
 */
class Package {

	/**
	 * Package directory (with trailing slash).
	 *
	 * @var string
	 */
	private $dir;

	/**
	 * Decoded manifest (lazy).
	 *
	 * @var array<string,mixed>|null
	 */
	private $manifest = null;

	/**
	 * Constructor.
	 *
	 * @param string $dir Package directory; defaults to the plugin's /demo.
	 */
	public function __construct( $dir = '' ) {
		$this->dir = trailingslashit( '' !== $dir ? $dir : QE_EDU_CORE_DIR . 'demo' );
	}

	/**
	 * Read and decode a JSON file inside the package.
	 *
	 * @param string $file Relative path.
	 * @return array<mixed>
	 */
	public function json( $file ) {
		$path = $this->dir . $file;
		$raw  = is_readable( $path ) ? file_get_contents( $path ) : false; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local package file.
		$data = false !== $raw ? json_decode( $raw, true ) : null;

		return is_array( $data ) ? $data : array();
	}

	/**
	 * The manifest, with every section present.
	 *
	 * @return array<string,mixed>
	 */
	public function manifest() {
		if ( null === $this->manifest ) {
			$this->manifest = array_merge(
				array(
					'images'     => array(),
					'posts'      => array(),
					'pages'      => array(),
					'templates'  => array(),
					'menus'      => array(),
					'settings'   => array(),
					'front_page' => '',
					'posts_page' => '',
				),
				$this->json( 'manifest.json' )
			);
		}

		return $this->manifest;
	}

	/**
	 * Whether the package is usable.
	 *
	 * @return bool
	 */
	public function valid() {
		return array() !== $this->manifest()['pages'];
	}

	/**
	 * Pages keyed by their key.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function pages() {
		return array_column( $this->manifest()['pages'], null, 'key' );
	}

	/**
	 * One section of the manifest.
	 *
	 * @param string $section images|posts|templates|menus|settings.
	 * @return array<mixed>
	 */
	public function get( $section ) {
		$manifest = $this->manifest();

		return isset( $manifest[ $section ] ) && is_array( $manifest[ $section ] ) ? $manifest[ $section ] : array();
	}

	/**
	 * A top-level string (front_page, posts_page).
	 *
	 * @param string $key Key.
	 * @return string
	 */
	public function value( $key ) {
		$manifest = $this->manifest();

		return isset( $manifest[ $key ] ) && is_string( $manifest[ $key ] ) ? $manifest[ $key ] : '';
	}

	/**
	 * Absolute path of an image file, or '' when missing.
	 *
	 * @param string $key Image key.
	 * @return string
	 */
	public function image_path( $key ) {
		$images = $this->get( 'images' );
		if ( ! isset( $images[ $key ]['file'] ) ) {
			return '';
		}

		$path = $this->dir . 'images/' . basename( (string) $images[ $key ]['file'] );

		return is_readable( $path ) ? $path : '';
	}
}
