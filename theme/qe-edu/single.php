<?php
/**
 * Single post template.
 *
 * @package QeEdu
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( ! qe_edu_pro_location( 'single' ) ) :
	// Optional Elementor banner (Theme Settings → Texts → "Blog post banner"); it shows the title.
	$qe_edu_banner = qe_edu_render_template_setting( 'templates.single' );
	?>
<main id="content" class="site-main qe-wrap qe-content-area">
	<?php
	while ( have_posts() ) :
		the_post();
		get_template_part( 'template-parts/content', 'single', array( 'hide_title' => $qe_edu_banner ) );

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
