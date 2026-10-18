<?php
/**
 * "SEO" box under the editor of every public post type, plus the same main
 * fields inside Elementor's page settings panel.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Seo;

use QeEdu\Core\Admin\Fields as AdminFields;

defined( 'ABSPATH' ) || exit;

/**
 * Metabox.
 */
final class Metabox {

	/**
	 * Nonce name / action.
	 */
	const NONCE = 'qe_edu_seo';

	/**
	 * Fields shown in Elementor's page settings.
	 */
	const ELEMENTOR_FIELDS = array( 'title', 'description', 'noindex' );

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'add_meta_boxes', array( $this, 'add' ) );
		add_action( 'save_post', array( $this, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'elementor/documents/register_controls', array( $this, 'elementor_controls' ) );
		add_action( 'elementor/document/after_save', array( $this, 'elementor_save' ), 10, 2 );
	}

	/**
	 * Add the box.
	 *
	 * @return void
	 */
	public function add() {
		add_meta_box( 'qe-edu-seo', __( 'SEO', 'qe-edu-core' ), array( $this, 'render' ), Fields::post_types(), 'normal', 'default' );
	}

	/**
	 * Admin scripts on post edit screens.
	 *
	 * @param string $hook_suffix Admin page.
	 * @return void
	 */
	public function assets( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'qe-edu-admin', QE_EDU_CORE_URL . 'assets/css/admin.css', array(), QE_EDU_CORE_VERSION );
		wp_enqueue_script( 'qe-edu-admin', QE_EDU_CORE_URL . 'assets/js/admin.min.js', array(), QE_EDU_CORE_VERSION, true );
		wp_localize_script( 'qe-edu-admin', 'qeEduAdmin', \QeEdu\Core\Settings\AdminPage::script_data() );
		wp_enqueue_script( 'qe-edu-seo', QE_EDU_CORE_URL . 'assets/js/seo.min.js', array(), QE_EDU_CORE_VERSION, true );
		wp_localize_script(
			'qe-edu-seo',
			'qeEduSeo',
			array(
				'site'    => get_bloginfo( 'name' ),
				'tagline' => get_bloginfo( 'description' ),
				'sep'     => (string) \QeEdu\Core\Settings\Settings::get( 'seo.separator' ),
				'i18n'    => array(
					/* translators: 1: number of characters, 2: recommended range. */
					'length'         => __( '%1$s characters (aim for %2$s)', 'qe-edu-core' ),
					'keywordTitle'   => __( 'The focus keyword is in the SEO title.', 'qe-edu-core' ),
					'keywordDesc'    => __( 'The focus keyword is in the meta description.', 'qe-edu-core' ),
					'keywordUrl'     => __( 'The focus keyword is in the page address.', 'qe-edu-core' ),
					'keywordHeading' => __( 'The focus keyword is in the page title.', 'qe-edu-core' ),
					'missing'        => __( 'Not yet', 'qe-edu-core' ),
					'ok'             => __( 'Yes', 'qe-edu-core' ),
				),
			)
		);
	}

	/**
	 * Render the box.
	 *
	 * @param \WP_Post $post Post.
	 * @return void
	 */
	public function render( $post ) {
		wp_nonce_field( self::NONCE, self::NONCE );

		$fields = Fields::all();
		$value  = static function ( $key ) use ( $post ) {
			return Fields::get( $post->ID, $key );
		};

		echo '<div class="qe-seo" data-qe-seo>';
		printf(
			'<div class="qe-seo__preview" aria-hidden="true"><span class="qe-seo__preview-url" data-qe-seo-url>%1$s</span><span class="qe-seo__preview-title" data-qe-seo-preview-title>%2$s</span><span class="qe-seo__preview-desc" data-qe-seo-preview-desc>%3$s</span></div>',
			esc_html( (string) get_permalink( $post ) ),
			esc_html( '' !== $value( 'title' ) ? $value( 'title' ) : $post->post_title ),
			esc_html( '' !== $value( 'description' ) ? $value( 'description' ) : Head::excerpt( $post->post_excerpt ? $post->post_excerpt : $post->post_content ) )
		);

		$main     = array( 'title', 'description', 'keyword', 'schema' );
		$advanced = array( 'canonical', 'noindex', 'nofollow', 'og_title', 'og_description', 'og_image' );

		foreach ( $main as $key ) {
			$this->field( $key, $fields[ $key ], $value( $key ) );
		}
		echo '<ul class="qe-seo__checks" data-qe-seo-checks></ul>';

		printf( '<details class="qe-seo__advanced"><summary>%s</summary>', esc_html__( 'Social sharing & advanced', 'qe-edu-core' ) );
		foreach ( $advanced as $key ) {
			$this->field( $key, $fields[ $key ], $value( $key ) );
		}
		echo '</details></div>';
	}

	/**
	 * Render one field.
	 *
	 * @param string              $key   Key.
	 * @param array<string,mixed> $field Definition.
	 * @param string              $value Value.
	 * @return void
	 */
	private function field( $key, $field, $value ) {
		$id   = 'qe-seo-' . $key;
		$name = 'qe_seo[' . $key . ']';

		echo '<div class="qe-seo__field">';

		switch ( $field['type'] ) {
			case 'checkbox':
				printf( '<label for="%1$s"><input type="checkbox" id="%1$s" name="%2$s" value="1"%3$s> %4$s</label>', esc_attr( $id ), esc_attr( $name ), checked( $value, '1', false ), esc_html( $field['label'] ) );
				break;
			case 'textarea':
				printf( '<label for="%1$s"><strong>%2$s</strong></label><textarea id="%1$s" name="%3$s" rows="3" class="widefat" data-qe-seo-input="%4$s">%5$s</textarea>', esc_attr( $id ), esc_html( $field['label'] ), esc_attr( $name ), esc_attr( $key ), esc_textarea( $value ) );
				break;
			case 'select':
				printf( '<label for="%1$s"><strong>%2$s</strong></label><select id="%1$s" name="%3$s">', esc_attr( $id ), esc_html( $field['label'] ), esc_attr( $name ) );
				foreach ( $field['options'] as $option => $label ) {
					printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $option ), selected( '' !== $value ? $value : 'WebPage', $option, false ), esc_html( $label ) );
				}
				echo '</select>';
				break;
			case 'media':
				printf( '<p><strong>%s</strong></p>', esc_html( $field['label'] ) );
				echo AdminFields::media( 'image', (int) $value, $id, $name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Fields::media().
				break;
			default:
				printf(
					'<label for="%1$s"><strong>%2$s</strong></label><input type="%3$s" id="%1$s" name="%4$s" value="%5$s" class="widefat" data-qe-seo-input="%6$s">',
					esc_attr( $id ),
					esc_html( $field['label'] ),
					'url' === $field['type'] ? 'url' : 'text',
					esc_attr( $name ),
					esc_attr( $value ),
					esc_attr( $key )
				);
		}

		if ( in_array( $key, array( 'title', 'description' ), true ) ) {
			printf( '<p class="qe-seo__meter" data-qe-seo-meter="%s"></p>', esc_attr( $key ) );
		}
		printf( '<p class="description">%s</p></div>', esc_html( $field['help'] ) );
	}

	/**
	 * Save the box.
	 *
	 * @param int      $post_id Post id.
	 * @param \WP_Post $post    Post.
	 * @return void
	 */
	public function save( $post_id, $post ) {
		if ( ! in_array( $post->post_type, Fields::post_types(), true )
			|| ! isset( $_POST[ self::NONCE ] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE )
			|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			|| ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$input = isset( $_POST['qe_seo'] ) && is_array( $_POST['qe_seo'] ) ? wp_unslash( $_POST['qe_seo'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised per field.

		foreach ( array_keys( Fields::all() ) as $key ) {
			Fields::save( $post_id, $key, isset( $input[ $key ] ) ? $input[ $key ] : '' );
		}
	}

	/**
	 * "SEO" section in Elementor's page settings (Settings tab).
	 *
	 * @param object $document Elementor document.
	 * @return void
	 */
	public function elementor_controls( $document ) {
		$post_id = (int) $document->get_main_id();
		if ( ! in_array( get_post_type( $post_id ), Fields::post_types(), true ) ) {
			return;
		}

		$fields = Fields::all();

		$document->start_controls_section(
			'qe_seo_section',
			array(
				'label' => __( 'SEO', 'qe-edu-core' ),
				'tab'   => 'settings',
			)
		);
		foreach ( self::ELEMENTOR_FIELDS as $key ) {
			$document->add_control(
				'qe_seo_' . $key,
				array(
					'label'        => $fields[ $key ]['label'],
					'description'  => $fields[ $key ]['help'],
					'type'         => array(
						'text'     => 'text',
						'textarea' => 'textarea',
						'checkbox' => 'switcher',
					)[ $fields[ $key ]['type'] ],
					'default'      => Fields::get( $post_id, $key ),
					'return_value' => '1',
					'label_block'  => true,
				)
			);
		}
		$document->end_controls_section();
	}

	/**
	 * Copy the Elementor SEO values to the post meta after saving.
	 *
	 * @param object              $document Elementor document.
	 * @param array<string,mixed> $data     Saved data.
	 * @return void
	 */
	public function elementor_save( $document, $data ) {
		$post_id = (int) $document->get_main_id();

		if ( ! isset( $data['settings'] ) || ! is_array( $data['settings'] ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		foreach ( self::ELEMENTOR_FIELDS as $key ) {
			if ( array_key_exists( 'qe_seo_' . $key, $data['settings'] ) ) {
				Fields::save( $post_id, $key, $data['settings'][ 'qe_seo_' . $key ] );
			}
		}
	}
}
