<?php
/**
 * Single post template.
 *
 * @package QeEdu
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( ! qe_edu_pro_location( 'single' ) ) :
	?>
<main id="content" class="site-main qe-wrap qe-content-area">
	<?php
	while ( have_posts() ) :
		the_post();
		get_template_part( 'template-parts/content', 'single' );

		the_post_navigation(
			array(
				'prev_text' => '<span class="qe-nav-label">' . esc_html( qe_edu_text( 'previous' ) ) . '</span> %title',
				'next_text' => '<span class="qe-nav-label">' . esc_html( qe_edu_text( 'next' ) ) . '</span> %title',
			)
		);

		if ( comments_open() || get_comments_number() ) {
			comments_template();
		}
	endwhile;
	?>
</main>
	<?php
endif;

get_footer();
