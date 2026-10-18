<?php
/**
 * Leads: private records created by the registration and newsletter forms.
 * Visible to administrators only, read-only, exportable as CSV.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\PostTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Leads.
 */
class Leads {

	/**
	 * Post type.
	 */
	const TYPE = 'qe_lead';

	/**
	 * Capability for everything about leads.
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public function register() {
		$this->register_type();
		add_filter( 'manage_' . self::TYPE . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . self::TYPE . '_posts_custom_column', array( $this, 'column' ), 10, 2 );
		add_action( 'add_meta_boxes_' . self::TYPE, array( $this, 'meta_box' ) );
		add_action( 'admin_post_qe_edu_export_leads', array( $this, 'export' ) );
		add_action( 'manage_posts_extra_tablenav', array( $this, 'export_button' ) );
		add_filter( 'wp_sitemaps_post_types', array( $this, 'exclude_from_sitemap' ) );
	}

	/**
	 * Register the private post type.
	 *
	 * @return void
	 */
	public function register_type() {
		$labels                 = Registrar::labels( __( 'Lead', 'qe-edu-core' ), __( 'Leads', 'qe-edu-core' ) );
		$labels['add_new_item'] = '';

		register_post_type(
			self::TYPE,
			array(
				'labels'          => $labels,
				'public'          => false,
				'show_ui'         => true,
				'show_in_rest'    => false,
				'show_in_menu'    => true,
				'menu_icon'       => 'dashicons-id-alt',
				'menu_position'   => 26,
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'capabilities'    => array(
					'create_posts'       => 'do_not_allow',
					'edit_posts'         => self::CAPABILITY,
					'edit_others_posts'  => self::CAPABILITY,
					'publish_posts'      => self::CAPABILITY,
					'read_private_posts' => self::CAPABILITY,
					'edit_post'          => self::CAPABILITY,
					'delete_post'        => self::CAPABILITY,
					'read_post'          => self::CAPABILITY,
					'delete_posts'       => self::CAPABILITY,
				),
				'map_meta_cap'    => false,
			)
		);
	}

	/**
	 * Lead types (form sources).
	 *
	 * @return array<string,string>
	 */
	public static function types() {
		return array(
			'registration' => __( 'Registration', 'qe-edu-core' ),
			'newsletter'   => __( 'Newsletter', 'qe-edu-core' ),
		);
	}

	/**
	 * Store a lead.
	 *
	 * @param string               $type   registration|newsletter.
	 * @param array<string,string> $fields Label => value (already sanitised).
	 * @param string               $email  Email address.
	 * @param string               $source URL of the page the form was on.
	 * @return int Post id (0 on failure).
	 */
	public static function create( $type, $fields, $email, $source ) {
		$name = trim( implode( ' ', array_slice( array_values( $fields ), 0, 2 ) ) );

		$post_id = wp_insert_post(
			array(
				'post_type'   => self::TYPE,
				'post_status' => 'private',
				'post_title'  => '' !== $email ? $email : $name,
				'meta_input'  => array(
					'_qe_lead_type'   => $type,
					'_qe_lead_email'  => $email,
					'_qe_lead_source' => $source,
					'_qe_lead_fields' => $fields,
				),
			)
		);

		return is_int( $post_id ) ? $post_id : 0;
	}

	/**
	 * List columns.
	 *
	 * @param array<string,string> $columns Columns.
	 * @return array<string,string>
	 */
	public function columns( $columns ) {
		return array(
			'cb'             => isset( $columns['cb'] ) ? $columns['cb'] : '',
			'title'          => __( 'Email', 'qe-edu-core' ),
			'qe_lead_type'   => __( 'Form', 'qe-edu-core' ),
			'qe_lead_source' => __( 'Page', 'qe-edu-core' ),
			'date'           => __( 'Received', 'qe-edu-core' ),
		);
	}

	/**
	 * Render a custom column.
	 *
	 * @param string $column  Column id.
	 * @param int    $post_id Post id.
	 * @return void
	 */
	public function column( $column, $post_id ) {
		if ( 'qe_lead_type' === $column ) {
			$types = self::types();
			$type  = (string) get_post_meta( $post_id, '_qe_lead_type', true );
			echo esc_html( isset( $types[ $type ] ) ? $types[ $type ] : $type );
		} elseif ( 'qe_lead_source' === $column ) {
			$source = (string) get_post_meta( $post_id, '_qe_lead_source', true );
			if ( '' !== $source ) {
				printf( '<a href="%1$s" target="_blank" rel="noopener">%2$s</a>', esc_url( $source ), esc_html( (string) wp_parse_url( $source, PHP_URL_PATH ) ) );
			}
		}
	}

	/**
	 * Read-only details box (replaces the editor).
	 *
	 * @return void
	 */
	public function meta_box() {
		add_meta_box( 'qe-edu-lead', __( 'Submitted details', 'qe-edu-core' ), array( $this, 'render' ), self::TYPE, 'normal', 'high' );
	}

	/**
	 * Render the details.
	 *
	 * @param \WP_Post $post Post.
	 * @return void
	 */
	public function render( $post ) {
		$fields = get_post_meta( $post->ID, '_qe_lead_fields', true );
		$fields = is_array( $fields ) ? $fields : array();

		echo '<table class="widefat striped"><tbody>';
		foreach ( $fields as $label => $value ) {
			printf( '<tr><th scope="row">%1$s</th><td>%2$s</td></tr>', esc_html( (string) $label ), esc_html( (string) $value ) );
		}
		printf( '<tr><th scope="row">%1$s</th><td>%2$s</td></tr>', esc_html__( 'Page', 'qe-edu-core' ), esc_html( (string) get_post_meta( $post->ID, '_qe_lead_source', true ) ) );
		echo '</tbody></table>';
	}

	/**
	 * "Download CSV" button above the leads list.
	 *
	 * @param string $which top|bottom.
	 * @return void
	 */
	public function export_button( $which ) {
		$screen = get_current_screen();
		if ( 'top' !== $which || ! $screen || self::TYPE !== $screen->post_type ) {
			return;
		}

		printf(
			'<div class="alignleft actions"><a class="button" href="%1$s">%2$s</a></div>',
			esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=qe_edu_export_leads' ), 'qe_edu_export_leads' ) ),
			esc_html__( 'Download all as CSV (Excel)', 'qe-edu-core' )
		);
	}

	/**
	 * Neutralise spreadsheet formula injection (=, +, -, @, tab, CR).
	 *
	 * @param string $value Cell value.
	 * @return string
	 */
	public static function csv_cell( $value ) {
		$value = (string) $value;

		return ( '' !== $value && false !== strpos( "=+-@\t\r", $value[0] ) ) ? "'" . $value : $value;
	}

	/**
	 * Rows for the CSV: header plus one row per lead.
	 *
	 * @return array<int,array<int,string>>
	 */
	public static function csv_rows() {
		$leads = get_posts(
			array(
				'post_type'      => self::TYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- full export requested by an administrator.
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		$records = array();
		$labels  = array();
		foreach ( $leads as $lead ) {
			$fields = get_post_meta( $lead->ID, '_qe_lead_fields', true );
			$fields = is_array( $fields ) ? $fields : array();
			$labels = array_merge( $labels, array_keys( $fields ) );

			$records[] = array( $lead, $fields );
		}
		$labels = array_values( array_unique( $labels ) );

		$rows = array( array_merge( array( __( 'Received', 'qe-edu-core' ), __( 'Form', 'qe-edu-core' ), __( 'Email', 'qe-edu-core' ) ), $labels, array( __( 'Page', 'qe-edu-core' ) ) ) );
		foreach ( $records as $record ) {
			list( $lead, $fields ) = $record;
			$row                   = array(
				get_the_date( 'Y-m-d H:i', $lead ),
				(string) get_post_meta( $lead->ID, '_qe_lead_type', true ),
				(string) get_post_meta( $lead->ID, '_qe_lead_email', true ),
			);
			foreach ( $labels as $label ) {
				$row[] = isset( $fields[ $label ] ) ? (string) $fields[ $label ] : '';
			}
			$row[]  = (string) get_post_meta( $lead->ID, '_qe_lead_source', true );
			$rows[] = array_map( array( self::class, 'csv_cell' ), $row );
		}

		return $rows;
	}

	/**
	 * Stream the CSV download.
	 *
	 * @return void
	 */
	public function export() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You are not allowed to export leads.', 'qe-edu-core' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'qe_edu_export_leads' );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=leads-' . gmdate( 'Y-m-d' ) . '.csv' );

		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- streaming to output.
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- UTF-8 BOM so Excel reads accents.
		foreach ( self::csv_rows() as $row ) {
			fputcsv( $out, $row, ',', '"', '\\' );
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		$this->halt();
	}

	/**
	 * Stop the request (overridable in tests).
	 *
	 * @return void
	 */
	protected function halt() {
		exit;
	}

	/**
	 * Never list leads in the XML sitemap.
	 *
	 * @param array<string,mixed> $post_types Post types.
	 * @return array<string,mixed>
	 */
	public function exclude_from_sitemap( $post_types ) {
		unset( $post_types[ self::TYPE ] );

		return $post_types;
	}
}
