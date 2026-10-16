<?php
/**
 * Search results.
 *
 * @package QeEdu
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( ! qe_edu_pro_location( 'archive' ) ) :
	?>
<main id="content" class="site-main qe-wrap qe-content-area">
	<header class="qe-page-header">
		<h1 class="qe-page-title"><?php qe_edu_echo_text( 'search_title', array( 'query' => get_search_query( false ) ) ); ?></h1>
		<?php get_search_form(); ?>
	</header>
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
		<p class="qe-empty"><?php qe_edu_echo_text( 'search_none' ); ?></p>
	<?php endif; ?>
</main>
	<?php
endif;

get_footer();
