<?php
/**
 * Mega Menu: a WordPress menu with optional multi-column panels (columns,
 * descriptions and an image per top-level item are set in Appearance →
 * Menus) and an accessible mobile menu with accordion submenus.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Elementor\Widgets;

use Elementor\Controls_Manager;
use QeEdu\Core\Elementor\MenuFields;

defined( 'ABSPATH' ) || exit;

/**
 * Mega menu.
 */
class MegaMenu extends Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'qe-mega-menu';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Mega Menu', 'qe-edu' );
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-nav-menu';
	}

	/**
	 * Script.
	 *
	 * @return string[]
	 */
	public function get_script_depends() {
		return $this->behaviour_script_depends();
	}

	/**
	 * Controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section( 'section_content', array( 'label' => __( 'Menu', 'qe-edu' ) ) );
		$this->add_select(
			'menu',
			__( 'Menu', 'qe-edu' ),
			TopBar::menu_options(),
			'',
			array( 'description' => __( 'Create and edit menus in Appearance → Menus. Tick "Mega panel" on a top-level item for a multi-column panel.', 'qe-edu' ) )
		);
		$this->add_select(
			'breakpoint',
			__( 'Switch to the mobile menu below', 'qe-edu' ),
			array(
				'1024' => __( 'Tablet width (1024px)', 'qe-edu' ),
				'767'  => __( 'Phone width (767px)', 'qe-edu' ),
			),
			'1024'
		);
		$this->add_text( 'toggle_text', __( 'Mobile menu button text', 'qe-edu' ), __( 'Menu', 'qe-edu' ) );
		$this->add_text( 'nav_label', __( 'Label for screen readers', 'qe-edu' ), __( 'Main menu', 'qe-edu' ) );
		$this->add_text( 'submenu_label', __( 'Submenu button label for screen readers', 'qe-edu' ), __( 'Show submenu', 'qe-edu' ) );
		$this->end_controls_section();

		$this->start_style_section();
		$this->add_text_style( 'link', __( 'Menu links', 'qe-edu' ), '.qe-mega__link', self::COLOR_SECONDARY, self::FONT_SECONDARY );
		$this->add_control(
			'active_color',
			array(
				'label'     => __( 'Current page and hover colour', 'qe-edu' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => array( 'default' => self::COLOR_PRIMARY ),
				'selectors' => array( '{{WRAPPER}} .qe-mega__link:hover, {{WRAPPER}} .qe-mega__link:focus-visible, {{WRAPPER}} .is-current > .qe-mega__link' => 'color: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();
	}

	/**
	 * Build a tree from flat menu items.
	 *
	 * @param array<int,object> $items Menu items.
	 * @return array<int,array<string,mixed>> Nodes: [item, children].
	 */
	public static function tree( $items ) {
		$nodes = array();
		foreach ( $items as $item ) {
			$nodes[ (int) $item->ID ] = array(
				'item'     => $item,
				'children' => array(),
			);
		}

		$tree = array();
		foreach ( $items as $item ) {
			$parent = (int) $item->menu_item_parent;
			if ( $parent && isset( $nodes[ $parent ] ) ) {
				$nodes[ $parent ]['children'][] = &$nodes[ (int) $item->ID ];
			} else {
				$tree[] = &$nodes[ (int) $item->ID ];
			}
		}

		return $tree;
	}

	/**
	 * Whether a menu item (or one of its children) is the current page.
	 *
	 * @param array<string,mixed> $node Node.
	 * @return bool
	 */
	public static function is_current( $node ) {
		$item = $node['item'];
		if ( 'custom' !== $item->type && (int) get_queried_object_id() === (int) $item->object_id ) {
			return true;
		}

		foreach ( $node['children'] as $child ) {
			if ( self::is_current( $child ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Print one link.
	 *
	 * @param object $item  Menu item.
	 * @param bool   $desc  Include the description.
	 * @return void
	 */
	private function print_link( $item, $desc ) {
		printf(
			'<a class="qe-mega__link" href="%1$s"%2$s%3$s><span class="qe-mega__title">%4$s</span>%5$s</a>',
			esc_url( $item->url ),
			'_blank' === $item->target ? ' target="_blank" rel="noopener"' : '',
			'' !== (string) $item->attr_title ? ' title="' . esc_attr( $item->attr_title ) . '"' : '',
			esc_html( $item->title ),
			$desc && '' !== trim( (string) $item->description ) ? '<span class="qe-mega__desc">' . esc_html( $item->description ) . '</span>' : ''
		);
	}

	/**
	 * Print a list of nodes.
	 *
	 * @param array<int,array<string,mixed>> $nodes    Nodes.
	 * @param int                            $depth    Depth.
	 * @param array<string,mixed>            $settings Settings.
	 * @return void
	 */
	private function print_nodes( $nodes, $depth, $settings ) {
		foreach ( $nodes as $node ) {
			$item    = $node['item'];
			$columns = 0 === $depth ? MenuFields::columns( (int) $item->ID ) : 0;
			$classes = array( 'qe-mega__item' );
			if ( $node['children'] ) {
				$classes[] = 'has-children';
			}
			if ( $columns ) {
				$classes[] = 'is-mega';
			}
			if ( self::is_current( $node ) ) {
				$classes[] = 'is-current';
			}

			printf( '<li class="%s">', esc_attr( implode( ' ', $classes ) ) );
			$this->print_link( $item, $depth > 0 );

			if ( $node['children'] ) {
				printf(
					'<button type="button" class="qe-mega__sub-toggle" aria-expanded="false"><span class="screen-reader-text">%1$s: %2$s</span></button>',
					esc_html( $settings['submenu_label'] ),
					esc_html( $item->title )
				);
				printf( '<div class="qe-mega__panel"%s>', $columns ? ' style="--qe-mega-cols:' . (int) $columns . '"' : '' );
				echo '<ul class="qe-mega__list">';
				$this->print_nodes( $node['children'], $depth + 1, $settings );
				echo '</ul>';
				if ( $columns ) {
					$this->print_image( MenuFields::image( (int) $item->ID ), 'medium_large', 'qe-mega__img', $item->title );
				}
				echo '</div>';
			}
			echo '</li>';
		}
	}

	/**
	 * Render.
	 *
	 * @return void
	 */
	protected function render() {
		$s     = $this->get_settings_for_display();
		$items = $s['menu'] ? wp_get_nav_menu_items( (int) $s['menu'] ) : array();

		if ( ! $items ) {
			$this->print_editor_notice( __( 'Choose a menu in the widget settings (create one in Appearance → Menus).', 'qe-edu' ) );
			return;
		}

		$id = 'qe-mega-' . $this->get_id();
		printf(
			'<nav class="qe-mega" aria-label="%1$s" data-qe-mega data-breakpoint="%2$d" style="--qe-mega-bp:%2$dpx">',
			esc_attr( $s['nav_label'] ),
			'767' === (string) $s['breakpoint'] ? 767 : 1024
		);
		printf(
			'<button type="button" class="qe-mega__toggle" aria-expanded="false" aria-controls="%1$s"><span class="qe-mega__bars" aria-hidden="true"></span><span class="qe-mega__toggle-text">%2$s</span></button>',
			esc_attr( $id ),
			esc_html( $s['toggle_text'] )
		);
		printf( '<ul class="qe-mega__menu" id="%s">', esc_attr( $id ) );
		$this->print_nodes( self::tree( $items ), 0, $s );
		echo '</ul></nav>';
	}
}
