<?php
/**
 * Page content inside the default page template.
 *
 * @package QeEdu
 */

defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'qe-entry' ); ?>>
	<?php if ( ! qe_edu_hide_title() ) : ?>
		<header class="qe-entry__header"><?php the_title( '<h1 class="qe-page-title">', '</h1>' ); ?></header>
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
</article>
