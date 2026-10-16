<?php
/**
 * 404 template. When an Elementor template is assigned in
 * Theme Settings → Header (404 page), that template is rendered instead.
 *
 * @package QeEdu
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="content" class="site-main">
	<?php if ( ! qe_edu_render_template_setting( 'templates.not_found' ) ) : ?>
		<section class="qe-wrap qe-content-area qe-404">
			<h1 class="qe-page-title"><?php qe_edu_echo_text( 'not_found_title' ); ?></h1>
			<p><?php qe_edu_echo_text( 'not_found_text' ); ?></p>
			<?php get_search_form(); ?>
		</section>
	<?php endif; ?>
</main>
<?php
get_footer();
