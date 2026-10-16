<?php
/**
 * Team Grid: people from the Team content type.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Elementor\Widgets;

use Elementor\Controls_Manager;
use QeEdu\Core\PostTypes\Types;

defined( 'ABSPATH' ) || exit;

/**
 * Team grid.
 */
class TeamGrid extends Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'qe-team';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Team Grid', 'qe-edu' );
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-person';
	}

	/**
	 * Controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section( 'section_content', array( 'label' => __( 'Team', 'qe-edu' ) ) );
		$this->add_text( 'heading', __( 'Heading', 'qe-edu' ), __( 'Meet our team', 'qe-edu' ) );
		$this->add_number( 'count', __( 'How many', 'qe-edu' ), 12, 1, 100 );
		$this->add_responsive_control(
			'columns',
			array(
				'label'          => __( 'Columns', 'qe-edu' ),
				'type'           => Controls_Manager::SELECT,
				'options'        => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
				),
				'default'        => '4',
				'tablet_default' => '3',
				'mobile_default' => '2',
				'selectors'      => array( '{{WRAPPER}} .qe-team' => '--qe-cols: {{VALUE}};' ),
			)
		);
		$this->add_switch( 'show_bio', __( 'Show short bio', 'qe-edu' ), '' );
		$this->add_switch( 'show_linkedin', __( 'Show LinkedIn link', 'qe-edu' ) );
		$this->add_text( 'linkedin_label', __( 'LinkedIn link text', 'qe-edu' ), __( 'LinkedIn profile', 'qe-edu' ) );
		$this->end_controls_section();

		$this->start_style_section();
		$this->add_text_style( 'name', __( 'Name', 'qe-edu' ), '.qe-person__name', self::COLOR_SECONDARY, self::FONT_SECONDARY );
		$this->add_text_style( 'role', __( 'Job title', 'qe-edu' ), '.qe-person__role', self::COLOR_PRIMARY, self::FONT_TEXT );
		$this->end_controls_section();
	}

	/**
	 * Render.
	 *
	 * @return void
	 */
	protected function render() {
		$s      = $this->get_settings_for_display();
		$people = $this->posts( 'qe_team', (int) $s['count'] );

		if ( ! $people ) {
			$this->print_editor_notice( __( 'No team members yet. Add them under Team in the dashboard menu.', 'qe-edu' ) );
			return;
		}

		echo '<div class="qe-section-head">';
		$this->print_heading( $s['heading'], 'h2', 'qe-section-head__title' );
		echo '</div>';

		$this->prime_images(
			array_map(
				static function ( $person ) {
					return array( 'image' => (int) get_post_thumbnail_id( $person ) );
				},
				$people
			)
		);
		echo '<div class="qe-team">';
		foreach ( $people as $person ) {
			$name     = get_the_title( $person );
			$linkedin = Types::get( $person->ID, 'linkedin' );

			echo '<figure class="qe-person"><div class="qe-person__media">';
			if ( ! $this->print_image( (int) get_post_thumbnail_id( $person ), 'medium_large', 'qe-person__img', $name ) ) {
				echo '<span class="qe-placeholder" aria-hidden="true"></span>';
			}
			echo '</div><figcaption>';
			printf( '<p class="qe-person__name">%s</p>', esc_html( $name ) );
			printf( '<p class="qe-person__role">%s</p>', esc_html( Types::get( $person->ID, 'role' ) ) );
			if ( 'yes' === $s['show_bio'] ) {
				$this->print_text( get_the_excerpt( $person ), 'qe-person__bio' );
			}
			if ( 'yes' === $s['show_linkedin'] && '' !== $linkedin ) {
				printf(
					'<a class="qe-person__link" href="%1$s" target="_blank" rel="noopener">%2$s<span class="screen-reader-text"> — %3$s</span></a>',
					esc_url( $linkedin ),
					esc_html( $s['linkedin_label'] ),
					esc_html( $name )
				);
			}
			echo '</figcaption></figure>';
		}
		echo '</div>';
	}
}
