<?php
/**
 * Extra fields on top-level menu items (Appearance → Menus) used by the
 * Mega Menu widget: number of columns and an optional image.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Elementor;

use QeEdu\Core\Admin\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Menu fields.
 */
final class MenuFields {

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_nav_menu_item_custom_fields', array( $this, 'render' ), 10, 3 );
		add_action( 'wp_update_nav_menu_item', array( $this, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
	}

	/**
	 * Columns of a menu item's panel (0 = normal dropdown).
	 *
	 * @param int $item_id Menu item id.
	 * @return int
	 */
	public static function columns( $item_id ) {
		$columns = (int) get_post_meta( $item_id, '_qe_mega_columns', true );

		return in_array( $columns, array( 2, 3, 4 ), true ) ? $columns : 0;
	}

	/**
	 * Panel image of a menu item.
	 *
	 * @param int $item_id Menu item id.
	 * @return int
	 */
	public static function image( $item_id ) {
		return absint( get_post_meta( $item_id, '_qe_mega_image', true ) );
	}

	/**
	 * Media picker on the Menus screen.
	 *
	 * @param string $hook_suffix Admin page.
	 * @return void
	 */
	public function assets( $hook_suffix ) {
		if ( 'nav-menus.php' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'qe-edu-admin', QE_EDU_CORE_URL . 'assets/css/admin.css', array(), QE_EDU_CORE_VERSION );
		wp_enqueue_script( 'qe-edu-admin', QE_EDU_CORE_URL . 'assets/js/admin.min.js', array(), QE_EDU_CORE_VERSION, true );
		wp_localize_script( 'qe-edu-admin', 'qeEduAdmin', \QeEdu\Core\Settings\AdminPage::script_data() );
	}

	/**
	 * Print the fields (top-level items only).
	 *
	 * @param int    $item_id Item id.
	 * @param object $item    Item (unused; part of the hook signature).
	 * @param int    $depth   Depth.
	 * @return void
	 */
	public function render( $item_id, $item, $depth ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed -- hook signature.
		if ( 0 !== (int) $depth ) {
			return;
		}

		$columns = self::columns( $item_id );

		echo '<div class="qe-menu-fields description-wide">';
		printf(
			'<p><label for="qe-mega-columns-%1$d">%2$s</label><br><select id="qe-mega-columns-%1$d" name="qe_mega_columns[%1$d]">',
			(int) $item_id,
			esc_html__( 'Mega panel (Mega Menu widget)', 'qe-edu-core' )
		);
		$options = array(
			0 => __( 'No — normal dropdown', 'qe-edu-core' ),
			2 => __( '2 columns', 'qe-edu-core' ),
			3 => __( '3 columns', 'qe-edu-core' ),
			4 => __( '4 columns', 'qe-edu-core' ),
		);
		foreach ( $options as $value => $label ) {
			printf( '<option value="%1$d"%2$s>%3$s</option>', (int) $value, selected( $columns, $value, false ), esc_html( $label ) );
		}
		echo '</select></p>';
		printf( '<p>%s</p>', esc_html__( 'Panel image (optional):', 'qe-edu-core' ) );
		echo Fields::media( 'image', self::image( $item_id ), 'qe-mega-image-' . (int) $item_id, 'qe_mega_image[' . (int) $item_id . ']' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Fields::media().
		echo '</div>';
	}

	/**
	 * Save the fields. WordPress verifies the menu form nonce before this runs.
	 *
	 * @param int $menu_id Menu id.
	 * @param int $item_id Item id.
	 * @return void
	 */
	public function save( $menu_id, $item_id ) {
		if ( ! current_user_can( 'edit_theme_options' ) || ! isset( $_POST['update-nav-menu-nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['update-nav-menu-nonce'] ) ), 'update-nav_menu' ) ) {
			return;
		}

		$columns = isset( $_POST['qe_mega_columns'][ $item_id ] ) ? absint( $_POST['qe_mega_columns'][ $item_id ] ) : 0;
		$image   = isset( $_POST['qe_mega_image'][ $item_id ] ) ? absint( $_POST['qe_mega_image'][ $item_id ] ) : 0;

		if ( in_array( $columns, array( 2, 3, 4 ), true ) ) {
			update_post_meta( $item_id, '_qe_mega_columns', $columns );
		} else {
			delete_post_meta( $item_id, '_qe_mega_columns' );
		}

		if ( $image ) {
			update_post_meta( $item_id, '_qe_mega_image', $image );
		} else {
			delete_post_meta( $item_id, '_qe_mega_image' );
		}
	}
}
