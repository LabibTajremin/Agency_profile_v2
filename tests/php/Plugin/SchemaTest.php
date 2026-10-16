<?php
/**
 * Schema tests.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Plugin;

use Brain\Monkey\Filters;
use QeEdu\Core\Settings\Schema;
use QeEdu\Tests\ThemeLoader;

/**
 * Schema.
 */
final class SchemaTest extends PluginTestCase {

	const TYPES = array( 'text', 'textarea', 'code', 'url', 'email', 'tel', 'number', 'checkbox', 'select', 'color', 'media', 'template', 'swatch', 'redirects' );

	public function test_texts_tab_without_theme_has_only_the_404_template() {
		if ( function_exists( 'qe_edu_default_strings' ) ) {
			$this->markTestSkipped( 'Theme already loaded in this process.' );
		}
		$this->assertSame( array( 'not_found_template' ), array_keys( Schema::tabs()['texts']['fields'] ) );
	}

	public function test_tabs() {
		$this->assertSame(
			array( 'brand', 'colors', 'typography', 'header', 'footer', 'contact', 'seo', 'performance', 'texts', 'tools' ),
			array_keys( Schema::tabs() )
		);
	}

	public function test_tabs_are_filterable() {
		Filters\expectApplied( 'qe_edu_settings_schema' )->once()->andReturn( array( 'x' => array( 'fields' => array() ) ) );
		$this->assertSame( array( 'x' ), array_keys( Schema::tabs() ) );
	}

	public function test_every_field_is_complete_and_plain_language() {
		ThemeLoader::load();

		foreach ( Schema::fields() as $path => $field ) {
			$this->assertContains( $field['type'], self::TYPES, $path );
			$this->assertNotSame( '', $field['label'], $path );
			$this->assertArrayHasKey( 'help', $field, $path );
			$this->assertArrayHasKey( 'default', $field, $path );
			$this->assertSame( $path, $field['path'] );
			if ( 'select' === $field['type'] ) {
				$this->assertArrayHasKey( $field['default'], $field['options'], $path );
			}
			if ( 'number' === $field['type'] ) {
				$this->assertGreaterThanOrEqual( $field['min'], $field['default'] );
				$this->assertLessThanOrEqual( $field['max'], $field['default'] );
			}
		}
	}

	public function test_texts_tab_lists_every_theme_string() {
		ThemeLoader::load();
		$fields = Schema::tabs()['texts']['fields'];

		foreach ( array_keys( qe_edu_default_strings() ) as $key ) {
			$this->assertSame( 'strings.' . $key, $fields[ $key ]['path'] );
		}
	}

	public function test_paths_match_what_the_theme_reads() {
		$fields = Schema::fields();
		foreach ( array( 'templates.header', 'templates.footer', 'templates.not_found', 'header.sticky', 'header.transparent', 'header.topbar', 'header.cta_text', 'header.cta_link', 'header.mobile_menu', 'footer.copyright', 'footer.back_to_top', 'colors.mode', 'colors.button_style', 'colors.link_underline', 'typography.pair', 'typography.self_host', 'social.facebook' ) as $path ) {
			$this->assertArrayHasKey( $path, $fields );
		}
	}

	public function test_defaults_match_the_theme_fallbacks() {
		$defaults = Schema::defaults();
		$this->assertTrue( $defaults['header']['sticky'] );
		$this->assertFalse( $defaults['header']['transparent'] );
		$this->assertTrue( $defaults['header']['topbar'] );
		$this->assertSame( 'light', $defaults['colors']['mode'] );
		$this->assertSame( 'rounded', $defaults['colors']['button_style'] );
		$this->assertTrue( $defaults['colors']['link_underline'] );
		$this->assertSame( 'montserrat-open-sans', $defaults['typography']['pair'] );
		$this->assertTrue( $defaults['typography']['self_host'] );
		$this->assertTrue( $defaults['footer']['back_to_top'] );
		$this->assertSame( 'fullscreen', $defaults['header']['mobile_menu'] );
		$this->assertSame( 0, $defaults['templates']['header'] );
	}

	public function test_ten_social_networks() {
		$this->assertCount( 10, Schema::social_networks() );
	}
}
