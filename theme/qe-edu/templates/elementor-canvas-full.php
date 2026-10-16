<?php
/**
 * Template Name: Full Width (Header + Footer)
 * Template Post Type: page, post, qe_destination, qe_service, qe_event
 *
 * Zero wrapper interference: no container, max-width, padding, title or
 * comments — Elementor controls the whole content area.
 *
 * @package QeEdu
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="content" class="site-main qe-full-width">
	<?php
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
	?>
</main>
<?php
get_footer();
