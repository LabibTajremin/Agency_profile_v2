<?php
/**
 * Kit sync tests (Phase 3 gate: swatches update Elementor globals).
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Plugin;

use Brain\Monkey\Actions;
use QeEdu\Core\Colors\KitSync;
use QeEdu\Core\Colors\Palette;
use QeEdu\Core\Settings\Settings;

/**
 * Kit sync.
 */
final class KitSyncTest extends PluginTestCase {

	protected function tearDown(): void {
		\Elementor\Plugin::$instance = null;
		parent::tearDown();
	}

	public function test_register() {
		$sync = new KitSync();
		$sync->register();
		$this->assertNotFalse( has_action( 'add_option_qe_edu_settings', 'QeEdu\Core\Colors\KitSync->sync()' ) );
		$this->assertNotFalse( has_action( 'update_option_qe_edu_settings', 'QeEdu\Core\Colors\KitSync->sync()' ) );
		$this->assertNotFalse( has_action( 'qe_edu_core_activated', 'QeEdu\Core\Colors\KitSync->sync()' ) );
	}

	public function test_sync_without_kit() {
		$this->assertFalse( ( new KitSync() )->sync() );
	}

	public function test_every_swatch_updates_elementor_globals() {
		$this->options['elementor_active_kit'] = 9;
		$this->meta[9]['_elementor_page_settings'] = array( 'container_width' => array( 'size' => 1240 ) );
		$files = \Mockery::mock();
		$files->shouldReceive( 'clear_cache' )->times( 12 );
		\Elementor\Plugin::$instance = (object) array( 'files_manager' => $files );
		Actions\expectDone( 'qe_edu_kit_synced' )->times( 12 )->with( 9 );

		foreach ( Palette::accents() as $slug => $accent ) {
			$this->settings( array( 'colors' => array( 'accent' => $slug ) ) );
			$this->assertTrue( ( new KitSync() )->sync() );

			$kit = $this->meta[9]['_elementor_page_settings'];
			$this->assertSame( $accent['primary'], $kit['system_colors'][0]['color'], $slug );
			$this->assertSame( $accent['dark'], $kit['system_colors'][3]['color'], $slug );
			$this->assertSame( 1240, $kit['container_width']['size'], 'Other kit settings kept' );
		}
	}

	public function test_sync_fonts_and_google_option() {
		$this->options['elementor_active_kit']     = 2;
		$this->meta[2]['_elementor_page_settings'] = 'garbage';

		$this->settings( array( 'typography' => array( 'pair' => 'poppins-inter' ) ) );
		( new KitSync() )->sync();
		$kit = $this->meta[2]['_elementor_page_settings'];
		$this->assertSame( 'Poppins', $kit['system_typography'][0]['typography_font_family'] );
		$this->assertSame( 'Inter', $kit['system_typography'][2]['typography_font_family'] );
		$this->assertSame( '1', $this->options['elementor_google_font'] );

		$this->settings( array() );
		( new KitSync() )->sync();
		$this->assertSame( '0', $this->options['elementor_google_font'] );
	}

	public function test_system_colors_for_custom() {
		$colors = KitSync::system_colors( array_replace_recursive( Settings::all(), array( 'colors' => array( 'accent' => 'custom', 'custom' => '#3366FF' ) ) ) );
		$this->assertSame( '#3366FF', $colors[0]['color'] );
		$this->assertSame( array( 'primary', 'secondary', 'text', 'accent' ), array_column( $colors, '_id' ) );
	}
}
