<?php
/**
 * Settings store tests.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Plugin;

use QeEdu\Core\Settings\Settings;

/**
 * Settings.
 */
final class SettingsTest extends PluginTestCase {

	public function test_paths() {
		$data = array();
		Settings::set_path( $data, 'a.b.c', 1 );
		Settings::set_path( $data, 'a.x', 2 );
		$this->assertSame( array( 'a' => array( 'b' => array( 'c' => 1 ), 'x' => 2 ) ), $data );
		$this->assertSame( 1, Settings::get_path( $data, 'a.b.c' ) );
		$this->assertSame( 'f', Settings::get_path( $data, 'a.b.c.d', 'f' ) );

		Settings::set_path( $data, 'a.x.y', 3 );
		$this->assertSame( array( 'y' => 3 ), $data['a']['x'] );

		Settings::unset_path( $data, 'a.b.c' );
		$this->assertSame( array(), $data['a']['b'] );
		Settings::unset_path( $data, 'z.q' );
		$this->assertArrayNotHasKey( 'z', $data );
	}

	public function test_stored_get_and_all() {
		$this->options['qe_edu_settings'] = 'garbage';
		$this->assertSame( array(), Settings::stored() );

		$this->settings( array( 'header' => array( 'sticky' => false ) ) );
		$this->assertFalse( Settings::get( 'header.sticky' ) );
		$this->assertTrue( Settings::get( 'header.topbar' ) );
		$this->assertSame( 'x', Settings::get( 'unknown.path', 'x' ) );

		$all = Settings::all();
		$this->assertFalse( $all['header']['sticky'] );
		$this->assertSame( 'qe-crimson', $all['colors']['accent'] );
	}

	/**
	 * Field factory.
	 *
	 * @param string $type  Type.
	 * @param array  $extra Extra keys.
	 * @return array
	 */
	private function field( $type, $extra = array() ) {
		return array_merge(
			array(
				'type'    => $type,
				'default' => 'd',
			),
			$extra
		);
	}

	public function test_sanitize_field_per_type() {
		$this->assertTrue( Settings::sanitize_field( $this->field( 'checkbox' ), '1' ) );
		$this->assertFalse( Settings::sanitize_field( $this->field( 'checkbox' ), 'no' ) );

		$number = $this->field( 'number', array( 'default' => 16, 'min' => 14, 'max' => 20 ) );
		$this->assertSame( 18, Settings::sanitize_field( $number, '18' ) );
		$this->assertSame( 20, Settings::sanitize_field( $number, '99' ) );
		$this->assertSame( 14, Settings::sanitize_field( $number, '2' ) );
		$this->assertSame( 16, Settings::sanitize_field( $number, 'abc' ) );
		$this->assertSame( 5, Settings::sanitize_field( $this->field( 'number', array( 'default' => 1 ) ), '5' ) );

		$this->assertSame( 12, Settings::sanitize_field( $this->field( 'media' ), '12' ) );
		$this->assertSame( 7, Settings::sanitize_field( $this->field( 'template' ), '-7' ) );

		$select = $this->field( 'select', array( 'options' => array( 'a' => 'A', 'd' => 'D' ) ) );
		$this->assertSame( 'a', Settings::sanitize_field( $select, 'a' ) );
		$this->assertSame( 'd', Settings::sanitize_field( $select, 'zzz' ) );
		$this->assertSame( 'd', Settings::sanitize_field( $select, array( 'a' ) ) );

		$swatch = $this->field( 'swatch', array( 'default' => 'qe-crimson' ) );
		$this->assertSame( 'emerald', Settings::sanitize_field( $swatch, 'emerald' ) );
		$this->assertSame( 'custom', Settings::sanitize_field( $swatch, 'custom' ) );
		$this->assertSame( 'qe-crimson', Settings::sanitize_field( $swatch, 'pink' ) );
		$this->assertSame( 'qe-crimson', Settings::sanitize_field( $swatch, array() ) );

		$color = $this->field( 'color', array( 'default' => '#A41E34' ) );
		$this->assertSame( '#FFAA00', Settings::sanitize_field( $color, '#fa0' ) );
		$this->assertSame( '#A41E34', Settings::sanitize_field( $color, 'red' ) );

		$this->assertSame( 'https://x.test', Settings::sanitize_field( $this->field( 'url' ), ' https://x.test ' ) );
		$this->assertSame( '', Settings::sanitize_field( $this->field( 'url' ), array() ) );
		$this->assertSame( 'a@b.test', Settings::sanitize_field( $this->field( 'email' ), 'a@b.test' ) );
		$this->assertSame( '', Settings::sanitize_field( $this->field( 'email' ), array() ) );
		$this->assertSame( '+971 (4) 000-0000', Settings::sanitize_field( $this->field( 'tel' ), '+971 (4) 000-0000<x>' ) );
		$this->assertSame( '', Settings::sanitize_field( $this->field( 'tel' ), array() ) );
		$this->assertSame( "a\nb", Settings::sanitize_field( $this->field( 'textarea' ), "a\nb<script>" ) );
		$this->assertSame( 'x', Settings::sanitize_field( $this->field( 'code' ), 'x' ) );
		$this->assertSame( '', Settings::sanitize_field( $this->field( 'code' ), array() ) );
		$this->assertSame( 'hi', Settings::sanitize_field( $this->field( 'text' ), '<b>hi</b>' ) );
		$this->assertSame( '', Settings::sanitize_field( $this->field( 'text' ), array() ) );
		$this->assertSame( array(), Settings::sanitize_field( $this->field( 'redirects' ), 'x' ) );
	}

	public function test_sanitize_redirects() {
		$rows = Settings::sanitize_redirects(
			array(
				array( 'from' => '/old/', 'to' => '/new/' ),
				array( 'from' => 'https://example.test/legacy?x=1', 'to' => 'https://other.test/page' ),
				array( 'from' => '/same/', 'to' => '/same/' ),
				array( 'from' => '/', 'to' => '/home/' ),
				array( 'from' => '', 'to' => '/x/' ),
				array( 'from' => '/x/', 'to' => '   ' ),
				'not-a-row',
			)
		);

		$this->assertSame(
			array(
				array( 'from' => '/old/', 'to' => '/new/' ),
				array( 'from' => '/legacy', 'to' => 'https://other.test/page' ),
			),
			$rows
		);

		$many = array_fill( 0, 600, array( 'from' => '/a', 'to' => '/b' ) );
		$this->assertCount( 500, Settings::sanitize_redirects( $many ) );
	}

	public function test_sanitize_all_and_by_tab() {
		$input = array(
			'header' => array( 'cta_text' => '<i>Apply</i>' ),
			'colors' => array( 'accent' => 'graphite' ),
		);

		$all = Settings::sanitize( $input );
		$this->assertSame( 'Apply', $all['header']['cta_text'] );
		$this->assertSame( 'graphite', $all['colors']['accent'] );
		$this->assertArrayNotHasKey( 'sticky', $all['header'], 'Missing checkbox untouched when importing' );

		$tab = Settings::sanitize( $input, array( 'header' ), array( 'colors' => array( 'accent' => 'emerald' ) ) );
		$this->assertSame( 'emerald', $tab['colors']['accent'], 'Other tabs kept' );
		$this->assertFalse( $tab['header']['sticky'], 'Unticked checkbox becomes false' );
		$this->assertSame( 'Apply', $tab['header']['cta_text'] );

		$this->assertSame( array(), Settings::sanitize( 'nope', array( 'brand' ) ) );
	}

	public function test_save_tab() {
		$this->settings( array( 'colors' => array( 'accent' => 'emerald' ) ) );
		$saved = Settings::save_tab( 'header', array( 'header' => array( 'sticky' => '1' ) ) );
		$this->assertTrue( $saved['header']['sticky'] );
		$this->assertSame( 'emerald', $this->options['qe_edu_settings']['colors']['accent'] );
	}

	public function test_export_and_import_round_trip() {
		$this->settings( array( 'colors' => array( 'accent' => 'teal-lagoon' ) ) );
		$json = Settings::export();
		$data = json_decode( $json, true );
		$this->assertSame( 'qe-edu-core', $data['plugin'] );
		$this->assertSame( 'teal-lagoon', $data['settings']['colors']['accent'] );

		$this->settings( array() );
		$this->assertTrue( Settings::import( $json ) );
		$this->assertSame( 'teal-lagoon', $this->options['qe_edu_settings']['colors']['accent'] );
	}

	public function test_import_rejects_bad_files() {
		$this->assertFalse( Settings::import( 'not json' ) );
		$this->assertFalse( Settings::import( '{"settings":{}}' ) );
		$this->assertFalse( Settings::import( '{"plugin":"other","settings":{}}' ) );
		$this->assertFalse( Settings::import( '{"plugin":"qe-edu-core","settings":"x"}' ) );
		$this->assertArrayNotHasKey( 'qe_edu_settings', $this->options );
	}

	public function test_import_sanitises() {
		$this->assertTrue( Settings::import( '{"plugin":"qe-edu-core","settings":{"colors":{"accent":"<script>"}}}' ) );
		$this->assertSame( 'qe-crimson', $this->options['qe_edu_settings']['colors']['accent'] );
	}

	public function test_reset_tab() {
		$this->settings(
			array(
				'colors'    => array( 'accent' => 'emerald' ),
				'header'    => array( 'sticky' => false ),
				'templates' => array( 'header' => 5 ),
			)
		);

		$this->assertFalse( Settings::reset_tab( 'nope' ) );
		$this->assertTrue( Settings::reset_tab( 'header' ) );

		$stored = $this->options['qe_edu_settings'];
		$this->assertSame( 'emerald', $stored['colors']['accent'] );
		$this->assertArrayNotHasKey( 'sticky', $stored['header'] );
		$this->assertArrayNotHasKey( 'header', $stored['templates'], 'Fields stored outside the tab path are reset too' );
	}
}
