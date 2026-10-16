<?php
/**
 * Full single post. `$args['hide_title']` is set when a banner template
 * already shows the title.
 *
 * @package QeEdu
 */

defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'qe-entry' ); ?>>
	<header class="qe-entry__header">
		<?php
		if ( empty( $args['hide_title'] ) ) {
			the_title( '<h1 class="qe-page-title">', '</h1>' );
		}
		?>
		<?php qe_edu_posted_on(); ?>
	</header>
	<?php if ( has_post_thumbnail() ) : ?>
		<figure class="qe-entry__media"><?php the_post_thumbnail( 'large' ); ?></figure>
	<?php endif; ?>
	<div class="qe-entry__content">
		<?php
		the_content();
		wp_link_pages(
			array(
				'before' => '<nav class="qe-page-links">' . esc_html( qe_edu_text( 'pages' ) ),
				'after'  => '</nav>',
			)
		);
		?>
	</div>
	<footer class="qe-entry__footer">
		<?php the_tags( '<p class="qe-tags">', ' ', '</p>' ); ?>
	</footer>
</article>
