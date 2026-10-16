<?php
/**
 * Social Links: icons for every network filled in under Theme Settings →
 * Contact & Social (plus WhatsApp), so links are edited in one place.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Elementor\Widgets;

use Elementor\Controls_Manager;
use QeEdu\Core\Settings\Schema;
use QeEdu\Core\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Social links.
 */
class SocialLinks extends Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'qe-social-links';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Social Links', 'qe-edu' );
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-social-icons';
	}

	/**
	 * Font Awesome icon per network.
	 *
	 * @return array<string,string>
	 */
	public static function icons() {
		return array(
			'facebook'  => 'fab fa-facebook-f',
			'instagram' => 'fab fa-instagram',
			'linkedin'  => 'fab fa-linkedin-in',
			'x'         => 'fab fa-x-twitter',
			'youtube'   => 'fab fa-youtube',
			'tiktok'    => 'fab fa-tiktok',
			'pinterest' => 'fab fa-pinterest-p',
			'snapchat'  => 'fab fa-snapchat',
			'telegram'  => 'fab fa-telegram',
			'threads'   => 'fab fa-threads',
			'whatsapp'  => 'fab fa-whatsapp',
		);
	}

	/**
	 * WhatsApp chat link for a phone number.
	 *
	 * @param string $phone Phone with country code.
	 * @return string
	 */
	public static function whatsapp_url( $phone ) {
		$digits = preg_replace( '/\D+/', '', (string) $phone );

		return '' !== $digits ? 'https://wa.me/' . $digits : '';
	}

	/**
	 * Controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section( 'section_content', array( 'label' => __( 'Social links', 'qe-edu' ) ) );
		$this->add_control(
			'info',
			array(
				'type' => Controls_Manager::RAW_HTML,
				'raw'  => esc_html__( 'Links come from Theme Settings → Contact & Social. Only networks with a link are shown.', 'qe-edu' ),
			)
		);
		$this->add_switch( 'whatsapp', __( 'Include WhatsApp', 'qe-edu' ) );
		$this->add_select(
			'shape',
			__( 'Shape', 'qe-edu' ),
			array(
				'circle' => __( 'Circles', 'qe-edu' ),
				'square' => __( 'Rounded squares', 'qe-edu' ),
				'plain'  => __( 'Icons only', 'qe-edu' ),
			),
			'circle'
		);
		$this->add_text( 'nav_label', __( 'Label for screen readers', 'qe-edu' ), __( 'Social media', 'qe-edu' ) );
		$this->end_controls_section();

		$this->start_style_section();
		$this->add_control(
			'icon_color',
			array(
				'label'     => __( 'Icon colour', 'qe-edu' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => array( 'default' => self::COLOR_PRIMARY ),
				'selectors' => array( '{{WRAPPER}} .qe-social a' => 'color: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();
	}

	/**
	 * Links to show: network => [label, url, icon].
	 *
	 * @param bool $whatsapp Include WhatsApp.
	 * @return array<string,array<string,string>>
	 */
	public static function links( $whatsapp ) {
		$icons = self::icons();
		$links = array();

		foreach ( Schema::social_networks() as $key => $label ) {
			$url = (string) Settings::get( 'social.' . $key );
			if ( '' !== $url ) {
				$links[ $key ] = array(
					'label' => $label,
					'url'   => $url,
					'icon'  => $icons[ $key ],
				);
			}
		}

		$chat = self::whatsapp_url( (string) Settings::get( 'contact.whatsapp' ) );
		if ( $whatsapp && '' !== $chat ) {
			$links['whatsapp'] = array(
				'label' => 'WhatsApp',
				'url'   => $chat,
				'icon'  => $icons['whatsapp'],
			);
		}

		return $links;
	}

	/**
	 * Render.
	 *
	 * @return void
	 */
	protected function render() {
		$s     = $this->get_settings_for_display();
		$links = self::links( 'yes' === $s['whatsapp'] );

		if ( ! $links ) {
			$this->print_editor_notice( __( 'Add your social media links in Theme Settings → Contact & Social.', 'qe-edu' ) );
			return;
		}

		printf( '<nav class="qe-social qe-social--%1$s" aria-label="%2$s"><ul>', esc_attr( in_array( $s['shape'], array( 'circle', 'square', 'plain' ), true ) ? $s['shape'] : 'circle' ), esc_attr( $s['nav_label'] ) );
		foreach ( $links as $link ) {
			printf( '<li><a href="%1$s" target="_blank" rel="noopener" aria-label="%2$s">', esc_url( $link['url'] ), esc_attr( $link['label'] ) );
			$this->print_icon(
				array(
					'value'   => $link['icon'],
					'library' => 'fa-brands',
				)
			);
			echo '</a></li>';
		}
		echo '</ul></nav>';
	}
}
