<?php
/**
 * Tests for inc/tgmpa.php.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Theme;

use Brain\Monkey\Functions;

/**
 * Required plugins notice.
 */
final class TgmpaTest extends ThemeTestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'self_admin_url' )->alias(
			static function ( $path ) {
				return 'https://example.test/wp-admin/' . $path;
			}
		);
		Functions\when( 'wp_nonce_url' )->alias(
			static function ( $url, $action ) {
				return $url . '&_wpnonce=' . $action;
			}
		);
	}

	public function test_required_and_missing_plugins() {
		$this->assertSame( array( 'elementor', 'qe-edu-core' ), array_column( qe_edu_required_plugins(), 'slug' ) );
		$this->assertCount( 2, qe_edu_missing_plugins() );

		do_action( 'elementor/loaded' );
		$this->assertSame( array( 'qe-edu-core' ), array_column( qe_edu_missing_plugins(), 'slug' ) );
	}

	public function test_action_links() {
		$plugins = qe_edu_required_plugins();

		// Fixture: tests/php/fixtures/plugins/elementor/elementor.php exists (installed).
		$this->assertStringContainsString( 'action=activate&amp;plugin=elementor%2Felementor.php', $this->decode( qe_edu_plugin_action_link( $plugins[0] ) ) );
		$this->assertStringContainsString( '>Activate<', qe_edu_plugin_action_link( $plugins[0] ) );

		// Bundled, not installed.
		$this->assertStringContainsString( 'plugin-install.php?tab=upload', qe_edu_plugin_action_link( $plugins[1] ) );

		// Not bundled, not installed.
		$other = array(
			'slug'    => 'missing',
			'file'    => 'missing/missing.php',
			'bundled' => false,
		);
		$this->assertStringContainsString( 'action=install-plugin&amp;plugin=missing', $this->decode( qe_edu_plugin_action_link( $other ) ) );
	}

	/**
	 * Normalise the stub escaping for comparisons.
	 *
	 * @param string $html HTML.
	 * @return string
	 */
	private function decode( $html ) {
		return str_replace( '&amp;amp;', '&amp;', str_replace( '&', '&amp;', html_entity_decode( $html ) ) );
	}

	public function test_notice_requires_capability() {
		Functions\when( 'current_user_can' )->justReturn( false );
		$this->assertSame( '', $this->capture( 'qe_edu_required_plugins_notice' ) );
	}

	public function test_notice_hidden_when_nothing_missing() {
		Functions\when( 'current_user_can' )->justReturn( true );
		do_action( 'elementor/loaded' );
		do_action( 'qe_edu_core_loaded' );
		$this->assertSame( '', $this->capture( 'qe_edu_required_plugins_notice' ) );
	}

	public function test_notice_lists_missing_plugins() {
		Functions\when( 'current_user_can' )->justReturn( true );
		$out = $this->capture( 'qe_edu_required_plugins_notice' );
		$this->assertStringContainsString( 'notice-warning', $out );
		$this->assertSame( 2, substr_count( $out, '<li>' ) );
	}
}
