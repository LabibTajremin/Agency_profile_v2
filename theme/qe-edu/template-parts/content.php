<?php
/**
 * Post card used in archives, search and the blog index.
 *
 * @package QeEdu
 */

defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'qe-card' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<a class="qe-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
			<?php the_post_thumbnail( 'qe-card' ); ?>
		</a>
	<?php endif; ?>
	<div class="qe-card__body">
		<h2 class="qe-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
		<?php if ( 'post' === get_post_type() ) : ?>
			<?php qe_edu_posted_on(); ?>
		<?php endif; ?>
		<div class="qe-card__excerpt"><?php the_excerpt(); ?></div>
		<a class="qe-link-arrow" href="<?php the_permalink(); ?>"><?php qe_edu_echo_text( 'read_more' ); ?><span class="screen-reader-text"> <?php the_title(); ?></span></a>
	</div>
</article>
