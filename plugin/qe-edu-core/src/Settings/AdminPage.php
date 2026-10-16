<?php
/**
 * "Theme Settings" admin screen: tabs, plain-English fields, live preview,
 * export / import and reset.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Settings;

use QeEdu\Core\Colors\FontPairs;
use QeEdu\Core\Colors\Palette;
use QeEdu\Core\Colors\Styles;

defined( 'ABSPATH' ) || exit;

/**
 * Admin page.
 */
class AdminPage {

	/**
	 * Menu slug.
	 */
	const SLUG = 'qe-edu-settings';

	/**
	 * Capability required for everything on this screen.
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_post_qe_edu_save_settings', array( $this, 'handle_save' ) );
		add_action( 'admin_post_qe_edu_export_settings', array( $this, 'handle_export' ) );
		add_action( 'admin_post_qe_edu_import_settings', array( $this, 'handle_import' ) );
		add_action( 'admin_post_qe_edu_reset_settings', array( $this, 'handle_reset' ) );
	}

	/**
	 * Add the top-level menu.
	 *
	 * @return void
	 */
	public function menu() {
		add_menu_page(
			__( 'Theme Settings', 'qe-edu' ),
			__( 'Theme Settings', 'qe-edu' ),
			self::CAPABILITY,
			self::SLUG,
			array( $this, 'render' ),
			'dashicons-admin-appearance',
			59
		);
	}

	/**
	 * URL of a tab.
	 *
	 * @param string              $tab  Tab id.
	 * @param array<string,mixed> $args Extra query args.
	 * @return string
	 */
	public static function url( $tab = 'brand', $args = array() ) {
		return add_query_arg(
			array_merge(
				array(
					'page' => self::SLUG,
					'tab'  => $tab,
				),
				$args
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * The current tab (from the query string), validated.
	 *
	 * @return string
	 */
	public static function current_tab() {
		$tabs = Schema::tabs();
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'brand'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation.

		return isset( $tabs[ $tab ] ) ? $tab : 'brand';
	}

	/**
	 * Data for the admin script (live preview + media pickers).
	 *
	 * @return array<string,mixed>
	 */
	public static function script_data() {
		$palettes = array();
		foreach ( array_keys( Palette::accents() ) as $slug ) {
			$palettes[ $slug ] = Palette::resolve( $slug );
		}

		$fonts = array();
		foreach ( array_keys( FontPairs::all() ) as $slug ) {
			$pair           = FontPairs::get( $slug );
			$fonts[ $slug ] = array(
				'heading' => FontPairs::stack( $pair['heading'] ),
				'body'    => FontPairs::stack( $pair['body'] ),
				'url'     => FontPairs::google_url( $slug ),
			);
		}

		return array(
			'palettes'     => $palettes,
			'fonts'        => $fonts,
			'defaultPair'  => FontPairs::DEFAULT_PAIR,
			'scales'       => Styles::heading_scales(),
			'minContrast'  => Palette::MIN_CONTRAST,
			'lightSurface' => Palette::LIGHT_SURFACE,
			'darkSurface'  => Palette::DARK_SURFACE,
			'i18n'         => array(
				/* translators: %s: contrast ratio such as 3.2. */
				'lowContrast' => __( 'Text on this colour would be hard to read (best contrast %s:1), so the site uses a slightly darker shade of it.', 'qe-edu' ),
				/* translators: %s: contrast ratio such as 7.1. */
				'okContrast'  => __( 'Good contrast (%s:1).', 'qe-edu' ),
				'choose'      => __( 'Choose file', 'qe-edu' ),
				'use'         => __( 'Use this file', 'qe-edu' ),
			),
		);
	}

	/**
	 * Enqueue admin assets on this screen only.
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public function assets( $hook_suffix ) {
		if ( 'toplevel_page_' . self::SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'qe-edu-admin', QE_EDU_CORE_URL . 'assets/css/admin.css', array(), QE_EDU_CORE_VERSION );
		wp_enqueue_script( 'qe-edu-admin', QE_EDU_CORE_URL . 'assets/js/admin.min.js', array(), QE_EDU_CORE_VERSION, true );
		wp_localize_script( 'qe-edu-admin', 'qeEduAdmin', self::script_data() );
	}

	/**
	 * Stop unless the current user may change settings. Every handler also
	 * verifies its own nonce with check_admin_referer().
	 *
	 * @return void
	 */
	protected function guard() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You are not allowed to change these settings.', 'qe-edu' ), '', array( 'response' => 403 ) );
		}
	}

	/**
	 * Redirect back to a tab with a notice, then stop.
	 *
	 * @param string $tab    Tab id.
	 * @param string $notice Notice code.
	 * @return void
	 */
	protected function back( $tab, $notice ) {
		wp_safe_redirect( self::url( $tab, array( 'qe_notice' => $notice ) ) );
		$this->halt();
	}

	/**
	 * Stop the request (separate method so tests can override it).
	 *
	 * @return void
	 */
	protected function halt() {
		exit;
	}

	/**
	 * Save one tab.
	 *
	 * @return void
	 */
	public function handle_save() {
		$this->guard();
		check_admin_referer( 'qe_edu_save_settings' );

		$tabs = Schema::tabs();
		$tab  = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : '';

		if ( ! isset( $tabs[ $tab ] ) ) {
			$this->back( 'brand', 'error' );
			return;
		}

		$input    = isset( $_POST[ Settings::OPTION ] ) ? wp_unslash( $_POST[ Settings::OPTION ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised field by field in Settings::sanitize().
		$settings = Settings::save_tab( $tab, $input );

		if ( 'brand' === $tab ) {
			self::sync_brand( $settings );
		}

		$this->back( $tab, 'saved' );
	}

	/**
	 * Mirror the logo and site icon into the WordPress core settings, so the
	 * Customizer and every theme feature that reads them stay in sync.
	 *
	 * @param array<string,mixed> $settings Saved settings.
	 * @return void
	 */
	public static function sync_brand( $settings ) {
		$logo = absint( Settings::get_path( $settings, 'brand.logo', 0 ) );
		$icon = absint( Settings::get_path( $settings, 'brand.favicon', 0 ) );

		if ( $logo ) {
			set_theme_mod( 'custom_logo', $logo );
		} else {
			remove_theme_mod( 'custom_logo' );
		}

		update_option( 'site_icon', $icon );
	}

	/**
	 * Download the settings as JSON.
	 *
	 * @return void
	 */
	public function handle_export() {
		$this->guard();
		check_admin_referer( 'qe_edu_export_settings' );

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=qe-edu-settings-' . gmdate( 'Y-m-d' ) . '.json' );
		echo Settings::export(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON download.
		$this->halt();
	}

	/**
	 * Import settings from an uploaded JSON file.
	 *
	 * @return void
	 */
	public function handle_import() {
		$this->guard();
		check_admin_referer( 'qe_edu_import_settings' );

		$file = isset( $_FILES['qe_edu_import']['tmp_name'] ) ? sanitize_text_field( $_FILES['qe_edu_import']['tmp_name'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		$ok   = '' !== $file && is_uploaded_file( $file ) && Settings::import( (string) file_get_contents( $file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local uploaded file.

		if ( $ok ) {
			self::sync_brand( Settings::stored() );
		}

		$this->back( 'tools', $ok ? 'imported' : 'import_error' );
	}

	/**
	 * Reset one tab to defaults.
	 *
	 * @return void
	 */
	public function handle_reset() {
		$this->guard();
		check_admin_referer( 'qe_edu_reset_settings' );

		$tab = isset( $_POST['reset_tab'] ) ? sanitize_key( wp_unslash( $_POST['reset_tab'] ) ) : '';

		$this->back( 'tools', Settings::reset_tab( $tab ) ? 'reset' : 'error' );
	}

	/**
	 * Notice messages.
	 *
	 * @return array<string,array<int,string>> code => [type, message].
	 */
	public static function notices() {
		return array(
			'saved'        => array( 'success', __( 'Settings saved.', 'qe-edu' ) ),
			'imported'     => array( 'success', __( 'Settings imported.', 'qe-edu' ) ),
			'reset'        => array( 'success', __( 'Section reset to its defaults.', 'qe-edu' ) ),
			'import_error' => array( 'error', __( 'That file could not be imported. Please choose a settings file exported from this screen.', 'qe-edu' ) ),
			'error'        => array( 'error', __( 'Something went wrong. Please try again.', 'qe-edu' ) ),
		);
	}

	/**
	 * Render the screen.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		$tabs    = Schema::tabs();
		$current = self::current_tab();
		$tab     = $tabs[ $current ];
		$notices = self::notices();
		$notice  = isset( $_GET['qe_notice'] ) ? sanitize_key( wp_unslash( $_GET['qe_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.

		echo '<div class="wrap qe-settings">';
		echo '<h1>' . esc_html__( 'Theme Settings', 'qe-edu' ) . '</h1>';

		if ( isset( $notices[ $notice ] ) ) {
			printf( '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>', esc_attr( $notices[ $notice ][0] ), esc_html( $notices[ $notice ][1] ) );
		}

		echo '<nav class="nav-tab-wrapper qe-settings__tabs">';
		foreach ( $tabs as $id => $definition ) {
			printf(
				'<a href="%1$s" class="nav-tab%2$s"%3$s>%4$s</a>',
				esc_url( self::url( $id ) ),
				$id === $current ? ' nav-tab-active' : '',
				$id === $current ? ' aria-current="page"' : '',
				esc_html( $definition['label'] )
			);
		}
		echo '</nav>';

		printf( '<p class="qe-settings__intro">%s</p>', esc_html( $tab['intro'] ) );

		echo '<div class="qe-settings__body' . ( ! empty( $tab['preview'] ) ? ' has-preview' : '' ) . '">';

		if ( 'tools' === $current ) {
			$this->render_tools( $tabs );
		} else {
			$this->render_form( $current );
		}

		if ( ! empty( $tab['preview'] ) ) {
			printf(
				'<div class="qe-settings__preview"><p class="qe-settings__preview-label">%1$s</p><iframe title="%2$s" src="%3$s" data-qe-preview loading="lazy"></iframe></div>',
				esc_html__( 'Live preview — changes show here before you save.', 'qe-edu' ),
				esc_attr__( 'Live preview of the home page', 'qe-edu' ),
				esc_url( home_url( '/' ) )
			);
		}

		echo '</div></div>';
	}

	/**
	 * Render a tab's form.
	 *
	 * @param string $id Tab id.
	 * @return void
	 */
	protected function render_form( $id ) {
		$settings = Settings::all();

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="qe-settings__form" data-qe-form>';
		wp_nonce_field( 'qe_edu_save_settings' );
		echo '<input type="hidden" name="action" value="qe_edu_save_settings">';
		echo '<input type="hidden" name="tab" value="' . esc_attr( $id ) . '">';

		$group = null;
		foreach ( Schema::fields() as $path => $field ) {
			if ( $field['tab'] !== $id ) {
				continue;
			}

			$field_group = isset( $field['group'] ) ? $field['group'] : '';
			if ( $field_group !== $group ) {
				if ( null !== $group ) {
					echo '</fieldset>';
				}
				echo '<fieldset class="qe-settings__group">';
				if ( '' !== $field_group ) {
					echo '<legend>' . esc_html( $field_group ) . '</legend>';
				}
				$group = $field_group;
			}

			$this->render_field( $field, Settings::get_path( $settings, $path ) );
		}

		if ( null !== $group ) {
			echo '</fieldset>';
		}

		submit_button( __( 'Save changes', 'qe-edu' ) );
		echo '</form>';
	}

	/**
	 * Form field name for a path: qe_edu_settings[a][b].
	 *
	 * @param string $path Dotted path.
	 * @return string
	 */
	public static function field_name( $path ) {
		return Settings::OPTION . '[' . str_replace( '.', '][', $path ) . ']';
	}

	/**
	 * Form field id for a path.
	 *
	 * @param string $path Dotted path.
	 * @return string
	 */
	public static function field_id( $path ) {
		return 'qe-' . str_replace( array( '.', '_' ), '-', $path );
	}

	/**
	 * Render one field with its label and help text.
	 *
	 * @param array<string,mixed> $field Field definition (from Schema::fields()).
	 * @param mixed               $value Current value.
	 * @return void
	 */
	public function render_field( $field, $value ) {
		$id   = self::field_id( $field['path'] );
		$name = self::field_name( $field['path'] );
		$type = $field['type'];

		printf( '<div class="qe-field qe-field--%1$s" data-qe-field="%2$s">', esc_attr( $type ), esc_attr( $field['path'] ) );

		if ( 'checkbox' === $type ) {
			printf(
				'<label for="%1$s" class="qe-field__check"><input type="checkbox" id="%1$s" name="%2$s" value="1"%3$s> %4$s</label>',
				esc_attr( $id ),
				esc_attr( $name ),
				checked( (bool) $value, true, false ),
				esc_html( $field['label'] )
			);
		} else {
			printf( '<label for="%1$s" class="qe-field__label">%2$s</label>', esc_attr( $id ), esc_html( $field['label'] ) );
			$this->render_control( $field, $value, $id, $name );
		}

		if ( '' !== $field['help'] ) {
			printf( '<p class="description">%s</p>', esc_html( $field['help'] ) );
		}

		/**
		 * Fires after a settings field is rendered (used e.g. by the SEO
		 * module to list the 404 log under the redirects table).
		 *
		 * @param array<string,mixed> $field Field definition.
		 */
		do_action( 'qe_edu_settings_after_field', $field );

		echo '</div>';
	}

	/**
	 * Render the input control for a field.
	 *
	 * @param array<string,mixed> $field Field definition.
	 * @param mixed               $value Current value.
	 * @param string              $id    Element id.
	 * @param string              $name  Input name.
	 * @return void
	 */
	protected function render_control( $field, $value, $id, $name ) {
		$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';

		switch ( $field['type'] ) {
			case 'textarea':
			case 'code':
				printf(
					'<textarea id="%1$s" name="%2$s" rows="4" class="large-text%3$s" placeholder="%4$s">%5$s</textarea>',
					esc_attr( $id ),
					esc_attr( $name ),
					'code' === $field['type'] ? ' code' : '',
					esc_attr( $placeholder ),
					esc_textarea( (string) $value )
				);
				break;

			case 'number':
				printf(
					'<input type="number" id="%1$s" name="%2$s" value="%3$s" min="%4$d" max="%5$d" class="small-text">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( (string) $value ),
					(int) $field['min'],
					(int) $field['max']
				);
				break;

			case 'select':
				printf( '<select id="%1$s" name="%2$s">', esc_attr( $id ), esc_attr( $name ) );
				foreach ( $field['options'] as $option => $label ) {
					printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $option ), selected( (string) $value, (string) $option, false ), esc_html( $label ) );
				}
				echo '</select>';
				break;

			case 'color':
				printf(
					'<input type="color" id="%1$s" name="%2$s" value="%3$s" data-qe-custom-color> <span class="qe-contrast" aria-live="polite" data-qe-contrast></span>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( strtolower( (string) $value ) )
				);
				break;

			case 'swatch':
				$this->render_swatches( $value, $id, $name );
				break;

			case 'media':
				$this->render_media( $field, (int) $value, $id, $name );
				break;

			case 'template':
				$this->render_template_select( (int) $value, $id, $name );
				break;

			case 'redirects':
				$this->render_redirects( is_array( $value ) ? $value : array(), $name );
				break;

			default:
				$types = array(
					'url'   => 'url',
					'email' => 'email',
					'tel'   => 'tel',
				);
				printf(
					'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="regular-text" placeholder="%5$s">',
					esc_attr( isset( $types[ $field['type'] ] ) ? $types[ $field['type'] ] : 'text' ),
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( (string) $value ),
					esc_attr( $placeholder )
				);
		}
	}

	/**
	 * The 12 accent swatches plus "Custom", as an accessible radio group.
	 *
	 * @param mixed  $value Selected slug.
	 * @param string $id    Base id.
	 * @param string $name  Input name.
	 * @return void
	 */
	protected function render_swatches( $value, $id, $name ) {
		echo '<div class="qe-swatches" role="radiogroup" aria-labelledby="' . esc_attr( $id ) . '-legend">';
		echo '<span id="' . esc_attr( $id ) . '-legend" class="screen-reader-text">' . esc_html__( 'Accent colour', 'qe-edu' ) . '</span>';

		$swatches = array();
		foreach ( Palette::accents() as $slug => $accent ) {
			$swatches[ $slug ] = array( $accent['name'], $accent['primary'] );
		}
		$swatches[ Palette::CUSTOM ] = array( __( 'Custom', 'qe-edu' ), '' );

		foreach ( $swatches as $slug => $swatch ) {
			printf(
				'<label class="qe-swatch%1$s"><input type="radio" name="%2$s" value="%3$s"%4$s data-qe-accent><span class="qe-swatch__dot"%5$s></span><span class="qe-swatch__name">%6$s</span></label>',
				Palette::CUSTOM === $slug ? ' qe-swatch--custom' : '',
				esc_attr( $name ),
				esc_attr( $slug ),
				checked( (string) $value, $slug, false ),
				'' !== $swatch[1] ? ' style="background:' . esc_attr( $swatch[1] ) . '"' : '',
				esc_html( $swatch[0] )
			);
		}

		echo '</div>';
	}

	/**
	 * Media picker: hidden attachment id, preview and buttons.
	 *
	 * @param array<string,mixed> $field Field.
	 * @param int                 $value Attachment id.
	 * @param string              $id    Element id.
	 * @param string              $name  Input name.
	 * @return void
	 */
	protected function render_media( $field, $value, $id, $name ) {
		$url     = $value ? wp_get_attachment_url( $value ) : '';
		$is_img  = 'image' === $field['mime'];
		$preview = '';

		if ( $url ) {
			$preview = $is_img
				? '<img src="' . esc_url( $url ) . '" alt="">'
				: '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( wp_basename( $url ) ) . '</a>';
		}

		printf(
			'<div class="qe-media" data-qe-media data-mime="%1$s"><input type="hidden" id="%2$s" name="%3$s" value="%4$s" data-qe-media-id><div class="qe-media__preview" data-qe-media-preview>%5$s</div><button type="button" class="button" data-qe-media-choose>%6$s</button> <button type="button" class="button-link qe-media__remove" data-qe-media-remove%7$s>%8$s</button></div>',
			esc_attr( $field['mime'] ),
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr( (string) $value ),
			$preview, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			esc_html__( 'Choose file', 'qe-edu' ),
			$value ? '' : ' hidden',
			esc_html__( 'Remove', 'qe-edu' )
		);
	}

	/**
	 * Elementor template select.
	 *
	 * @param int    $value Selected template id.
	 * @param string $id    Element id.
	 * @param string $name  Input name.
	 * @return void
	 */
	protected function render_template_select( $value, $id, $name ) {
		$templates = get_posts(
			array(
				'post_type'      => 'elementor_library',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		printf( '<select id="%1$s" name="%2$s">', esc_attr( $id ), esc_attr( $name ) );
		printf( '<option value="0">%s</option>', esc_html__( 'Built-in design (no Elementor template)', 'qe-edu' ) );
		foreach ( $templates as $template ) {
			printf( '<option value="%1$d"%2$s>%3$s</option>', (int) $template->ID, selected( $value, (int) $template->ID, false ), esc_html( $template->post_title ) );
		}
		echo '</select>';

		if ( ! $templates ) {
			printf( '<p class="description">%s</p>', esc_html__( 'No Elementor templates yet. Import the demo (Tools tab) or create one in Elementor → Templates.', 'qe-edu' ) );
		}
	}

	/**
	 * Redirects table: existing rows plus three empty rows; "Add row" clones.
	 *
	 * @param array<int,array<string,string>> $rows Rows.
	 * @param string                          $name Base input name.
	 * @return void
	 */
	protected function render_redirects( $rows, $name ) {
		$rows = array_merge(
			$rows,
			array_fill(
				0,
				3,
				array(
					'from' => '',
					'to'   => '',
				)
			)
		);

		echo '<table class="widefat striped qe-redirects" data-qe-redirects><thead><tr>';
		echo '<th scope="col">' . esc_html__( 'Old address (e.g. /old-page/)', 'qe-edu' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'New address (e.g. /new-page/ or a full link)', 'qe-edu' ) . '</th>';
		echo '</tr></thead><tbody>';
		foreach ( array_values( $rows ) as $i => $row ) {
			printf(
				'<tr><td><input type="text" class="regular-text" name="%1$s[%2$d][from]" value="%3$s" aria-label="%5$s"></td><td><input type="text" class="regular-text" name="%1$s[%2$d][to]" value="%4$s" aria-label="%6$s"></td></tr>',
				esc_attr( $name ),
				(int) $i,
				esc_attr( $row['from'] ),
				esc_attr( $row['to'] ),
				esc_attr__( 'Old address', 'qe-edu' ),
				esc_attr__( 'New address', 'qe-edu' )
			);
		}
		echo '</tbody></table>';
		printf( '<p><button type="button" class="button" data-qe-add-row>%s</button></p>', esc_html__( 'Add another redirect', 'qe-edu' ) );
	}

	/**
	 * Tools tab.
	 *
	 * @param array<string,array<string,mixed>> $tabs Tabs.
	 * @return void
	 */
	protected function render_tools( $tabs ) {
		echo '<div class="qe-tools">';

		/**
		 * Fires at the top of the Tools tab (the demo importer adds its card here).
		 */
		do_action( 'qe_edu_tools_tab' );

		// Export.
		echo '<section class="qe-card"><h2>' . esc_html__( 'Back up your settings', 'qe-edu' ) . '</h2>';
		echo '<p>' . esc_html__( 'Downloads a small file with every Theme Settings value. Keep it safe or use it on another site.', 'qe-edu' ) . '</p>';
		printf(
			'<a class="button button-primary" href="%s">%s</a></section>',
			esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=qe_edu_export_settings' ), 'qe_edu_export_settings' ) ),
			esc_html__( 'Download settings file', 'qe-edu' )
		);

		// Import.
		echo '<section class="qe-card"><h2>' . esc_html__( 'Restore settings', 'qe-edu' ) . '</h2>';
		echo '<p>' . esc_html__( 'Choose a settings file you downloaded earlier. Your current settings will be replaced.', 'qe-edu' ) . '</p>';
		echo '<form method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'qe_edu_import_settings' );
		echo '<input type="hidden" name="action" value="qe_edu_import_settings">';
		echo '<label for="qe-edu-import" class="screen-reader-text">' . esc_html__( 'Settings file', 'qe-edu' ) . '</label>';
		echo '<input type="file" id="qe-edu-import" name="qe_edu_import" accept="application/json,.json" required> ';
		submit_button( __( 'Restore', 'qe-edu' ), 'secondary', 'submit', false );
		echo '</form></section>';

		// Reset.
		echo '<section class="qe-card"><h2>' . esc_html__( 'Reset a section', 'qe-edu' ) . '</h2>';
		echo '<p>' . esc_html__( 'Puts one tab back to the way it was when the theme was installed. Other tabs are not touched.', 'qe-edu' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'qe_edu_reset_settings' );
		echo '<input type="hidden" name="action" value="qe_edu_reset_settings">';
		echo '<label for="qe-edu-reset-tab" class="screen-reader-text">' . esc_html__( 'Section', 'qe-edu' ) . '</label>';
		echo '<select id="qe-edu-reset-tab" name="reset_tab">';
		foreach ( $tabs as $id => $definition ) {
			if ( $definition['fields'] ) {
				printf( '<option value="%1$s">%2$s</option>', esc_attr( $id ), esc_html( $definition['label'] ) );
			}
		}
		echo '</select> ';
		submit_button( __( 'Reset section', 'qe-edu' ), 'delete', 'submit', false );
		echo '</form></section>';

		echo '</div>';
	}
}
