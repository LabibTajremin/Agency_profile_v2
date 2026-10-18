<?php
/**
 * "Import demo" card on Theme Settings → Tools, its form handler and the
 * `wp qe-edu demo-import [--page=<key>]` command.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\DemoImport;

use QeEdu\Core\Settings\AdminPage;

defined( 'ABSPATH' ) || exit;

/**
 * Demo import admin.
 */
class Admin {

	/**
	 * admin-post action and nonce action.
	 */
	const ACTION = 'qe_edu_demo_import';

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'qe_edu_tools_tab', array( $this, 'card' ) );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );

		if ( class_exists( 'WP_CLI' ) ) {
			\WP_CLI::add_command( 'qe-edu demo-import', array( $this, 'cli' ) );
		}
	}

	/**
	 * The importer (overridable in tests).
	 *
	 * @return Importer
	 */
	protected function importer() {
		return new Importer();
	}

	/**
	 * The package (overridable in tests).
	 *
	 * @return Package
	 */
	protected function package() {
		return new Package();
	}

	/**
	 * Whether Elementor is active.
	 *
	 * @return bool
	 */
	protected function elementor_active() {
		return (bool) did_action( 'elementor/loaded' );
	}

	/**
	 * Result of the last import (from the redirect).
	 *
	 * @return void
	 */
	private function result() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- display only.
		if ( ! isset( $_GET['qe_demo'] ) ) {
			return;
		}

		$created = isset( $_GET['created'] ) ? absint( $_GET['created'] ) : 0;
		$skipped = isset( $_GET['skipped'] ) ? absint( $_GET['skipped'] ) : 0;
		$page    = isset( $_GET['page_id'] ) ? absint( $_GET['page_id'] ) : 0;
		// phpcs:enable

		echo '<div class="notice notice-success inline"><p>';
		printf(
			/* translators: 1: number of items added, 2: number of items that already existed. */
			esc_html__( 'Demo imported: %1$d items added, %2$d already there (left unchanged).', 'qe-edu-core' ),
			(int) $created,
			(int) $skipped
		);
		printf(
			' <a href="%1$s">%2$s</a>',
			esc_url( $page ? (string) get_permalink( $page ) : home_url( '/' ) ),
			esc_html( $page ? __( 'View the page', 'qe-edu-core' ) : __( 'View your site', 'qe-edu-core' ) )
		);
		echo '</p></div>';
	}

	/**
	 * The Tools tab card.
	 *
	 * @return void
	 */
	public function card() {
		$package = $this->package();

		echo '<section class="qe-card qe-demo-import"><h2>' . esc_html__( 'Import the demo', 'qe-edu-core' ) . '</h2>';
		$this->result();
		echo '<p>' . esc_html__( 'Adds the demo pages, header, footer, menus, images and sample content, so you can start by changing text instead of building from scratch. Anything you already have is left as it is, and running it again never creates duplicates.', 'qe-edu-core' ) . '</p>';

		if ( ! $package->valid() ) {
			echo '<p class="description">' . esc_html__( 'The demo files are missing. Please reinstall the QE Edu Core plugin.', 'qe-edu-core' ) . '</p></section>';
			return;
		}

		$ready = $this->elementor_active();
		if ( ! $ready ) {
			echo '<p class="notice notice-warning inline">' . esc_html__( 'Please install and activate Elementor first — the demo pages are built with it.', 'qe-edu-core' ) . '</p>';
		}

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( self::ACTION );
		echo '<input type="hidden" name="action" value="' . esc_attr( self::ACTION ) . '">';
		echo '<p><label for="qe-demo-scope"><strong>' . esc_html__( 'What to import', 'qe-edu-core' ) . '</strong></label><br>';
		echo '<select id="qe-demo-scope" name="qe_demo_scope">';
		printf( '<option value="all">%s</option>', esc_html__( 'The whole site (recommended)', 'qe-edu-core' ) );
		echo '<optgroup label="' . esc_attr__( 'Or just one page', 'qe-edu-core' ) . '">';
		foreach ( $package->pages() as $key => $page ) {
			printf( '<option value="%1$s">%2$s</option>', esc_attr( $key ), esc_html( $page['title'] ) );
		}
		echo '</optgroup></select></p>';
		echo '<p class="description">' . esc_html__( 'Importing copies about 60 small images into your Media Library and can take up to a minute.', 'qe-edu-core' ) . '</p>';
		submit_button( __( 'Import demo', 'qe-edu-core' ), 'primary', 'submit', false, $ready ? array() : array( 'disabled' => 'disabled' ) );
		echo '</form></section>';
	}

	/**
	 * Handle the form.
	 *
	 * @return void
	 */
	public function handle() {
		if ( ! current_user_can( AdminPage::CAPABILITY ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'qe-edu-core' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::ACTION );

		$scope  = isset( $_POST['qe_demo_scope'] ) ? sanitize_key( wp_unslash( $_POST['qe_demo_scope'] ) ) : 'all';
		$report = $this->importer()->run( '' !== $scope ? $scope : 'all' );

		wp_safe_redirect(
			AdminPage::url(
				'tools',
				array(
					'qe_demo' => 'done',
					'created' => $report['created'],
					'skipped' => $report['skipped'],
					'page_id' => $report['page'],
				)
			)
		);
		$this->halt();
	}

	/**
	 * Import the demo from the command line.
	 *
	 * ## OPTIONS
	 *
	 * [--page=<key>]
	 * : Import one page only (for example `contact`). Default: the whole site.
	 *
	 * @param string[]             $args       Positional arguments.
	 * @param array<string,string> $assoc_args Options.
	 * @return void
	 */
	public function cli( $args, $assoc_args ) {
		$scope  = isset( $assoc_args['page'] ) ? sanitize_key( $assoc_args['page'] ) : 'all';
		$report = $this->importer()->run( $scope );

		\WP_CLI::success( sprintf( 'Demo imported: %d added, %d already there.', $report['created'], $report['skipped'] ) );
	}

	/**
	 * Stop the request (overridable in tests).
	 *
	 * @return void
	 */
	protected function halt() {
		exit;
	}
}
