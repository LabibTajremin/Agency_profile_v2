<?php
/**
 * CTA Download: "Company Profile" block with a "Download PDF" button. The file
 * defaults to Theme Settings → Brand → Company profile PDF.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Elementor\Widgets;

use Elementor\Controls_Manager;
use QeEdu\Core\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * CTA download.
 */
class CtaDownload extends Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'qe-cta-download';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'CTA Download', 'qe-edu' );
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-download-button';
	}

	/**
	 * Controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section( 'section_content', array( 'label' => __( 'Content', 'qe-edu' ) ) );
		$this->add_text( 'heading', __( 'Heading', 'qe-edu' ), __( 'Company Profile', 'qe-edu' ) );
		$this->add_textarea( 'text', __( 'Text', 'qe-edu' ), __( 'Discover our story, services and global network in one document.', 'qe-edu' ) );
		$this->add_text( 'button_text', __( 'Button text', 'qe-edu' ), __( 'Download PDF', 'qe-edu' ) );
		$this->add_control(
			'file',
			array(
				'label'       => __( 'File', 'qe-edu' ),
				'type'        => Controls_Manager::MEDIA,
				'media_types' => array( 'application/pdf' ),
				'default'     => array( 'url' => '' ),
				'description' => __( 'Leave empty to use Theme Settings → Brand → Company profile PDF.', 'qe-edu' ),
			)
		);
		$this->add_control(
			'image',
			array(
				'label'   => __( 'Image (optional)', 'qe-edu' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => array( 'url' => '' ),
			)
		);
		$this->end_controls_section();

		$this->start_style_section();
		$this->add_text_style( 'heading', __( 'Heading', 'qe-edu' ), '.qe-cta__heading', self::COLOR_SECONDARY, self::FONT_PRIMARY );
		$this->add_text_style( 'text', __( 'Text', 'qe-edu' ), '.qe-cta__text', self::COLOR_TEXT, self::FONT_TEXT );
		$this->add_button_style( '.qe-cta .qe-btn' );
		$this->end_controls_section();
	}

	/**
	 * URL of the file to download.
	 *
	 * @param array<string,mixed> $s Settings.
	 * @return string
	 */
	public static function file_url( $s ) {
		if ( ! empty( $s['file']['url'] ) ) {
			return (string) $s['file']['url'];
		}

		$id = absint( Settings::get( 'brand.profile_pdf' ) );

		return $id ? (string) wp_get_attachment_url( $id ) : '';
	}

	/**
	 * Render.
	 *
	 * @return void
	 */
	protected function render() {
		$s   = $this->get_settings_for_display();
		$url = self::file_url( $s );

		echo '<div class="qe-cta">';
		if ( ! empty( $s['image']['url'] ) || ! empty( $s['image']['id'] ) ) {
			echo '<div class="qe-cta__media">';
			$this->print_image( $s['image'], 'large', 'qe-cta__img', $s['heading'] );
			echo '</div>';
		}
		echo '<div class="qe-cta__body">';
		$this->print_heading( $s['heading'], 'h2', 'qe-cta__heading' );
		$this->print_text( $s['text'], 'qe-cta__text' );

		if ( '' !== $url ) {
			$this->add_render_attribute( 'download_button', 'download', wp_basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ) );
			$this->print_button( 'download_button', $s['button_text'], array( 'url' => $url ), 'qe-btn qe-btn--download' );
		} else {
			$this->print_editor_notice( __( 'Choose a PDF here or in Theme Settings → Brand to show the download button.', 'qe-edu' ) );
		}
		echo '</div></div>';
	}
}
