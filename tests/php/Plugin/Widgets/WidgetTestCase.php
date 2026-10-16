<?php
/**
 * Base for widget tests: WordPress / Elementor stubs and fake content.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Plugin\Widgets;

use Brain\Monkey\Functions;
use QeEdu\Tests\Plugin\PluginTestCase;

/**
 * Widget test case.
 */
abstract class WidgetTestCase extends PluginTestCase {

	/**
	 * Attachment id lists primed by widgets.
	 *
	 * @var array<int,int[]>
	 */
	protected $primed = array();


	/**
	 * Posts returned by get_posts().
	 *
	 * @var \WP_Post[]
	 */
	protected $posts = array();

	/**
	 * Whether the editor is active.
	 *
	 * @var bool
	 */
	protected $editing = false;

	/**
	 * Menu items returned by wp_get_nav_menu_items().
	 *
	 * @var array<int,object>
	 */
	protected $menu_items = array();

	protected function setUp(): void {
		parent::setUp();
		$this->primed = array();
		Functions\when( '_prime_post_caches' )->alias(
			function ( $ids ) {
				$this->primed[] = $ids;
			}
		);
		$this->posts      = array();
		$this->editing    = false;
		$this->menu_items = array();

		$editor = \Mockery::mock();
		$editor->shouldReceive( 'is_edit_mode' )->andReturnUsing( fn() => $this->editing );
		$document = \Mockery::mock();
		$document->shouldReceive( 'get_main_id' )->andReturn( 42 );
		$documents = \Mockery::mock();
		$documents->shouldReceive( 'get_current' )->andReturn( $document );
		\Elementor\Plugin::$instance = (object) array(
			'editor'    => $editor,
			'documents' => $documents,
		);
		\Elementor\Icons_Manager::$rendered = array();

		Functions\when( 'get_posts' )->alias( fn() => $this->posts );
		Functions\when( 'get_post_thumbnail_id' )->justReturn( 0 );
		Functions\when( 'get_the_title' )->alias(
			static function ( $post = 0 ) {
				return is_object( $post ) ? $post->post_title : 'Current page';
			}
		);
		Functions\when( 'get_the_excerpt' )->alias(
			static function ( $post = null ) {
				return is_object( $post ) ? $post->post_excerpt : '';
			}
		);
		Functions\when( 'get_permalink' )->alias(
			static function ( $post = 0 ) {
				return 'https://example.test/p/' . ( is_object( $post ) ? $post->ID : (int) $post ) . '/';
			}
		);
		Functions\when( 'get_the_date' )->justReturn( 'January 1, 2026' );
		Functions\when( 'date_i18n' )->alias(
			static function ( $format, $timestamp ) {
				return gmdate( $format, $timestamp );
			}
		);
		Functions\when( 'number_format_i18n' )->alias(
			static function ( $number ) {
				return number_format( (float) $number );
			}
		);
		Functions\when( 'tag_escape' )->returnArg();
		Functions\when( 'wpautop' )->alias(
			static function ( $text ) {
				return '<p>' . $text . '</p>';
			}
		);
		Functions\when( 'do_shortcode' )->returnArg();
		Functions\when( 'shortcode_unautop' )->returnArg();
		Functions\when( 'wp_create_nonce' )->justReturn( 'nonce123' );
		Functions\when( 'wp_get_attachment_image' )->alias(
			static function ( $id, $size, $icon, $attrs ) {
				return sprintf( '<img src="https://example.test/%1$d.jpg" class="%2$s" alt="%3$s">', $id, $attrs['class'], $attrs['alt'] );
			}
		);
		Functions\when( 'wp_get_attachment_url' )->alias(
			static function ( $id ) {
				return 'https://example.test/' . $id . '.pdf';
			}
		);
		Functions\when( 'get_the_post_thumbnail_url' )->justReturn( false );
		Functions\when( 'wp_get_nav_menus' )->justReturn( array( (object) array( 'term_id' => 5, 'name' => 'Main' ) ) );
		Functions\when( 'wp_get_nav_menu_items' )->alias( fn() => $this->menu_items );
		Functions\when( 'get_queried_object_id' )->justReturn( 0 );
		Functions\when( 'get_theme_mod' )->justReturn( 0 );
		Functions\when( 'antispambot' )->returnArg();
		Functions\when( 'wp_basename' )->alias( 'basename' );
		Functions\when( 'is_front_page' )->justReturn( false );
		Functions\when( 'is_singular' )->justReturn( false );
		Functions\when( 'is_archive' )->justReturn( true );
		Functions\when( 'is_home' )->justReturn( false );
		Functions\when( 'is_search' )->justReturn( false );
		Functions\when( 'is_404' )->justReturn( false );
		Functions\when( 'get_the_archive_title' )->justReturn( 'Archive' );
		Functions\when( 'wp_strip_all_tags' )->alias( 'strip_tags' );
		Functions\when( 'get_the_ID' )->justReturn( 7 );
		Functions\when( 'is_email' )->alias(
			static function ( $email ) {
				return (bool) filter_var( $email, FILTER_VALIDATE_EMAIL );
			}
		);
	}

	protected function tearDown(): void {
		\Elementor\Plugin::$instance = null;
		parent::tearDown();
	}

	/**
	 * Two fake posts with every field filled (101 is online, 102 is not).
	 *
	 * @return void
	 */
	protected function with_posts() {
		$this->posts = array(
			new \WP_Post(
				array(
					'ID'           => 101,
					'post_title'   => 'Post A',
					'post_excerpt' => 'Excerpt A',
				)
			),
			new \WP_Post(
				array(
					'ID'           => 102,
					'post_title'   => 'Post B',
					'post_excerpt' => 'Excerpt B',
				)
			),
		);
		foreach ( array( 101, 102 ) as $id ) {
			$this->meta[ $id ] = array(
				'_qe_country'  => 'Country ' . $id,
				'_qe_flag'     => '9',
				'_qe_link'     => '',
				'_qe_role'     => 'Role ' . $id,
				'_qe_linkedin' => 'https://linkedin.test/' . $id,
				'_qe_rating'   => '5',
				'_qe_date'     => '2030-01-0' . ( $id - 100 ),
				'_qe_time'     => 101 === $id ? '10:00' : '',
				'_qe_venue'    => 'Venue ' . $id,
				'_qe_url'      => 'https://partner.test/' . $id,
			);
		}
		$this->meta[101]['_qe_online_link'] = 'https://zoom.test/1';
	}

	/**
	 * A menu: "About" with two children (one current), and "Study".
	 *
	 * @return void
	 */
	protected function with_menu() {
		$item = static function ( $id, $parent, $title, $object_id = 0, $type = 'post_type' ) {
			return (object) array(
				'ID'               => $id,
				'menu_item_parent' => $parent,
				'title'            => $title,
				'url'              => 'https://example.test/' . $id,
				'target'           => 3 === $id ? '_blank' : '',
				'attr_title'       => 2 === $id ? 'Tip' : '',
				'description'      => 2 === $id ? 'Desc' : '',
				'object_id'        => $object_id,
				'type'             => $type,
			);
		};
		$this->menu_items = array(
			$item( 1, 0, 'About', 0, 'custom' ),
			$item( 2, 1, 'Who we are', 77 ),
			$item( 3, 1, 'Values', 78 ),
			$item( 4, 0, 'Study', 79 ),
			$item( 5, 99, 'Orphan', 80 ),
		);
	}

	/**
	 * Render a widget.
	 *
	 * @param string              $class    Widget class.
	 * @param array<string,mixed> $settings Settings overriding defaults.
	 * @return string
	 */
	protected function render( $class, $settings = array() ) {
		$widget = new $class( array( 'settings' => $settings ) );
		$widget->get_controls_for_test();
		return $widget->render_for_test();
	}
}
