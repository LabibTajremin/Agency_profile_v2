<?php
/**
 * Base for SEO tests: conditional tags and plugin detection helpers.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Plugin\Seo;

use Brain\Monkey\Functions;
use QeEdu\Tests\Plugin\PluginTestCase;

/**
 * SEO test case.
 */
abstract class SeoTestCase extends PluginTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->conditionals( array() );
		Functions\when( 'get_queried_object_id' )->justReturn( 0 );
		Functions\when( 'wp_strip_all_tags' )->alias( 'strip_tags' );
		Functions\when( 'strip_shortcodes' )->returnArg();
		Functions\when( 'get_post_types' )->justReturn(
			array(
				'post'       => 'post',
				'page'       => 'page',
				'attachment' => 'attachment',
				'qe_event'   => 'qe_event',
			)
		);
	}

	/**
	 * Set which conditional tags are true.
	 *
	 * @param string[] $true Tags.
	 * @return void
	 */
	protected function conditionals( $true ) {
		foreach ( array( 'is_singular', 'is_front_page', 'is_home', 'is_search', 'is_404', 'is_archive', 'is_category', 'is_tag', 'is_tax', 'is_post_type_archive', 'is_admin' ) as $tag ) {
			Functions\when( $tag )->justReturn( in_array( $tag, $true, true ) );
		}
	}

	/**
	 * Pretend another SEO plugin is (not) active.
	 *
	 * @param string $plugin yoast|rankmath|''.
	 * @return void
	 */
	protected function seo_plugin( $plugin ) {
		\Patchwork\redefine(
			'defined',
			static function ( $name ) use ( $plugin ) {
				if ( 'WPSEO_VERSION' === $name ) {
					return 'yoast' === $plugin;
				}
				if ( 'RANK_MATH_VERSION' === $name ) {
					return false;
				}
				return \Patchwork\relay();
			}
		);
		\Patchwork\redefine(
			'class_exists',
			static function ( $name ) use ( $plugin ) {
				if ( 'RankMath' === $name ) {
					return 'rankmath' === $plugin;
				}
				return \Patchwork\relay();
			}
		);
	}
}
