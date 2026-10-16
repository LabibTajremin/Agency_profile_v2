<?php
/**
 * WP-CLI fixture for the SEO gate: a parent + child page with per-page SEO
 * values (Course schema), a "noindex" page, an event with a date, one
 * redirect and an extra robots.txt rule. Idempotent. Prints JSON.
 *
 * Usage: wp eval-file tests/e2e/fixtures/create-seo-fixtures.php
 *
 * @package QeEdu
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- WP-CLI script scope.

$upsert = static function ( $path, $args, $meta = array() ) {
	$slug     = basename( $path );
	$existing = get_page_by_path( $path, OBJECT, $args['post_type'] );
	$id       = $existing ? $existing->ID : wp_insert_post(
		array_merge(
			array(
				'post_name'   => $slug,
				'post_status' => 'publish',
			),
			$args
		)
	);
	foreach ( $meta as $key => $value ) {
		update_post_meta( $id, $key, $value );
	}
	return (int) $id;
};

$parent = $upsert(
	'seo-parent',
	array(
		'post_type'    => 'page',
		'post_title'   => 'Study Abroad Guide',
		'post_content' => 'Guides for students.',
	)
);
$page   = $upsert(
	'seo-parent/seo-page',
	array(
		'post_type'    => 'page',
		'post_title'   => 'Study in Canada',
		'post_parent'  => $parent,
		'post_content' => 'Everything about studying in Canada.',
	),
	array(
		'_qe_seo_title'       => 'Custom SEO Title {sep} {site}',
		'_qe_seo_description' => 'Plan your studies in Canada with expert help.',
		'_qe_seo_keyword'     => 'canada',
		'_qe_seo_og_title'    => 'Study in Canada with us',
		'_qe_seo_schema'      => 'Course',
	)
);
$hidden = $upsert(
	'seo-hidden',
	array(
		'post_type'    => 'page',
		'post_title'   => 'Hidden Page',
		'post_content' => 'Not for search engines.',
	),
	array( '_qe_seo_noindex' => '1' )
);
$event  = $upsert(
	'seo-fair',
	array(
		'post_type'    => 'qe_event',
		'post_title'   => 'Education Fair',
		'post_excerpt' => 'Meet universities in person.',
	),
	array(
		'_qe_date'  => '2026-12-01',
		'_qe_time'  => '10:00',
		'_qe_venue' => 'Dhaka',
	)
);

$settings = get_option( 'qe_edu_settings', array() );
$settings = is_array( $settings ) ? $settings : array();
$seo      = isset( $settings['seo'] ) && is_array( $settings['seo'] ) ? $settings['seo'] : array();
$rows     = isset( $seo['redirects'] ) && is_array( $seo['redirects'] ) ? $seo['redirects'] : array();
if ( ! in_array( '/old-seo/', array_column( $rows, 'from' ), true ) ) {
	$rows[] = array(
		'from' => '/old-seo/',
		'to'   => '/seo-parent/seo-page/',
	);
}
$seo['redirects']      = $rows;
$seo['robots_txt']     = 'Disallow: /private/';
$settings['seo']       = $seo;
update_option( 'qe_edu_settings', $settings );
flush_rewrite_rules();

echo wp_json_encode(
	array(
		'page'      => $page,
		'pageUrl'   => get_permalink( $page ),
		'parentUrl' => get_permalink( $parent ),
		'hiddenUrl' => get_permalink( $hidden ),
		'eventUrl'  => get_permalink( $event ),
	)
);
