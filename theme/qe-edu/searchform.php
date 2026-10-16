<?php
/**
 * Search form.
 *
 * @package QeEdu
 */

defined( 'ABSPATH' ) || exit;

$qe_edu_search_id = wp_unique_id( 'qe-search-' );
?>
<form role="search" method="get" class="qe-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $qe_edu_search_id ); ?>"><?php qe_edu_echo_text( 'search_label' ); ?></label>
	<input type="search" id="<?php echo esc_attr( $qe_edu_search_id ); ?>" class="qe-search-form__field" placeholder="<?php echo esc_attr( qe_edu_text( 'search_placeholder' ) ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>" name="s">
	<button type="submit" class="qe-btn qe-search-form__submit"><?php qe_edu_echo_text( 'search_button' ); ?></button>
</form>
