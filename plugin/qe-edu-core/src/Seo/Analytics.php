<?php
/**
 * Google Analytics 4 / Tag Manager, optionally behind a consent bar.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Seo;

use QeEdu\Core\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Analytics.
 */
final class Analytics {

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! Conflict::output_enabled() ) {
			return;
		}

		add_action( 'wp_head', array( $this, 'head' ), 5 );
		add_action( 'wp_body_open', array( $this, 'body' ), 1 );
		add_action( 'wp_footer', array( $this, 'consent_bar' ), 30 );
	}

	/**
	 * Whether this request is the Theme Settings live preview (never tracked).
	 *
	 * @return bool
	 */
	public static function is_preview() {
		return isset( $_GET['qe_preview'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only flag.
	}

	/**
	 * Valid GA4 id, or ''.
	 *
	 * @return string
	 */
	public static function ga4() {
		$id = strtoupper( trim( (string) Settings::get( 'seo.ga4_id' ) ) );

		return preg_match( '/^G-[A-Z0-9]{4,20}$/', $id ) ? $id : '';
	}

	/**
	 * Valid GTM id, or ''.
	 *
	 * @return string
	 */
	public static function gtm() {
		$id = strtoupper( trim( (string) Settings::get( 'seo.gtm_id' ) ) );

		return preg_match( '/^GTM-[A-Z0-9]{4,12}$/', $id ) ? $id : '';
	}

	/**
	 * Whether analytics wait for consent.
	 *
	 * @return bool
	 */
	public static function needs_consent() {
		return (bool) Settings::get( 'seo.consent' ) && ( '' !== self::ga4() || '' !== self::gtm() );
	}

	/**
	 * The loader script (runs immediately, or after consent).
	 *
	 * @return string JavaScript.
	 */
	public static function loader() {
		$js  = '';
		$ga4 = self::ga4();
		$gtm = self::gtm();

		if ( '' !== $ga4 ) {
			$js .= "var s=document.createElement('script');s.async=true;s.src='https://www.googletagmanager.com/gtag/js?id=" . $ga4 . "';document.head.appendChild(s);"
				. "window.dataLayer=window.dataLayer||[];window.gtag=function(){dataLayer.push(arguments);};gtag('js',new Date());gtag('config','" . $ga4 . "');";
		}

		if ( '' !== $gtm ) {
			$js .= "(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s);j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','" . $gtm . "');";
		}

		return $js;
	}

	/**
	 * Print the loader in <head>.
	 *
	 * @return void
	 */
	public function head() {
		$loader = self::loader();
		if ( '' === $loader || self::is_preview() ) {
			return;
		}

		if ( self::needs_consent() ) {
			// Only after the visitor accepted (stored in this browser).
			$loader = 'window.qeEduLoadAnalytics=function(){if(window.qeEduAnalyticsLoaded){return;}window.qeEduAnalyticsLoaded=true;' . $loader . "};try{if(localStorage.getItem('qe_consent')==='yes'){window.qeEduLoadAnalytics();}}catch(e){}";
		}

		echo '<script>' . $loader . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ids are validated above.
	}

	/**
	 * GTM <noscript> fallback (only without a consent requirement).
	 *
	 * @return void
	 */
	public function body() {
		$gtm = self::gtm();
		if ( '' === $gtm || self::needs_consent() || self::is_preview() ) {
			return;
		}

		printf(
			'<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=%s" height="0" width="0" style="display:none;visibility:hidden" title="Google Tag Manager"></iframe></noscript>',
			esc_attr( $gtm )
		);
	}

	/**
	 * Consent bar.
	 *
	 * @return void
	 */
	public function consent_bar() {
		if ( ! self::needs_consent() || self::is_preview() ) {
			return;
		}

		$text    = (string) Settings::get( 'seo.consent_text' );
		$accept  = (string) Settings::get( 'seo.consent_accept' );
		$decline = (string) Settings::get( 'seo.consent_decline' );

		wp_enqueue_style( 'qe-edu-core-widgets' );

		printf(
			'<div class="qe-consent" role="region" aria-label="%1$s" hidden><p>%2$s</p><div class="qe-consent__actions"><button type="button" class="qe-btn" data-qe-consent="yes">%3$s</button><button type="button" class="qe-btn qe-btn--ghost" data-qe-consent="no">%4$s</button></div></div>',
			esc_attr__( 'Cookie consent', 'qe-edu-core' ),
			esc_html( '' !== $text ? $text : __( 'We use cookies to understand how visitors use this site. Is that OK?', 'qe-edu-core' ) ),
			esc_html( '' !== $accept ? $accept : __( 'Accept', 'qe-edu-core' ) ),
			esc_html( '' !== $decline ? $decline : __( 'Decline', 'qe-edu-core' ) )
		);
		wp_enqueue_script( 'qe-edu-core-consent', QE_EDU_CORE_URL . 'assets/js/consent.min.js', array(), QE_EDU_CORE_VERSION, true );
	}
}
