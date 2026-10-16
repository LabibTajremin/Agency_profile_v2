<?php
/**
 * Event List: upcoming events from the Events content type, with Event schema.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Elementor\Widgets;

use QeEdu\Core\PostTypes\Types;

defined( 'ABSPATH' ) || exit;

/**
 * Event list.
 */
class EventList extends Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'qe-event-list';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Event List', 'qe-edu' );
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-calendar';
	}

	/**
	 * Controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section( 'section_content', array( 'label' => __( 'Events', 'qe-edu' ) ) );
		$this->add_text( 'heading', __( 'Heading', 'qe-edu' ), __( 'Upcoming events & webinars', 'qe-edu' ) );
		$this->add_number( 'count', __( 'How many', 'qe-edu' ), 6, 1, 50 );
		$this->add_switch( 'show_past', __( 'Also show past events', 'qe-edu' ), '' );
		$this->add_select(
			'layout',
			__( 'Layout', 'qe-edu' ),
			array(
				'list' => __( 'List', 'qe-edu' ),
				'grid' => __( 'Cards', 'qe-edu' ),
			),
			'list'
		);
		$this->add_text( 'button_text', __( 'Button text', 'qe-edu' ), __( 'Details & registration', 'qe-edu' ) );
		$this->add_text( 'online_label', __( 'Label for online events', 'qe-edu' ), __( 'Online', 'qe-edu' ) );
		$this->add_text( 'empty_text', __( 'Text when there are no events', 'qe-edu' ), __( 'No upcoming events right now — please check back soon.', 'qe-edu' ) );
		$this->add_switch( 'schema', __( 'Tell Google about these events (rich results)', 'qe-edu' ) );
		$this->end_controls_section();

		$this->start_style_section();
		$this->add_text_style( 'title', __( 'Event title', 'qe-edu' ), '.qe-event__title', self::COLOR_SECONDARY, self::FONT_SECONDARY );
		$this->add_text_style( 'date', __( 'Date', 'qe-edu' ), '.qe-event__date', self::COLOR_PRIMARY, self::FONT_ACCENT );
		$this->end_controls_section();
	}

	/**
	 * Events.
	 *
	 * @param array<string,mixed> $s Settings.
	 * @return \WP_Post[]
	 */
	public function events( $s ) {
		$args = array(
			'meta_key' => '_qe_date', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'orderby'  => 'meta_value',
			'order'    => 'ASC',
		);

		if ( 'yes' !== $s['show_past'] ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_qe_date',
					'value'   => wp_date( 'Y-m-d' ),
					'compare' => '>=',
				),
			);
		}

		return $this->posts( 'qe_event', (int) $s['count'], $args );
	}

	/**
	 * Event JSON-LD.
	 *
	 * @param \WP_Post $post Event.
	 * @return array<string,mixed>
	 */
	public static function schema( $post ) {
		$date   = Types::get( $post->ID, 'date' );
		$time   = Types::get( $post->ID, 'time' );
		$online = Types::get( $post->ID, 'online_link' );
		$venue  = Types::get( $post->ID, 'venue' );
		$start  = $date;

		if ( '' !== $time ) {
			// Full ISO 8601 with the site's time zone offset.
			$moment = date_create( $date . ' ' . $time, wp_timezone() );
			$start  = $moment ? $moment->format( 'c' ) : $date . 'T' . $time;
		}

		$data = array(
			'@context'            => 'https://schema.org',
			'@type'               => 'Event',
			'name'                => get_the_title( $post ),
			'startDate'           => $start,
			'description'         => wp_strip_all_tags( get_the_excerpt( $post ) ),
			'url'                 => (string) get_permalink( $post ),
			'eventStatus'         => 'https://schema.org/EventScheduled',
			'eventAttendanceMode' => '' !== $online ? 'https://schema.org/OnlineEventAttendanceMode' : 'https://schema.org/OfflineEventAttendanceMode',
			'location'            => '' !== $online
				? array(
					'@type' => 'VirtualLocation',
					'url'   => $online,
				)
				: array(
					'@type'   => 'Place',
					'name'    => $venue,
					'address' => $venue,
				),
			'organizer'           => array(
				'@type' => 'Organization',
				'name'  => get_bloginfo( 'name' ),
				'url'   => home_url( '/' ),
			),
		);

		$image = get_the_post_thumbnail_url( $post, 'large' );
		if ( $image ) {
			$data['image'] = $image;
		}

		return $data;
	}

	/**
	 * Render.
	 *
	 * @return void
	 */
	protected function render() {
		$s      = $this->get_settings_for_display();
		$events = $this->events( $s );

		echo '<div class="qe-section-head">';
		$this->print_heading( $s['heading'], 'h2', 'qe-section-head__title' );
		echo '</div>';

		if ( ! $events ) {
			$this->print_text( $s['empty_text'], 'qe-empty' );
			return;
		}

		$schema = array();
		printf( '<div class="qe-events qe-events--%s">', 'grid' === $s['layout'] ? 'grid' : 'list' );
		foreach ( $events as $i => $post ) {
			$date   = Types::get( $post->ID, 'date' );
			$time   = Types::get( $post->ID, 'time' );
			$venue  = Types::get( $post->ID, 'venue' );
			$online = Types::get( $post->ID, 'online_link' );
			$stamp  = '' !== $date ? (int) strtotime( $date ) : 0;

			echo '<article class="qe-event">';
			if ( $stamp ) {
				printf(
					'<time class="qe-event__date" datetime="%1$s"><span class="qe-event__day">%2$s</span><span class="qe-event__month">%3$s</span></time>',
					esc_attr( $date . ( '' !== $time ? 'T' . $time : '' ) ),
					esc_html( date_i18n( 'd', $stamp ) ),
					esc_html( date_i18n( 'M Y', $stamp ) )
				);
			}
			echo '<div class="qe-event__body">';
			printf( '<h3 class="qe-event__title">%s</h3>', esc_html( get_the_title( $post ) ) );
			$meta = array_filter( array( '' !== $time ? $time : '', '' !== $online ? (string) $s['online_label'] : $venue ) );
			if ( $meta ) {
				printf( '<p class="qe-event__meta">%s</p>', esc_html( implode( ' · ', $meta ) ) );
			}
			$this->print_text( get_the_excerpt( $post ), 'qe-event__text' );
			$this->print_button( 'event-' . $i, $s['button_text'], array( 'url' => (string) get_permalink( $post ) ), 'qe-link-arrow' );
			echo '</div></article>';

			$schema[] = self::schema( $post );
		}
		echo '</div>';

		if ( 'yes' === $s['schema'] ) {
			printf( '<script type="application/ld+json">%s</script>', wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON with HEX_TAG.
		}
	}
}
