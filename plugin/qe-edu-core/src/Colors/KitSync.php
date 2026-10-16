<?php
/**
 * Keeps the active Elementor Kit's Global Colors and Global Fonts in sync with
 * Theme Settings, so the site owner changes colours and fonts in one place.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Colors;

use QeEdu\Core\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Kit sync.
 */
final class KitSync {

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'add_option_' . Settings::OPTION, array( $this, 'sync' ), 10, 0 );
		add_action( 'update_option_' . Settings::OPTION, array( $this, 'sync' ), 10, 0 );
		add_action( 'qe_edu_core_activated', array( $this, 'sync' ), 10, 0 );
	}

	/**
	 * Global Colors for the current settings.
	 *
	 * @param array<string,mixed> $settings Settings merged over defaults.
	 * @return array<int,array<string,string>>
	 */
	public static function system_colors( $settings ) {
		$colors = Palette::resolve( (string) Settings::get_path( $settings, 'colors.accent' ), (string) Settings::get_path( $settings, 'colors.custom' ) );

		return array(
			array(
				'_id'   => 'primary',
				'title' => __( 'Primary (accent)', 'qe-edu' ),
				'color' => $colors['primary'],
			),
			array(
				'_id'   => 'secondary',
				'title' => __( 'Secondary (navy)', 'qe-edu' ),
				'color' => '#140342',
			),
			array(
				'_id'   => 'text',
				'title' => __( 'Text', 'qe-edu' ),
				'color' => '#3D3A4F',
			),
			array(
				'_id'   => 'accent',
				'title' => __( 'Accent (dark)', 'qe-edu' ),
				'color' => $colors['dark'],
			),
		);
	}

	/**
	 * Global Fonts for the current settings.
	 *
	 * @param array<string,mixed> $settings Settings merged over defaults.
	 * @return array<int,array<string,string>>
	 */
	public static function system_typography( $settings ) {
		$pair = FontPairs::get( (string) Settings::get_path( $settings, 'typography.pair' ) );

		$font = static function ( $id, $title, $family, $weight ) {
			return array(
				'_id'                    => $id,
				'title'                  => $title,
				'typography_typography'  => 'custom',
				'typography_font_family' => $family,
				'typography_font_weight' => $weight,
			);
		};

		return array(
			$font( 'primary', __( 'Headings', 'qe-edu' ), $pair['heading'], '700' ),
			$font( 'secondary', __( 'Sub-headings', 'qe-edu' ), $pair['heading'], '600' ),
			$font( 'text', __( 'Body text', 'qe-edu' ), $pair['body'], '400' ),
			$font( 'accent', __( 'Buttons', 'qe-edu' ), $pair['heading'], '600' ),
		);
	}

	/**
	 * Push the settings into the active Kit.
	 *
	 * @return bool Whether a Kit was updated.
	 */
	public function sync() {
		$kit_id = absint( get_option( 'elementor_active_kit' ) );

		if ( ! $kit_id ) {
			return false;
		}

		$settings = Settings::all();
		$current  = get_post_meta( $kit_id, '_elementor_page_settings', true );
		$current  = is_array( $current ) ? $current : array();

		$current['system_colors']     = self::system_colors( $settings );
		$current['system_typography'] = self::system_typography( $settings );

		update_post_meta( $kit_id, '_elementor_page_settings', $current );

		// Self-hosted fonts: stop Elementor fetching them from Google too.
		update_option( 'elementor_google_font', Styles::needs_google_fonts( $settings ) ? '1' : '0' );

		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}

		/**
		 * Fires after the Elementor Kit was synced with Theme Settings.
		 *
		 * @param int $kit_id Kit id.
		 */
		do_action( 'qe_edu_kit_synced', $kit_id );

		return true;
	}
}
