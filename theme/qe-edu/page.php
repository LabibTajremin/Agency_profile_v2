<?php
/**
 * Default page template.
 *
 * @package QeEdu
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="content" class="site-main qe-wrap qe-content-area">
	<?php
	while ( have_posts() ) :
		the_post();
		get_template_part( 'template-parts/content', 'page' );

		if ( comments_open() || get_comments_number() ) {
			comments_template();
		}
	endwhile;
	?>
</main>
<?php
get_footer();
