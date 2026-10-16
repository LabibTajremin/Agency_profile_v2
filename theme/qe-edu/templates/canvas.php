<?php
/**
 * Template Name: Blank Canvas (no header or footer)
 * Template Post Type: page, post
 *
 * For landing pages: only the page content, no header, footer or wrappers.
 *
 * @package QeEdu
 */

defined( 'ABSPATH' ) || exit;

get_template_part( 'template-parts/document-open' );
?>
<main id="content" class="site-main qe-full-width qe-canvas">
	<?php
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
	?>
</main>
<?php wp_footer(); ?>
</body>
</html>
