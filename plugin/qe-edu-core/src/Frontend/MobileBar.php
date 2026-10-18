<?php
/**
 * Sticky "Apply / WhatsApp" bar at the bottom of the screen on phones
 * (Theme Settings → Footer).
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Frontend;

use QeEdu\Core\Elementor\Widgets\SocialLinks;
use QeEdu\Core\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Mobile bar.
 */
final class MobileBar {

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_footer', array( $this, 'render' ), 5 );
		add_filter( 'body_class', array( $this, 'body_class' ) );
	}

	/**
	 * Buttons to show: [text, url, class].
	 *
	 * @return array<int,array<string,string>>
	 */
	public static function buttons() {
		if ( ! Settings::get( 'footer.mobile_bar' ) ) {
			return array();
		}

		$buttons = array();
		$link    = (string) Settings::get( 'footer.mobile_bar_link' );
		if ( '' !== $link ) {
			$text      = (string) Settings::get( 'footer.mobile_bar_text' );
			$buttons[] = array(
				'text'  => '' !== $text ? $text : __( 'Apply now', 'qe-edu-core' ),
				'url'   => $link,
				'class' => 'qe-mobile-bar__apply',
			);
		}

		$chat = SocialLinks::whatsapp_url( (string) Settings::get( 'contact.whatsapp' ) );
		if ( '' !== $chat ) {
			$text      = (string) Settings::get( 'footer.mobile_bar_chat' );
			$buttons[] = array(
				'text'  => '' !== $text ? $text : __( 'WhatsApp us', 'qe-edu-core' ),
				'url'   => $chat,
				'class' => 'qe-mobile-bar__chat',
			);
		}

		return $buttons;
	}

	/**
	 * Reserve space at the bottom of the page when the bar shows.
	 *
	 * @param string[] $classes Classes.
	 * @return string[]
	 */
	public function body_class( $classes ) {
		if ( self::buttons() ) {
			$classes[] = 'qe-has-mobile-bar';
		}

		return $classes;
	}

	/**
	 * Print the bar.
	 *
	 * @return void
	 */
	public function render() {
		$buttons = self::buttons();
		if ( ! $buttons ) {
			return;
		}

		wp_enqueue_style( 'qe-edu-core-widgets' );

		echo '<div class="qe-mobile-bar">';
		foreach ( $buttons as $button ) {
			printf(
				'<a class="%1$s" href="%2$s"%3$s>%4$s</a>',
				esc_attr( $button['class'] ),
				esc_url( $button['url'] ),
				0 === strpos( $button['url'], 'https://wa.me/' ) ? ' target="_blank" rel="noopener"' : '',
				esc_html( $button['text'] )
			);
		}
		echo '</div>';
	}
}
