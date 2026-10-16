<?php
/**
 * Comments.
 *
 * @package QeEdu
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="qe-comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="qe-comments__title"><?php qe_edu_echo_text( 'comments_title' ); ?></h2>
		<ol class="qe-comments__list">
			<?php
			wp_list_comments(
				array(
					'style'      => 'ol',
					'short_ping' => true,
				)
			);
			?>
		</ol>
		<?php the_comments_navigation(); ?>
	<?php endif; ?>
	<?php if ( ! comments_open() && get_comments_number() ) : ?>
		<p class="qe-comments__closed"><?php qe_edu_echo_text( 'comments_closed' ); ?></p>
	<?php endif; ?>
	<?php comment_form(); ?>
</section>
