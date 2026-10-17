<?php
/**
 * One-click demo import. Adds what is missing and never overwrites: every
 * imported item carries a `_qe_demo` key, so running the import again finds
 * it and skips it instead of creating a duplicate.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\DemoImport;

use QeEdu\Core\PostTypes\Types;
use QeEdu\Core\Seo\Fields as SeoFields;
use QeEdu\Core\Settings\AdminPage;
use QeEdu\Core\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Importer.
 */
class Importer {

	/**
	 * Meta / term-meta key holding the demo key.
	 */
	const META = '_qe_demo';

	/**
	 * Page template used by demo pages.
	 */
	const PAGE_TEMPLATE = 'templates/elementor-canvas-full.php';

	/**
	 * Package.
	 *
	 * @var Package
	 */
	private $package;

	/**
	 * Resolved ids: [kind][key] => id.
	 *
	 * @var array<string,array<string,int>>
	 */
	private $ids = array(
		'image' => array(),
		'page'  => array(),
		'menu'  => array(),
	);

	/**
	 * What happened: created / skipped counts.
	 *
	 * @var array<string,int>
	 */
	private $report = array(
		'created' => 0,
		'skipped' => 0,
	);

	/**
	 * Constructor.
	 *
	 * @param Package|null $package Package (defaults to the bundled demo).
	 */
	public function __construct( $package = null ) {
		$this->package = $package ? $package : new Package();
	}

	/**
	 * Import the whole site or one page.
	 *
	 * @param string $scope `all` or a page key.
	 * @return array{created:int,skipped:int,page:int} Report (`page` = id of the single page imported).
	 */
	public function run( $scope = 'all' ) {
		$pages  = $this->package->pages();
		$single = 'all' !== $scope;

		if ( $single && ! isset( $pages[ $scope ] ) ) {
			return array_merge( $this->report, array( 'page' => 0 ) );
		}

		$this->prepare();

		// Create the pages first, so every link between them resolves to a real page.
		$created = array();
		foreach ( $single ? array( $pages[ $scope ] ) : $pages as $page ) {
			if ( $this->create_page( $page ) ) {
				$created[] = $page;
			}
		}

		if ( ! $single ) {
			$this->import_posts();
			$this->import_menus();
		}

		foreach ( $created as $page ) {
			$this->fill_page( $page );
		}

		if ( ! $single ) {
			foreach ( $this->package->get( 'templates' ) as $template ) {
				$this->import_template( $template );
			}
			$this->apply_settings();
			// Logo → Site Identity, and the logo (or icon) → favicon.
			AdminPage::sync_brand( Settings::all() );
			$this->apply_reading();
		}

		$this->finish();

		return array_merge( $this->report, array( 'page' => $single ? $this->id( 'page', $scope ) : 0 ) );
	}

	/**
	 * Give PHP enough time and memory for image processing.
	 *
	 * @return void
	 */
	protected function prepare() {
		wp_raise_memory_limit( 'admin' );
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 300 ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- long one-off import.
		}
	}

	/**
	 * Clear Elementor's generated CSS so the new pages render with fresh styles.
	 *
	 * @return void
	 */
	protected function finish() {
		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}
	}

	/**
	 * Find an item imported earlier.
	 *
	 * @param string          $key       Demo key (`page:home`).
	 * @param string|string[] $post_type Post type(s).
	 * @return int
	 */
	public static function find( $key, $post_type ) {
		$found = get_posts(
			array(
				'post_type'        => $post_type,
				'post_status'      => array( 'publish', 'draft', 'pending', 'private', 'future', 'inherit' ),
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'meta_key'         => self::META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'       => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'suppress_filters' => true,
			)
		);

		return $found ? (int) $found[0] : 0;
	}

	/**
	 * Resolved id for a key, importing images on demand.
	 *
	 * @param string $kind image|page|menu.
	 * @param string $key  Key.
	 * @return int
	 */
	private function id( $kind, $key ) {
		if ( ! isset( $this->ids[ $kind ][ $key ] ) ) {
			if ( 'image' === $kind ) {
				$this->ids[ $kind ][ $key ] = $this->import_image( $key );
			} else {
				$this->ids[ $kind ][ $key ] = 'menu' === $kind ? self::find_menu( $key ) : self::find( 'page:' . $key, 'page' );
			}
		}

		return $this->ids[ $kind ][ $key ];
	}

	/**
	 * Value for a placeholder.
	 *
	 * @param string $kind     Kind.
	 * @param string $argument Argument.
	 * @return string
	 */
	public function lookup( $kind, $argument ) {
		switch ( $kind ) {
			case 'image':
			case 'page':
			case 'menu':
				$id = $this->id( $kind, $argument );
				return $id ? (string) $id : '';
			case 'image-url':
				$id = $this->id( 'image', $argument );
				return $id ? (string) wp_get_attachment_url( $id ) : '';
			case 'page-url':
				$id    = $this->id( 'page', $argument );
				$pages = $this->package->pages();
				$slug  = isset( $pages[ $argument ]['slug'] ) ? $pages[ $argument ]['slug'] : $argument;
				return $id ? (string) get_permalink( $id ) : home_url( '/' . $slug . '/' );
			case 'home-url':
				return home_url( '/' );
			case 'date':
				return gmdate( 'Y-m-d', (int) strtotime( $argument, (int) current_time( 'timestamp' ) ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- local dates for demo events.
			default:
				return '';
		}
	}

	/**
	 * Resolve placeholders in data.
	 *
	 * @param mixed $data Data.
	 * @return mixed
	 */
	private function resolve( $data ) {
		return ( new Resolver( array( $this, 'lookup' ) ) )->resolve( $data );
	}

	/**
	 * Copy one image from the package into the media library.
	 *
	 * @param string $key Image key.
	 * @return int Attachment id (0 when the file is missing or the upload failed).
	 */
	protected function import_image( $key ) {
		$existing = self::find( 'image:' . $key, 'attachment' );
		if ( $existing ) {
			return $existing;
		}

		$path = $this->package->image_path( $key );
		if ( '' === $path ) {
			return 0;
		}

		$upload = wp_upload_bits( basename( $path ), null, (string) file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local package file.
		if ( ! empty( $upload['error'] ) ) {
			return 0;
		}

		$images = $this->package->get( 'images' );
		$alt    = isset( $images[ $key ]['alt'] ) ? (string) $images[ $key ]['alt'] : '';
		$type   = wp_check_filetype( $upload['file'] );
		$id     = wp_insert_attachment(
			array(
				'post_mime_type' => (string) $type['type'],
				'post_title'     => $alt,
				'post_name'      => 'qe-demo-' . $key, // Keeps page slugs such as "about" free.

				'post_status'    => 'inherit',
			),
			$upload['file']
		);

		if ( ! $id || is_wp_error( $id ) ) {
			return 0;
		}

		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
		update_post_meta( $id, '_wp_attachment_image_alt', $alt );
		update_post_meta( $id, self::META, 'image:' . $key );
		++$this->report['created'];

		return (int) $id;
	}

	/**
	 * Sample destinations, services, partners, events, team, testimonials and news.
	 *
	 * @return void
	 */
	private function import_posts() {
		foreach ( $this->package->get( 'posts' ) as $post ) {
			$key = 'post:' . $post['key'];
			if ( self::find( $key, $post['type'] ) ) {
				++$this->report['skipped'];
				continue;
			}

			$post = $this->resolve( $post );
			$args = array(
				'post_type'    => $post['type'],
				'post_status'  => 'publish',
				'post_author'  => $this->author(),
				'post_title'   => $post['title'],
				'post_excerpt' => isset( $post['excerpt'] ) ? $post['excerpt'] : '',
				'post_content' => isset( $post['content'] ) ? $post['content'] : '',
				'menu_order'   => isset( $post['order'] ) ? (int) $post['order'] : 0,
			);
			if ( ! empty( $post['date'] ) ) {
				$args['post_date'] = $post['date'] . ' 09:00:00';
			}
			if ( ! empty( $post['category'] ) ) {
				$term = term_exists( $post['category'], 'category' );
				$term = $term ? $term : wp_insert_term( $post['category'], 'category' );
				if ( is_array( $term ) ) {
					$args['post_category'] = array( (int) $term['term_id'] );
				}
			}

			$id = wp_insert_post( wp_slash( $args ), true );
			if ( is_wp_error( $id ) || ! $id ) {
				continue;
			}

			update_post_meta( $id, self::META, $key );
			foreach ( isset( $post['meta'] ) ? $post['meta'] : array() as $field => $value ) {
				update_post_meta( $id, Types::meta_key( $field ), $value );
			}
			if ( ! empty( $post['image'] ) && $this->id( 'image', $post['image'] ) ) {
				set_post_thumbnail( $id, $this->id( 'image', $post['image'] ) );
			}
			++$this->report['created'];
		}
	}

	/**
	 * Author of imported posts: the person importing, or (from WP-CLI) the
	 * first administrator.
	 *
	 * @return int
	 */
	private function author() {
		$user = get_current_user_id();
		if ( $user ) {
			return $user;
		}

		$admins = get_users(
			array(
				'role'    => 'administrator',
				'number'  => 1,
				'fields'  => 'ID',
				'orderby' => 'ID',
			)
		);

		return $admins ? (int) $admins[0] : 0;
	}

	/**
	 * Store Elementor data on a post.
	 *
	 * @param int          $post_id Post id.
	 * @param array<mixed> $data    Elements.
	 * @param string       $type    Elementor document type.
	 * @return void
	 */
	private function save_elementor( $post_id, $data, $type ) {
		update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $post_id, '_elementor_template_type', $type );
		update_post_meta( $post_id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '' );
		update_post_meta( $post_id, '_elementor_data', wp_slash( (string) wp_json_encode( $this->resolve( $data ) ) ) );
	}

	/**
	 * Create one page (without content yet).
	 *
	 * @param array<string,mixed> $page Manifest entry.
	 * @return bool Whether a new page was created.
	 */
	private function create_page( $page ) {
		if ( $this->id( 'page', $page['key'] ) ) {
			++$this->report['skipped'];
			return false;
		}

		$args     = array(
			'post_type'   => 'page',
			'post_status' => 'publish',
			'post_author' => $this->author(),
			'post_title'  => $page['title'],
			'post_name'   => $page['slug'],
		);
		$existing = get_page_by_path( $page['slug'] );

		if ( $existing && 'publish' === $existing->post_status ) {
			// The site already has this page: link to it, never change it.
			$this->ids['page'][ $page['key'] ] = (int) $existing->ID;
			++$this->report['skipped'];
			return false;
		}

		if ( $existing && self::is_untouched_placeholder( $existing ) ) {
			// WordPress's own unedited "Privacy Policy" draft: use it instead of a "-2" copy.
			$args['ID'] = (int) $existing->ID;
			$id         = wp_update_post( wp_slash( $args ), true );
		} else {
			$id = wp_insert_post( wp_slash( $args ), true );
		}
		if ( is_wp_error( $id ) || ! $id ) {
			return false;
		}

		$this->ids['page'][ $page['key'] ] = (int) $id;
		update_post_meta( $id, self::META, 'page:' . $page['key'] );
		++$this->report['created'];

		return true;
	}

	/**
	 * Add the Elementor layout and SEO values to a page created by this run.
	 *
	 * @param array<string,mixed> $page Manifest entry.
	 * @return void
	 */
	private function fill_page( $page ) {
		$id = $this->id( 'page', $page['key'] );

		if ( ! empty( $page['file'] ) ) {
			update_post_meta( $id, '_wp_page_template', self::PAGE_TEMPLATE );
			$this->save_elementor( $id, $this->package->json( $page['file'] ), 'wp-page' );
		}

		foreach ( isset( $page['seo'] ) ? $page['seo'] : array() as $field => $value ) {
			SeoFields::save( $id, $field, $this->resolve( $value ) );
		}
	}

	/**
	 * Whether a page is WordPress's default privacy policy draft, never edited.
	 *
	 * @param \WP_Post $post Page.
	 * @return bool
	 */
	public static function is_untouched_placeholder( $post ) {
		return 'draft' === $post->post_status
			&& (int) get_option( 'wp_page_for_privacy_policy' ) === (int) $post->ID
			&& $post->post_modified_gmt === $post->post_date_gmt;
	}

	/**
	 * One Elementor template (header, footer, 404, single, archive), assigned
	 * in Theme Settings when that slot is still empty.
	 *
	 * @param array<string,mixed> $template Manifest entry.
	 * @return void
	 */
	private function import_template( $template ) {
		$key = 'template:' . $template['key'];
		$id  = self::find( $key, 'elementor_library' );

		if ( $id ) {
			++$this->report['skipped'];
		} else {
			$id = wp_insert_post(
				wp_slash(
					array(
						'post_type'   => 'elementor_library',
						'post_status' => 'publish',
						'post_title'  => $template['title'],
					)
				),
				true
			);
			if ( is_wp_error( $id ) || ! $id ) {
				return;
			}
			update_post_meta( $id, self::META, $key );
			wp_set_object_terms( $id, 'section', 'elementor_library_type' );
			$this->save_elementor( $id, $this->package->json( $template['file'] ), 'section' );
			++$this->report['created'];
		}

		if ( ! empty( $template['setting'] ) && ! absint( Settings::get( $template['setting'] ) ) ) {
			$this->set_setting( $template['setting'], (int) $id );
		}
	}

	/**
	 * A menu created by the demo.
	 *
	 * @param string $key Menu key.
	 * @return int Menu term id.
	 */
	public static function find_menu( $key ) {
		$terms = get_terms(
			array(
				'taxonomy'   => 'nav_menu',
				'hide_empty' => false,
				'fields'     => 'ids',
				'meta_key'   => self::META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => 'menu:' . $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		return is_array( $terms ) && $terms ? (int) $terms[0] : 0;
	}

	/**
	 * Menus, assigned to empty theme locations.
	 *
	 * @return void
	 */
	private function import_menus() {
		$locations = get_nav_menu_locations();

		foreach ( $this->package->get( 'menus' ) as $key => $menu ) {
			$id = $this->id( 'menu', $key );

			if ( $id ) {
				++$this->report['skipped'];
			} else {
				$name = $menu['name'];
				$id   = wp_create_nav_menu( wp_get_nav_menu_object( $name ) ? $name . ' (demo)' : $name );
				if ( is_wp_error( $id ) || ! $id ) {
					continue;
				}
				update_term_meta( $id, self::META, 'menu:' . $key );
				$this->add_items( (int) $id, $menu['items'], 0 );
				$this->ids['menu'][ $key ] = (int) $id;
				++$this->report['created'];
			}

			if ( ! empty( $menu['location'] ) && empty( $locations[ $menu['location'] ] ) ) {
				$locations[ $menu['location'] ] = (int) $id;
			}
		}

		set_theme_mod( 'nav_menu_locations', $locations );
	}

	/**
	 * Add menu items: [title, page key, children?].
	 *
	 * @param int          $menu_id Menu id.
	 * @param array<mixed> $items   Items.
	 * @param int          $parent_id Parent item id.
	 * @return void
	 */
	private function add_items( $menu_id, $items, $parent_id ) {
		foreach ( $items as $item ) {
			$page = $this->id( 'page', $item[1] );
			$args = array(
				'menu-item-title'     => $item[0],
				'menu-item-status'    => 'publish',
				'menu-item-parent-id' => $parent_id,
			);
			if ( $page ) {
				$args['menu-item-type']      = 'post_type';
				$args['menu-item-object']    = 'page';
				$args['menu-item-object-id'] = $page;
			} else {
				$args['menu-item-type'] = 'custom';
				$args['menu-item-url']  = $this->lookup( 'page-url', $item[1] );
			}

			$item_id = wp_update_nav_menu_item( $menu_id, 0, $args );
			if ( ! empty( $item[2] ) && ! is_wp_error( $item_id ) ) {
				$this->add_items( $menu_id, $item[2], (int) $item_id );
			}
		}
	}

	/**
	 * Write one Theme Settings value.
	 *
	 * @param string $path  Dotted path.
	 * @param mixed  $value Value.
	 * @return void
	 */
	private function set_setting( $path, $value ) {
		$stored = Settings::stored();
		$cursor = &$stored;
		foreach ( explode( '.', $path ) as $part ) {
			if ( ! isset( $cursor[ $part ] ) || ! is_array( $cursor[ $part ] ) ) {
				$cursor[ $part ] = array();
			}
			$cursor = &$cursor[ $part ];
		}
		$cursor = $value;
		unset( $cursor );

		update_option( Settings::OPTION, $stored );
	}

	/**
	 * Demo Theme Settings (logo, contact details, …) — only where still empty.
	 *
	 * @return void
	 */
	private function apply_settings() {
		foreach ( $this->package->get( 'settings' ) as $path => $value ) {
			$current = Settings::get( $path );
			if ( '' !== (string) $current && '0' !== (string) $current ) {
				continue;
			}
			$value = $this->resolve( $value );
			$this->set_setting( $path, is_numeric( $value ) ? (int) $value : $value );
		}
	}

	/**
	 * Home page and news page (only when WordPress still shows latest posts).
	 *
	 * @return void
	 */
	private function apply_reading() {
		if ( 'page' === get_option( 'show_on_front' ) && get_option( 'page_on_front' ) ) {
			return;
		}

		$front = $this->id( 'page', $this->package->value( 'front_page' ) );
		$posts = $this->id( 'page', $this->package->value( 'posts_page' ) );
		if ( ! $front ) {
			return;
		}

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front );
		if ( $posts ) {
			update_option( 'page_for_posts', $posts );
		}
	}
}
