<?php
/**
 * Fallback footer, used only when no Elementor footer template is assigned.
 *
 * @package QeEdu
 */

defined( 'ABSPATH' ) || exit;

$qe_edu_columns   = array( 'footer-resources', 'footer-study', 'footer-about' );
$qe_edu_copyright = qe_edu_setting_text( 'footer.copyright', 'copyright' );
?>
<footer class="qe-footer">
	<div class="qe-wrap qe-footer__grid">
		<div class="qe-footer__brand">
			<?php qe_edu_site_branding(); ?>
			<p class="qe-footer__tagline"><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
		</div>
		<?php foreach ( $qe_edu_columns as $qe_edu_location ) : ?>
			<?php if ( has_nav_menu( $qe_edu_location ) ) : ?>
				<div class="qe-footer__col">
					<h2 class="qe-footer__heading"><?php echo esc_html( wp_get_nav_menu_name( $qe_edu_location ) ); ?></h2>
					<?php qe_edu_nav( $qe_edu_location, 'footer_label', 'qe-footer__nav' ); ?>
				</div>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
	<div class="qe-footer__bottom">
		<div class="qe-wrap qe-footer__bottom-inner">
			<p class="qe-footer__copyright"><?php echo esc_html( $qe_edu_copyright ); ?></p>
			<?php qe_edu_nav( 'legal', 'legal_label', 'qe-footer__legal' ); ?>
		</div>
	</div>
</footer>
