<?php
/**
 * Site footer.
 *
 * @package QeEdu
 */

defined( 'ABSPATH' ) || exit;

qe_edu_render_location( 'footer' );
?>
</div><!-- #page -->
<?php if ( qe_edu_enabled( 'footer.back_to_top', true ) ) : ?>
	<a href="#page" class="qe-back-to-top" hidden><span class="screen-reader-text"><?php qe_edu_echo_text( 'back_to_top' ); ?></span></a>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
