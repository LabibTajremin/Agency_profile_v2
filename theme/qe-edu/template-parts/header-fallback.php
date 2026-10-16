<?php
/**
 * Fallback header, used only when no Elementor header template is assigned.
 *
 * @package QeEdu
 */

defined( 'ABSPATH' ) || exit;

$qe_edu_cta_text = qe_edu_setting_text( 'header.cta_text', 'header_cta_text' );
$qe_edu_cta_link = qe_edu_setting( 'header.cta_link', '' );
?>
<header class="qe-header" data-qe-header>
	<?php if ( qe_edu_enabled( 'header.topbar', true ) && has_nav_menu( 'topbar' ) ) : ?>
		<div class="qe-topbar">
			<div class="qe-wrap">
				<?php qe_edu_nav( 'topbar', 'topbar_label', 'qe-topbar__nav' ); ?>
			</div>
		</div>
	<?php endif; ?>
	<div class="qe-header__main">
		<div class="qe-wrap qe-header__inner">
			<div class="qe-header__brand"><?php qe_edu_site_branding(); ?></div>
			<button type="button" class="qe-menu-toggle" aria-controls="qe-primary-nav" aria-expanded="false" data-qe-menu-toggle>
				<span class="qe-menu-toggle__bar" aria-hidden="true"></span>
				<span class="screen-reader-text" data-qe-menu-label><?php qe_edu_echo_text( 'menu_open' ); ?></span>
			</button>
			<div class="qe-header__panel" id="qe-primary-nav" data-qe-menu-panel>
				<?php qe_edu_nav( 'primary', 'menu_label', 'qe-primary-nav', 3 ); ?>
				<?php if ( $qe_edu_cta_link ) : ?>
					<a class="qe-btn qe-header__cta" href="<?php echo esc_url( $qe_edu_cta_link ); ?>"><?php echo esc_html( $qe_edu_cta_text ); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</header>
