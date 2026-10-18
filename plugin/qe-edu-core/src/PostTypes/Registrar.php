<?php
/**
 * Registers the content types, their meta and their simple edit screens.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\PostTypes;

use QeEdu\Core\Admin\Fields;
use QeEdu\Core\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Registrar.
 */
final class Registrar {

	/**
	 * Nonce action/name for the fields box.
	 */
	const NONCE = 'qe_edu_fields';

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public function register() {
		$this->register_types();
		add_action( 'qe_edu_core_activated', array( $this, 'register_types' ) );
		add_filter( 'use_block_editor_for_post_type', array( $this, 'classic_editor' ), 10, 2 );
		add_action( 'add_meta_boxes', array( $this, 'meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save' ), 10, 2 );
		add_filter( 'enter_title_here', array( $this, 'title_placeholder' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
	}

	/**
	 * Media pickers on our edit screens.
	 *
	 * @param string $hook_suffix Admin page.
	 * @return void
	 */
	public function assets( $hook_suffix ) {
		$screen = get_current_screen();

		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) || ! $screen || ! array_key_exists( $screen->post_type, Types::all() ) ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'qe-edu-admin', QE_EDU_CORE_URL . 'assets/css/admin.css', array(), QE_EDU_CORE_VERSION );
		wp_enqueue_script( 'qe-edu-admin', QE_EDU_CORE_URL . 'assets/js/admin.min.js', array(), QE_EDU_CORE_VERSION, true );
		wp_localize_script( 'qe-edu-admin', 'qeEduAdmin', \QeEdu\Core\Settings\AdminPage::script_data() );
	}

	/**
	 * Register every type and its meta (runs on init via the plugin boot).
	 *
	 * @return void
	 */
	public function register_types() {
		$rewrites = array();
		foreach ( Types::all() as $type => $definition ) {
			if ( ! self::enabled( $type ) ) {
				continue;
			}
			$slug              = self::slug( $type, $definition['slug'] );
			$rewrites[ $type ] = $definition['public'] ? $slug : '';
			$supports          = array( 'title', 'thumbnail', 'page-attributes' );
			if ( '' !== $definition['excerpt'] ) {
				$supports[] = 'excerpt';
			}
			if ( ! empty( $definition['editor'] ) ) {
				$supports[] = 'editor';
			}

			register_post_type(
				$type,
				array(
					'labels'              => self::labels( $definition['singular'], $definition['plural'] ),
					'public'              => $definition['public'],
					'publicly_queryable'  => $definition['public'],
					'exclude_from_search' => ! $definition['public'],
					'show_ui'             => true,
					'show_in_rest'        => true,
					'has_archive'         => $definition['public'],
					'rewrite'             => $definition['public'] ? array( 'slug' => $slug ) : false,
					'menu_icon'           => $definition['icon'],
					'menu_position'       => 25,
					'supports'            => $supports,
				)
			);

			foreach ( $definition['fields'] as $key => $field ) {
				register_post_meta(
					$type,
					Types::meta_key( $key ),
					array(
						'type'              => 'string',
						'single'            => true,
						'show_in_rest'      => true,
						'sanitize_callback' => static function ( $value ) use ( $field ) {
							return Types::sanitize( $field, $value );
						},
						'auth_callback'     => static function ( $allowed, $meta_key, $post_id ) {
							return current_user_can( 'edit_post', $post_id );
						},
					)
				);
			}
		}

		self::refresh_permalinks( $rewrites );
	}

	/**
	 * Whether a content type is switched on in Theme Settings → Texts.
	 *
	 * @param string $type Post type.
	 * @return bool
	 */
	public static function enabled( $type ) {
		return (bool) Settings::get( 'post_types.' . $type . '_enabled', true );
	}

	/**
	 * The web address part of a content type (Theme Settings → Texts).
	 *
	 * @param string $type     Post type.
	 * @param string $fallback Built-in slug.
	 * @return string
	 */
	public static function slug( $type, $fallback ) {
		$slug = sanitize_title( (string) Settings::get( 'post_types.' . $type . '_slug', '' ) );

		return '' !== $slug ? $slug : $fallback;
	}

	/**
	 * Rebuild the permalinks once after the addresses or types change, so new
	 * addresses work without visiting Settings → Permalinks.
	 *
	 * @param array<string,string> $rewrites Post type => slug.
	 * @return void
	 */
	private static function refresh_permalinks( $rewrites ) {
		$hash = md5( (string) wp_json_encode( $rewrites ) );
		if ( get_option( 'qe_edu_rewrite_hash' ) === $hash ) {
			return;
		}
		update_option( 'qe_edu_rewrite_hash', $hash );
		// After every plugin has registered its types, or their rules would be lost.
		add_action( 'wp_loaded', array( __CLASS__, 'flush' ) );
	}

	/**
	 * Rebuild the rewrite rules.
	 *
	 * @return void
	 */
	public static function flush() {
		flush_rewrite_rules( false );
	}

	/**
	 * Admin labels for a type.
	 *
	 * @param string $singular Singular name.
	 * @param string $plural   Plural name.
	 * @return array<string,string>
	 */
	public static function labels( $singular, $plural ) {
		return array(
			'name'               => $plural,
			'singular_name'      => $singular,
			'menu_name'          => $plural,
			/* translators: %s: item name, e.g. Destination. */
			'add_new_item'       => sprintf( __( 'Add new %s', 'qe-edu-core' ), $singular ),
			/* translators: %s: item name. */
			'edit_item'          => sprintf( __( 'Edit %s', 'qe-edu-core' ), $singular ),
			/* translators: %s: item name. */
			'new_item'           => sprintf( __( 'New %s', 'qe-edu-core' ), $singular ),
			/* translators: %s: item name. */
			'view_item'          => sprintf( __( 'View %s', 'qe-edu-core' ), $singular ),
			/* translators: %s: items name, e.g. Destinations. */
			'search_items'       => sprintf( __( 'Search %s', 'qe-edu-core' ), $plural ),
			/* translators: %s: items name. */
			'not_found'          => sprintf( __( 'No %s yet.', 'qe-edu-core' ), $plural ),
			/* translators: %s: items name. */
			'all_items'          => sprintf( __( 'All %s', 'qe-edu-core' ), $plural ),
			'featured_image'     => __( 'Image', 'qe-edu-core' ),
			'set_featured_image' => __( 'Choose image', 'qe-edu-core' ),
		);
	}

	/**
	 * Plain edit screens (no block editor) for our types.
	 *
	 * @param bool   $use_blocks Whether to use the block editor.
	 * @param string $post_type  Post type.
	 * @return bool
	 */
	public function classic_editor( $use_blocks, $post_type ) {
		return array_key_exists( $post_type, Types::all() ) ? false : $use_blocks;
	}

	/**
	 * Friendlier title placeholder.
	 *
	 * @param string   $text Placeholder.
	 * @param \WP_Post $post Post.
	 * @return string
	 */
	public function title_placeholder( $text, $post ) {
		$types = Types::all();

		if ( isset( $types[ $post->post_type ] ) ) {
			/* translators: %s: item name, e.g. Destination. */
			return sprintf( __( '%s name', 'qe-edu-core' ), $types[ $post->post_type ]['singular'] );
		}

		return $text;
	}

	/**
	 * Add the "Details" box to every type with fields.
	 *
	 * @return void
	 */
	public function meta_boxes() {
		foreach ( Types::all() as $type => $definition ) {
			add_meta_box( 'qe-edu-fields', __( 'Details', 'qe-edu-core' ), array( $this, 'render' ), $type, 'normal', 'high' );
		}
	}

	/**
	 * Render the fields box.
	 *
	 * @param \WP_Post $post Post.
	 * @return void
	 */
	public function render( $post ) {
		$types = Types::all();
		wp_nonce_field( self::NONCE, self::NONCE );

		echo '<div class="qe-fields">';
		foreach ( $types[ $post->post_type ]['fields'] as $key => $field ) {
			$id    = 'qe-field-' . $key;
			$value = Types::get( $post->ID, $key );

			echo '<div class="qe-fields__row">';
			printf( '<label for="%1$s"><strong>%2$s</strong></label><br>', esc_attr( $id ), esc_html( $field['label'] ) );

			if ( 'media' === $field['type'] ) {
				echo Fields::media( 'image', (int) $value, $id, 'qe_fields[' . $key . ']' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Fields::media().
			} elseif ( 'select' === $field['type'] ) {
				printf( '<select id="%1$s" name="qe_fields[%2$s]">', esc_attr( $id ), esc_attr( $key ) );
				foreach ( $field['options'] as $option => $label ) {
					printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $option ), selected( $value, (string) $option, false ), esc_html( $label ) );
				}
				echo '</select>';
			} else {
				$types_map = array(
					'url'  => 'url',
					'date' => 'date',
					'time' => 'time',
				);
				printf(
					'<input type="%1$s" id="%2$s" name="qe_fields[%3$s]" value="%4$s" class="widefat">',
					esc_attr( isset( $types_map[ $field['type'] ] ) ? $types_map[ $field['type'] ] : 'text' ),
					esc_attr( $id ),
					esc_attr( $key ),
					esc_attr( $value )
				);
			}

			printf( '<p class="description">%s</p></div>', esc_html( $field['help'] ) );
		}
		echo '</div>';
	}

	/**
	 * Save the fields box.
	 *
	 * @param int      $post_id Post id.
	 * @param \WP_Post $post    Post.
	 * @return void
	 */
	public function save( $post_id, $post ) {
		$types = Types::all();

		if ( ! isset( $types[ $post->post_type ], $_POST[ self::NONCE ] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE )
			|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			|| ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$input = isset( $_POST['qe_fields'] ) && is_array( $_POST['qe_fields'] ) ? wp_unslash( $_POST['qe_fields'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised per field below.

		foreach ( $types[ $post->post_type ]['fields'] as $key => $field ) {
			$value = Types::sanitize( $field, isset( $input[ $key ] ) ? $input[ $key ] : '' );

			if ( '' === $value || ( '0' === $value && 'media' === $field['type'] ) ) {
				delete_post_meta( $post_id, Types::meta_key( $key ) );
			} else {
				update_post_meta( $post_id, Types::meta_key( $key ), $value );
			}
		}
	}
}
