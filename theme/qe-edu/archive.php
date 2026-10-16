<?php
/**
 * Archive template (categories, tags, dates, authors, custom post types).
 *
 * @package QeEdu
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( ! qe_edu_pro_location( 'archive' ) ) :
	// Optional Elementor banner (Theme Settings → Texts → "Blog & archive banner").
	$qe_edu_banner = qe_edu_render_template_setting( 'templates.archive' );
	?>
<main id="content" class="site-main qe-wrap qe-content-area">
	<?php if ( ! $qe_edu_banner ) : ?>
	<header class="qe-page-header">
		<h1 class="qe-page-title">
			<?php
			if ( is_home() && ! is_front_page() ) {
				single_post_title();
			} elseif ( is_archive() ) {
				echo wp_kses_post( get_the_archive_title() );
			} else {
				qe_edu_echo_text( 'blog_title' );
			}
			?>
		</h1>
		<?php the_archive_description( '<div class="qe-archive-description">', '</div>' ); ?>
	</header>
	<?php endif; ?>
	<?php if ( have_posts() ) : ?>
		<div class="qe-post-grid">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/content', get_post_type() );
			endwhile;
			?>
		</div>
		<?php
		the_posts_pagination(
			array(
				'prev_text' => esc_html( qe_edu_text( 'previous' ) ),
				'next_text' => esc_html( qe_edu_text( 'next' ) ),
			)
		);
		?>
	<?php else : ?>
		<?php get_template_part( 'template-parts/content', 'none' ); ?>
	<?php endif; ?>
</main>
	<?php
endif;

get_footer();
