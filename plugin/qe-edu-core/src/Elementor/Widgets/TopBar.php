<?php
/**
 * Top Bar: small utility links plus contact details and social icons from
 * Theme Settings.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use QeEdu\Core\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Top bar.
 */
class TopBar extends Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'qe-top-bar';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Top Bar', 'qe-edu-core' );
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-navigation-horizontal';
	}

	/**
	 * Menus for the select control.
	 *
	 * @return array<string,string>
	 */
	public static function menu_options() {
		$options = array( '' => __( '— Choose a menu —', 'qe-edu-core' ) );
		foreach ( wp_get_nav_menus() as $menu ) {
			$options[ (string) $menu->term_id ] = $menu->name;
		}

		return $options;
	}

	/**
	 * Controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section( 'section_content', array( 'label' => __( 'Links', 'qe-edu-core' ) ) );
		$this->add_select(
			'source',
			__( 'Links come from', 'qe-edu-core' ),
			array(
				'links' => __( 'Typed in below', 'qe-edu-core' ),
				'menu'  => __( 'A menu (Appearance → Menus)', 'qe-edu-core' ),
			),
			'links'
		);
		$this->add_select( 'menu', __( 'Menu', 'qe-edu-core' ), self::menu_options(), '', array( 'condition' => array( 'source' => 'menu' ) ) );

		$links = new Repeater();
		$links->add_control(
			'text',
			array(
				'label'   => __( 'Text', 'qe-edu-core' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			)
		);
		$links->add_control(
			'link',
			array(
				'label'   => __( 'Link', 'qe-edu-core' ),
				'type'    => Controls_Manager::URL,
				'default' => array( 'url' => '#' ),
			)
		);
		$defaults = array();
		foreach ( array( __( 'News', 'qe-edu-core' ), __( 'Events', 'qe-edu-core' ), __( 'Careers', 'qe-edu-core' ), __( 'Study Mobility Program', 'qe-edu-core' ), __( 'Contact Us', 'qe-edu-core' ) ) as $text ) {
			$defaults[] = array(
				'text' => $text,
				'link' => array( 'url' => '#' ),
			);
		}
		$this->add_control(
			'links',
			array(
				'label'       => __( 'Links', 'qe-edu-core' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $links->get_controls(),
				'default'     => $defaults,
				'title_field' => '{{{ text }}}',
				'condition'   => array( 'source' => 'links' ),
			)
		);
		$this->add_switch( 'show_contact', __( 'Show phone and email (from Theme Settings)', 'qe-edu-core' ) );
		$this->add_text( 'nav_label', __( 'Label for screen readers', 'qe-edu-core' ), __( 'Utility links', 'qe-edu-core' ) );
		$this->end_controls_section();

		$this->start_style_section();
		$this->add_text_style( 'links', __( 'Links', 'qe-edu-core' ), '.qe-topbar-w a', self::COLOR_TEXT, self::FONT_TEXT );
		$this->end_controls_section();
		$this->add_box_style( '.qe-topbar-w', array( 'label' => __( 'Bar', 'qe-edu-core' ) ) );
	}

	/**
	 * Links to print: [text, url, target].
	 *
	 * @param array<string,mixed> $s Settings.
	 * @return array<int,array<string,string>>
	 */
	public static function items( $s ) {
		$items = array();

		if ( 'menu' === $s['source'] ) {
			$menu = $s['menu'] ? wp_get_nav_menu_items( (int) $s['menu'] ) : array();
			foreach ( (array) $menu as $item ) {
				if ( 0 === (int) $item->menu_item_parent ) {
					$items[] = array(
						'text'   => $item->title,
						'url'    => $item->url,
						'target' => (string) $item->target,
					);
				}
			}
			return $items;
		}

		foreach ( self::items_with(
			$s['links'],
			array(
				'text' => '',
				'link' => array(),
			)
		) as $link ) {
			$items[] = array(
				'text'   => (string) $link['text'],
				'url'    => isset( $link['link']['url'] ) ? (string) $link['link']['url'] : '',
				'target' => ! empty( $link['link']['is_external'] ) ? '_blank' : '',
			);
		}

		return $items;
	}

	/**
	 * Render.
	 *
	 * @return void
	 */
	protected function render() {
		$s = $this->get_settings_for_display();

		echo '<div class="qe-topbar-w">';
		if ( 'yes' === $s['show_contact'] ) {
			$phone = (string) Settings::get( 'contact.phone' );
			$email = (string) Settings::get( 'contact.email' );
			echo '<p class="qe-topbar-w__contact">';
			if ( '' !== $phone ) {
				printf( '<a href="tel:%1$s">%2$s</a>', esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ), esc_html( $phone ) );
			}
			if ( '' !== $email ) {
				printf( '<a href="mailto:%1$s">%2$s</a>', esc_attr( antispambot( $email ) ), esc_html( antispambot( $email ) ) );
			}
			echo '</p>';
		}

		printf( '<nav class="qe-topbar-w__nav" aria-label="%s"><ul>', esc_attr( $s['nav_label'] ) );
		foreach ( self::items( $s ) as $item ) {
			printf(
				'<li><a href="%1$s"%2$s>%3$s</a></li>',
				esc_url( $item['url'] ),
				'_blank' === $item['target'] ? ' target="_blank" rel="noopener"' : '',
				esc_html( $item['text'] )
			);
		}
		echo '</ul></nav></div>';
	}
}
