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
				'title' => __( 'Primary (accent)', 'qe-edu-core' ),
				'color' => $colors['primary'],
			),
			array(
				'_id'   => 'secondary',
				'title' => __( 'Secondary (navy)', 'qe-edu-core' ),
				'color' => '#140342',
			),
			array(
				'_id'   => 'text',
				'title' => __( 'Text', 'qe-edu-core' ),
				'color' => '#3D3A4F',
			),
			array(
				'_id'   => 'accent',
				'title' => __( 'Accent (dark)', 'qe-edu-core' ),
				'color' => $colors['dark'],
			),
		);
	}

	/**
	 * Kit button styles (Site Settings → Buttons): Elementor's core Button
	 * otherwise defaults to grey. Background and hover use the Global Colors;
	 * text uses the contrast-checked theme variables, which dark mode also
	 * overrides; the corner radius follows Theme Settings → Button shape.
	 *
	 * @param array<string,mixed> $settings Settings merged over defaults.
	 * @return array<string,mixed>
	 */
	public static function button_styles( $settings ) {
		$radii  = array(
			'rounded' => 10,
			'pill'    => 999,
			'square'  => 0,
		);
		$shape  = (string) Settings::get_path( $settings, 'colors.button_style' );
		$radius = (string) ( isset( $radii[ $shape ] ) ? $radii[ $shape ] : $radii['rounded'] );

		return array(
			'button_text_color'       => 'var(--qe-on-primary)',
			'button_hover_text_color' => 'var(--qe-on-primary-dark)',
			'button_border_radius'    => array(
				'unit'     => 'px',
				'top'      => $radius,
				'right'    => $radius,
				'bottom'   => $radius,
				'left'     => $radius,
				'isLinked' => true,
			),
			'__globals__'             => array(
				'button_background_color'       => 'globals/colors?id=primary',
				'button_hover_background_color' => 'globals/colors?id=accent',
				'button_typography_typography'  => 'globals/typography?id=accent',
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
			$font( 'primary', __( 'Headings', 'qe-edu-core' ), $pair['heading'], '700' ),
			$font( 'secondary', __( 'Sub-headings', 'qe-edu-core' ), $pair['heading'], '600' ),
			$font( 'text', __( 'Body text', 'qe-edu-core' ), $pair['body'], '400' ),
			$font( 'accent', __( 'Buttons', 'qe-edu-core' ), $pair['heading'], '600' ),
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

		$buttons                = self::button_styles( $settings );
		$current['__globals__'] = array_merge( isset( $current['__globals__'] ) && is_array( $current['__globals__'] ) ? $current['__globals__'] : array(), $buttons['__globals__'] );
		unset( $buttons['__globals__'] );
		$current = array_merge( $current, $buttons );

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
